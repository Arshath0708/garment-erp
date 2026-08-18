<?php

namespace Database\Seeders;

use App\Models\DocumentChecklistType;
use Illuminate\Database\Seeder;

/**
 * The 26-row "Export docs to payment receipt from buyer" checklist, turned
 * into data. Order and grouping follow the sheet: packing/invoicing docs
 * first, then customs clearance, then shipping-line paperwork, then bank/
 * payment reconciliation ending in eBRC — which closes the Export Document.
 *
 * Re-runnable: firstOrCreate keyed on `code`, so editing a row's name/
 * description here and reseeding won't duplicate it, and won't touch
 * anything an admin has since changed on the Document Checklist Types screen.
 */
class DocumentChecklistTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'code' => 'packing_list', 'name' => 'Packing List', 'category' => 'generated',
                'description' => 'Compiles what each carton has — for our own record and for the customs documents.',
                'variant_labels' => ['For Our Record (with Supplier)', 'Without Supplier (for Carton)', 'For Export Documentation'],
            ],
            [
                'code' => 'item_summary', 'name' => 'Item Summary', 'category' => 'generated',
                'description' => 'Which cartons contain what, in detail — design number, supplier name, etc. — as per the cartons chosen for this shipment.',
                'variant_labels' => ['Raw Data (All Details)', 'Split by Description & Price Band (AA/AB)', 'Supplier-wise Split'],
            ],
            [
                'code' => 'export_invoice', 'name' => 'Export Invoice', 'category' => 'generated',
                'description' => 'Split by description and price band — qty, unit, price and GST based on the incoterm (FOB/CIF/CNF). '
                    .'For CIF, insurance and freight need to be added, calculated on CBM (volume weight), weight, or item value — a choice per shipment.',
                'variant_labels' => ['For Customs', 'For Buyer', 'For Bank', 'For E-way Bill'],
            ],
            [
                'code' => 'cha_checklist', 'name' => 'Checklist', 'category' => 'uploaded',
                'description' => 'Received from the Clearing House Agent (CHA) after documents are sent and uploaded to the customs website.',
            ],
            [
                'code' => 'e_sanchit_docs', 'name' => 'E-Sanchit Documents', 'category' => 'uploaded',
                'description' => 'Export invoice, packing list and declarations, signed and stamped on our letterhead.',
            ],
            [
                'code' => 'purchase_bills', 'name' => 'Purchase Bills (Part of E-Sanchit)', 'category' => 'generated',
                'description' => 'Trims, transport, packing materials and every supplier bill for this shipment, split carton-wise where the same '
                    .'goods are packed across multiple cartons — generated once the bill is scanned, so it\'s clear which goods ship in this consignment.',
                'variant_labels' => ['By Carton No. & Export Invoice/Balance Shipment', 'By Carton No., Item Description & Supplier'],
            ],
            [
                'code' => 'delivery_challan', 'name' => 'Delivery Challan', 'category' => 'generated',
                'description' => 'Sent to the customs department when goods go to the docks/airport.',
            ],
            [
                'code' => 'examination', 'name' => 'Examination', 'category' => 'manual',
                'description' => 'Recorded once goods are examined at customs — no document to upload or generate.',
            ],
            [
                'code' => 'assessed_copy', 'name' => 'Assessed Copy', 'category' => 'uploaded',
                'description' => 'Received by email from the customs department once goods are examined and passed for stuffing.',
            ],
            [
                'code' => 'leo_copy', 'name' => 'LEO Copy', 'category' => 'uploaded',
                'description' => 'The final document from customs, carrying the LEO number and date.',
            ],
            [
                'code' => 'container_seal_no', 'name' => 'Container & Seal Numbers', 'category' => 'manual',
                'description' => 'Received by email; can connect to multiple shipments when several buyers share one container.',
            ],
            [
                'code' => 'vgm', 'name' => 'VGM (Verified Gross Mass)', 'category' => 'generated',
                'description' => 'Declaration of the container/cargo\'s volume weight.',
                'variant_labels' => ['LCL Shipment', 'FCL Shipment'],
            ],
            [
                'code' => 'measurement_copy', 'name' => 'Measurement Copy', 'category' => 'uploaded',
                'description' => null,
            ],
            [
                'code' => 'clp', 'name' => 'CLP', 'category' => 'uploaded',
                'description' => 'Received from the CHA.',
            ],
            [
                'code' => 'bl_draft', 'name' => 'Bill of Lading (Draft)', 'category' => 'generated',
                'description' => 'Draft generated and sent to the shipping line, including all invoice numbers for this shipment.',
            ],
            [
                'code' => 'bl_final', 'name' => 'Bill of Lading (Final)', 'category' => 'uploaded',
                'description' => 'Original B/L emailed to the bank; physical B/L sent to the buyer to release goods — record the physical receipt date and upload a scan.',
            ],
            [
                'code' => 'insurance', 'name' => 'Insurance Certificate', 'category' => 'uploaded',
                'description' => 'Either cancel the insurance draft, or enter B/L number + date and upload the certificate.',
            ],
            [
                'code' => 'e_invoice', 'name' => 'E-Invoice', 'category' => 'generated',
                'description' => 'The export invoice converted into an e-invoice for the government website.',
            ],
            [
                'code' => 'bank_docs', 'name' => 'Docs to Bank', 'category' => 'generated',
                'description' => 'Sent to the bank within 21 days of the B/L date. GR Waiver just records the shipment with the bank; a Bill of '
                    .'Exchange is raised when the client\'s docs are routed through the bank instead — each has its own bank form to fill.',
                'variant_labels' => ['GR Waiver', 'Bill of Exchange'],
            ],
            [
                'code' => 'buyer_docs', 'name' => 'Buyer Docs', 'category' => 'generated',
                'description' => 'Emailed to the buyer once GR is released. On a Bill of Exchange, no docs go out to the buyer — only a shipment intimation is sent instead.',
                'variant_labels' => ['GR Release Email', 'Bill of Exchange Intimation'],
            ],
            [
                'code' => 'goods_received', 'name' => 'Goods Received by Client', 'category' => 'manual',
                'description' => 'Checked by tracking the container number through the shipping line.',
            ],
            [
                'code' => 'payment_received', 'name' => 'Payment Received (Swift Copy)', 'category' => 'uploaded',
                'description' => 'Swift copy from the client, or intimation from the client that payment has been made.',
            ],
            [
                'code' => 'eefc_upload', 'name' => 'Payment Proof to Bank (EEFC A/c)', 'category' => 'uploaded',
                'description' => 'The bank traces the payment; upload proof of the payment release into the EEFC account.',
            ],
            [
                'code' => 'firc', 'name' => 'FIRC', 'category' => 'uploaded',
                'description' => 'Optional — only when payment lands in a bank other than the one stated in the documents.',
            ],
            [
                'code' => 'bank_certificate', 'name' => 'Bank Certificate', 'category' => 'uploaded',
                'description' => 'Generally available about a month after payment is received.',
            ],
            [
                'code' => 'ebrc', 'name' => 'eBRC', 'category' => 'uploaded', 'closes_shipment' => true,
                'description' => 'Downloaded from the RBI website — this closes the file for the shipment.',
            ],
        ];

        foreach ($rows as $index => $row) {
            DocumentChecklistType::query()->firstOrCreate(
                ['code' => $row['code']],
                [
                    'name'            => $row['name'],
                    'description'     => $row['description'] ?? null,
                    'category'        => $row['category'],
                    'variant_labels'  => $row['variant_labels'] ?? null,
                    'closes_shipment' => $row['closes_shipment'] ?? false,
                    'sort_order'      => $index + 1,
                    'status'          => 'active',
                ]
            );
        }

        /**
         * The Generate Documents tab prints each type's description and
         * variant labels verbatim, so a description clarified after the
         * type already exists needs to reach rows that firstOrCreate()
         * above intentionally leaves alone. Whitelisted here rather than
         * syncing every row, so an admin's edit on the Document Checklist
         * Types screen for anything NOT in this list still survives a
         * reseed.
         */
        $syncDescriptions = ['packing_list', 'item_summary', 'export_invoice', 'purchase_bills', 'bank_docs', 'buyer_docs', 'insurance'];

        foreach ($rows as $row) {
            if (! in_array($row['code'], $syncDescriptions, true)) {
                continue;
            }

            DocumentChecklistType::query()->where('code', $row['code'])->update([
                'description'    => $row['description'] ?? null,
                'variant_labels' => $row['variant_labels'] ?? null,
            ]);
        }

        $this->command?->info('Document checklist types seeded ('.count($rows).' rows).');
    }
}
