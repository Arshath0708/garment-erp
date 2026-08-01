<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Buyer master — the fields the client's prototype carries and the sheet did
 * not.
 *
 * ## 1. `order_mode` — the one that changes a downstream module
 *
 * The prototype's Buyer form asks "Order Mode: OC Required / Direct Order", and
 * the OC screen branches on it:
 *
 * - **OC Required** — a full order confirmation with item lines, sent to the
 *   buyer for confirmation, and POs are raised from those lines.
 * - **Direct Order** — no OC document and no item lines. The contract number is
 *   generated so POs have something to hang off, and the items are entered at
 *   PO stage instead.
 *
 * Without this column the OC module has to ask the user which kind of order
 * they are creating every time, and can never validate the answer. It is a
 * property of the buyer — how that buyer works — so it belongs here.
 *
 * Defaulted to `oc` rather than left nullable: every existing buyer was entered
 * under the assumption that an OC is produced, so that is what they are.
 *
 * ## 2. Part-advance split
 *
 * The prototype opens two boxes when the payment term is a part advance —
 * "% Advance" and "% at Sight" — and requires them to total 100. `payment_terms`
 * is a lookup of names, so the split cannot live there: the same term row is
 * shared by every buyer using it, and the percentages differ per buyer.
 *
 * ## 3. Currency, incoterms and shipment method become many
 *
 * The sheet drew one of each and the 000800 migration built one FK each. The
 * prototype lets a buyer carry several — a buyer invoiced in USD on one order
 * and AED on the next is one buyer, not two, and "Air + Sea" is a normal answer
 * to shipment method rather than a contradiction.
 *
 * The existing FK columns are **kept** and become the default — the value
 * pre-selected on a new order, and the one the list screen shows. That is what
 * the prototype does too (`currency = currencies[0]`). Dropping them in favour
 * of the pivots alone would mean every screen that needs "this buyer's
 * currency" has to pick one out of a set and invent a rule for which.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * Which terms carry a split, stated rather than inferred.
         *
         * `payment_terms` already holds `days` instead of parsing "30 Days" out
         * of the name — the 000600 seeder says so in as many words. The same
         * argument applies harder here: "Advance" and "50% Advance, 50% on
         * Delivery" both contain the word "advance" and only the second one is
         * split, so any name-sniffing rule is wrong on the first row it meets.
         */
        Schema::table('payment_terms', function (Blueprint $table) {
            $table->boolean('has_split')->default(false)->after('days');
        });

        DB::table('payment_terms')
            ->where('name', 'like', '%\%%')
            ->update(['has_split' => true]);

        Schema::table('buyers', function (Blueprint $table) {
            $table->enum('order_mode', ['oc', 'direct'])
                ->default('oc')
                ->after('name_on_export_invoice');

            /*
             * decimal(5,2) rather than integer: a 33.33 / 66.67 split is a
             * normal way to divide a payment into thirds, and rounding it to
             * whole percent silently moves money.
             *
             * The "must total 100" rule is not a check constraint. It only
             * applies when the payment term is a part advance, and the term is
             * a foreign key to a row whose meaning the database cannot read.
             * The form request enforces it where the condition is visible.
             */
            $table->decimal('advance_percent', 5, 2)->nullable()->after('payment_term_id');
            $table->decimal('sight_percent', 5, 2)->nullable()->after('advance_percent');

            $table->index('order_mode');
        });

        /*
         * Three pivots, same shape. `restrictOnDelete` on the lookup side
         * matches buyer_category: a currency in use by a buyer cannot be
         * deleted out from under it, and the Lookups screen gets to say so in a
         * sentence rather than the database saying it in an error.
         */
        foreach ([
            'buyer_currency'        => ['currency_id', 'currencies'],
            'buyer_incoterm'        => ['incoterm_id', 'incoterms'],
            'buyer_shipment_method' => ['shipment_method_id', 'shipment_methods'],
        ] as $pivot => [$column, $lookup]) {
            Schema::create($pivot, function (Blueprint $table) use ($column, $lookup) {
                $table->id();
                $table->foreignId('buyer_id')->constrained()->cascadeOnDelete();
                $table->foreignId($column)->constrained($lookup)->restrictOnDelete();

                $table->unique(['buyer_id', $column]);
            });
        }

        /*
         * Carry the single value each buyer already has into its pivot, so the
         * two agree from the first request. Without this an existing buyer
         * would open with an empty multi-select and the default still set —
         * and saving would then clear the default it was never shown.
         */
        foreach ([
            'buyer_currency'        => 'currency_id',
            'buyer_incoterm'        => 'incoterm_id',
            'buyer_shipment_method' => 'shipment_method_id',
        ] as $pivot => $column) {
            DB::table('buyers')
                ->whereNotNull($column)
                ->orderBy('id')
                ->select('id', $column)
                ->chunk(200, function ($buyers) use ($pivot, $column) {
                    DB::table($pivot)->insertOrIgnore(
                        $buyers->map(fn ($buyer) => [
                            'buyer_id' => $buyer->id,
                            $column    => $buyer->{$column},
                        ])->all()
                    );
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_shipment_method');
        Schema::dropIfExists('buyer_incoterm');
        Schema::dropIfExists('buyer_currency');

        Schema::table('buyers', function (Blueprint $table) {
            $table->dropIndex(['order_mode']);
            $table->dropColumn(['order_mode', 'advance_percent', 'sight_percent']);
        });
    }
};
