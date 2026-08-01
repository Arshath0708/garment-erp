<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agent master — fields the original sheet did not carry.
 *
 * The client's own working prototype (tradeos_flow, Agent Master screen) is the
 * source for everything added here. It was sent as "information reference ...
 * to explain where I need what connections", so it is treated as the field list
 * the sheet was missing rather than as a UI to copy.
 *
 * The 000600 migration built seven columns because that was all the sheet had.
 * The prototype's form has twenty-plus, and three of them change how downstream
 * modules work:
 *
 * 1. `commission_paid_by` — "Who Pays This Commission?" (we / supplier / buyer).
 *    Without it the Agent Commission screen in Accounts cannot tell whether a
 *    commission is our payable, a deduction from the supplier's bill, or a
 *    deduction from the buyer's invoice. Those are three different ledgers.
 *
 * 2. `agent_commissions` rows rather than one `commission_rate` column. The
 *    prototype lets one agent carry several entries ("+ Add commission entry"),
 *    each independently a percentage or a fixed amount per piece, and buyer-side
 *    entries carry their own currency. One decimal column cannot hold that, and
 *    the costing panel reads `commissions[0]` by type — so the type has to
 *    survive into the database, not be inferred from the number.
 *
 * 3. `payment_term` — when the commission becomes due (after the buyer pays us,
 *    after we pay the supplier, on the BL date, or monthly). This decides when a
 *    debit note is raised, so it cannot live in `remarks`.
 *
 * ## Side-dependent fields
 *
 * The prototype shows and hides blocks by agent type:
 *
 * | Field                | Supplier side | Buyer side |
 * |----------------------|---------------|------------|
 * | `gst_number`         | required      | hidden     |
 * | `pan_number`         | required      | hidden     |
 * | `ifsc_code`          | required      | hidden     |
 * | `swift_code`         | hidden        | required   |
 *
 * All four are nullable here. The schema cannot express "required when
 * agent_type = supplier" without a check constraint that MySQL 8 would enforce
 * on every write including seeds and factories; the rule belongs in the form
 * request, where it can also produce a readable message.
 *
 * `commission_rate` is dropped. It was annotated "Reserved for future use" and
 * nothing reads it — `AgentService` never sets it, the form posts it but no
 * screen displays it. Its replacement is `agent_commissions.amount`. Existing
 * values are copied into a commission row first so no data is lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            // Contact block. The prototype marks all three required; nullable
            // here so the 000600 seed data survives the migration.
            $table->string('phone', 30)->nullable()->after('display_code');
            $table->string('city', 80)->nullable()->after('phone');
            $table->string('address', 255)->nullable()->after('city');

            // Tax block — supplier side only. Lengths match the Supplier master
            // so the same validation regex applies to both.
            $table->string('gst_number', 15)->nullable()->after('address');
            $table->string('pan_number', 10)->nullable()->after('gst_number');

            // Bank block. IFSC for domestic transfers, SWIFT for international —
            // the prototype swaps the field with the agent type rather than
            // showing both, because an agent is paid one way or the other.
            $table->string('bank_name', 120)->nullable()->after('pan_number');
            $table->string('account_number', 40)->nullable()->after('bank_name');
            $table->string('ifsc_code', 11)->nullable()->after('account_number');
            $table->string('swift_code', 20)->nullable()->after('ifsc_code');

            /*
             * Who settles this commission. Nullable rather than defaulted:
             * "we pay" is the most common answer but guessing it silently would
             * put a payable on our books for an agent the supplier actually
             * pays. An unanswered field should read as unanswered.
             */
            $table->enum('commission_paid_by', ['us', 'supplier', 'buyer'])
                ->nullable()
                ->after('calculation_basis_id');

            /*
             * When it becomes due. `custom` opens a free-text box in the
             * prototype, so the description needs somewhere to live — hence the
             * companion column rather than an ever-growing enum.
             */
            $table->enum('payment_term', [
                'after_buyer',
                'after_supplier',
                'on_shipment',
                'monthly',
                'custom',
            ])->nullable()->after('commission_paid_by');

            $table->string('payment_term_custom', 255)->nullable()->after('payment_term');

            // The Buyer and Supplier forms both filter this dropdown by type and
            // then by status; 000600 indexed them separately.
            $table->index(['agent_type', 'status'], 'agents_type_status_index');
        });

        /*
         * One row per commission entry. Ordered, because the costing panel reads
         * the first entry as the one that applies and the rest are alternates —
         * without `sort_order` "first" would mean "whatever the database
         * returned", which changes when a row is edited.
         *
         * `currency_id` is nullable: supplier-side commissions are always INR
         * (the prototype hides the currency picker for them), buyer-side ones
         * carry the buyer's currency.
         */
        Schema::create('agent_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();

            /*
             * `percent` is a percentage of the commission basis (Net Value for
             * supplier side, FOB Value for buyer side — `agents.
             * calculation_basis_id` says which). `fixed` is an amount per piece.
             * Storing the type means the settlement screen never has to guess
             * whether 2 means 2% or ₹2.
             */
            $table->enum('commission_type', ['percent', 'fixed']);

            /*
             * decimal(12,4) matches buyers.agent_commission_value and
             * suppliers.agent_commission_value — a commission resolved from any
             * of the three places has the same precision.
             */
            $table->decimal('amount', 12, 4);

            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();

            $table->index(['agent_id', 'sort_order']);
        });

        /*
         * Carry `commission_rate` forward before dropping it. Percent is the
         * safe reading: `calculation_basis_id` points at a basis such as "Net
         * Value" or "FOB Value", and a fixed rupee amount is not a percentage
         * of anything — so a row with a basis and a rate was a percentage.
         */
        DB::table('agents')
            ->whereNotNull('commission_rate')
            ->orderBy('id')
            ->select('id', 'commission_rate')
            ->chunk(200, function ($agents) {
                $now = now();

                DB::table('agent_commissions')->insert(
                    $agents->map(fn ($agent) => [
                        'agent_id'        => $agent->id,
                        'commission_type' => 'percent',
                        'amount'          => $agent->commission_rate,
                        'currency_id'     => null,
                        'sort_order'      => 0,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ])->all()
                );
            });

        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->decimal('commission_rate', 8, 2)->nullable()->after('calculation_basis_id');
        });

        // Restore the single value from the first commission entry, so a
        // rollback loses the extra rows but not the primary rate.
        DB::table('agent_commissions')
            ->where('sort_order', 0)
            ->where('commission_type', 'percent')
            ->orderBy('id')
            ->select('agent_id', 'amount')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('agents')
                        ->where('id', $row->agent_id)
                        ->update(['commission_rate' => $row->amount]);
                }
            });

        Schema::dropIfExists('agent_commissions');

        Schema::table('agents', function (Blueprint $table) {
            $table->dropIndex('agents_type_status_index');
            $table->dropColumn([
                'phone',
                'city',
                'address',
                'gst_number',
                'pan_number',
                'bank_name',
                'account_number',
                'ifsc_code',
                'swift_code',
                'commission_paid_by',
                'payment_term',
                'payment_term_custom',
            ]);
        });
    }
};
