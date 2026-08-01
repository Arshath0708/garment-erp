<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\CalculationBasis;
use App\Models\Currency;
use Illuminate\Database\Seeder;

/**
 * The agents named on the client's own Buyer and Supplier example rows.
 *
 * Agent sheet col A is one side per agent, so an agent working both sides is
 * two records with two display codes — see Agent::TYPES.
 *
 * Rows are complete rather than minimal. The Agent form requires contact, tax,
 * bank and commission details, so a half-filled seed row cannot be saved from
 * the screen without inventing values — which makes "open a seeded agent and
 * change one thing" impossible, and that is the first thing anyone does.
 *
 * Idempotent on `display_code`, like every other seeder here.
 */
class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $fobBasis = CalculationBasis::where('name', 'FOB Value')->value('id');
        $netBasis = CalculationBasis::where('name', 'Net Value')->value('id');
        $usd      = Currency::where('iso_code', 'USD')->value('id');

        $agents = [
            [
                'display_code'       => 'AG01',
                'name'               => 'David',
                'agent_type'         => 'buyer',
                'phone'              => '+44 20 7946 0102',
                'city'               => 'London',
                'address'            => '14 Cheapside, London EC2V 6AA, United Kingdom',
                'bank_name'          => 'HSBC UK',
                'account_number'     => '40512367',
                'swift_code'         => 'HBUKGB4B',
                'calculation_basis_id' => $fobBasis,
                'commission_paid_by' => 'us',
                'payment_term'       => 'after_buyer',
                'commission'         => ['percent', 2.5, $usd],
            ],
            [
                'display_code'       => 'AG02',
                'name'               => 'James',
                'agent_type'         => 'buyer',
                'phone'              => '+971 4 355 1122',
                'city'               => 'Dubai',
                'address'            => 'Office 208, Al Fahidi Street, Bur Dubai, UAE',
                'bank_name'          => 'Emirates NBD',
                'account_number'     => '1015482290301',
                'swift_code'         => 'EBILAEAD',
                'calculation_basis_id' => $fobBasis,
                'commission_paid_by' => 'buyer',
                'payment_term'       => 'on_shipment',
                'commission'         => ['percent', 1.5, $usd],
            ],

            /*
             * Supplier and jobber side. Present specifically so the Buyer form
             * can be checked to NOT offer them — the col O filter is a
             * requirement, not a convenience, and an all-buyer-side list would
             * never show a bug.
             *
             * The PAN in each GSTIN matches the PAN column: the form
             * cross-checks characters 3–12, so seed rows that disagree would
             * fail the moment anyone opened one.
             */
            [
                'display_code'       => 'AG03',
                'name'               => 'Suresh',
                'agent_type'         => 'supplier',
                'phone'              => '+91 98200 41122',
                'city'               => 'Mumbai',
                'address'            => '12 Kalbadevi Road, Mumbai 400002, Maharashtra',
                'gst_number'         => '27AAECS1429B1Z6',
                'pan_number'         => 'AAECS1429B',
                'bank_name'          => 'HDFC Bank',
                'account_number'     => '50200012345678',
                'ifsc_code'          => 'HDFC0000518',
                'calculation_basis_id' => $netBasis,
                'commission_paid_by' => 'supplier',
                'payment_term'       => 'after_supplier',
                'commission'         => ['percent', 2.0, null],
            ],
            [
                'display_code'       => 'AG04',
                'name'               => 'Ravi',
                'agent_type'         => 'jobber',
                'phone'              => '+91 99040 77321',
                'city'               => 'Surat',
                'address'            => '48 Ring Road, Surat 395002, Gujarat',
                'gst_number'         => '24AAFCR8271K1Z3',
                'pan_number'         => 'AAFCR8271K',
                'bank_name'          => 'ICICI Bank',
                'account_number'     => '004301509876',
                'ifsc_code'          => 'ICIC0000043',
                'calculation_basis_id' => $netBasis,
                'commission_paid_by' => 'us',
                'payment_term'       => 'monthly',
                'commission'         => ['fixed', 5.0, null],
            ],
        ];

        foreach ($agents as $row) {
            [$type, $amount, $currencyId] = $row['commission'];
            unset($row['commission']);

            $code = $row['display_code'];
            unset($row['display_code']);

            $agent = Agent::firstOrCreate(
                ['display_code' => $code],
                $row + ['status' => 'active'],
            );

            // Separate from firstOrCreate so re-running the seeder does not
            // stack a second identical entry on an agent that already exists.
            $agent->commissions()->firstOrCreate(
                ['sort_order' => 0],
                ['commission_type' => $type, 'amount' => $amount, 'currency_id' => $currencyId],
            );
        }

        $this->command?->info('Agents seeded.');
    }
}
