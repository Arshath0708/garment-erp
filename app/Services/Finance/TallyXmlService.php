<?php

namespace App\Services\Finance;

use App\Models\CompanyProfile;
use App\Models\DebitNote;
use App\Models\ExportDocument;
use App\Models\TallySetting;
use Illuminate\Support\Carbon;

class TallyXmlService
{
    public function salesVoucher(ExportDocument $document, TallySetting $settings): string
    {
        $document->loadMissing(['buyer', 'items.product.gstRate', 'currency']);

        $party = $document->buyer?->company_name ?: 'Buyer';
        $invoiceNo = $document->invoice_no ?: $document->doc_num;
        $date = $this->tallyDate($document->invoice_date ?? $document->created_at);
        $taxable = round($document->totalAmount(), 2);
        $tax = $this->igstAmount($document);
        $total = round($taxable + $tax, 2);

        $narration = 'Export invoice '.$invoiceNo;
        if (filled($document->gst_irn)) {
            $narration .= '. IRN '.$document->gst_irn;
            if (filled($document->gst_ack_no)) {
                $narration .= ' Ack '.$document->gst_ack_no;
            }
        }

        $entries = $this->ledgerEntry($party, $total, debit: true)
            .$this->ledgerEntry((string) $settings->sales_ledger, $taxable, debit: false);

        if ($tax > 0) {
            $entries .= $this->ledgerEntry((string) $settings->igst_ledger, $tax, debit: false);
        }

        return $this->envelope($settings, $this->voucher(
            voucherType: $settings->sales_voucher_type,
            voucherNumber: $invoiceNo,
            date: $date,
            party: $party,
            narration: $narration,
            entries: $entries,
        ));
    }

    public function debitNoteVoucher(DebitNote $note, TallySetting $settings): string
    {
        $note->loadMissing('supplier');

        $party = $note->supplier?->company_name ?: 'Jobber';
        $amount = round((float) $note->amount, 2);
        $date = $this->tallyDate($note->note_date);
        $narration = ($note->reasonLabel()).' — '.$note->debit_note_num;
        if (filled($note->notes)) {
            $narration .= '. '.$note->notes;
        }

        $entries = $this->ledgerEntry($party, $amount, debit: true)
            .$this->ledgerEntry((string) $settings->job_work_ledger, $amount, debit: false);

        return $this->envelope($settings, $this->voucher(
            voucherType: $settings->debit_note_voucher_type,
            voucherNumber: $note->debit_note_num,
            date: $date,
            party: $party,
            narration: $narration,
            entries: $entries,
        ));
    }

    public function igstAmount(ExportDocument $document): float
    {
        $tax = 0.0;
        foreach ($document->items as $item) {
            $rate = (float) ($item->product?->gstRate?->rate ?? 0);
            $tax += ((float) $item->amount) * $rate / 100;
        }

        return round($tax, 2);
    }

    private function envelope(TallySetting $settings, string $message): string
    {
        $company = $this->xml($settings->company_name ?: CompanyProfile::current()->company_name);

        return <<<XML
<ENVELOPE>
 <HEADER>
  <TALLYREQUEST>Import Data</TALLYREQUEST>
 </HEADER>
 <BODY>
  <IMPORTDATA>
   <REQUESTDESC>
    <REPORTNAME>Vouchers</REPORTNAME>
    <STATICVARIABLES>
     <SVCURRENTCOMPANY>{$company}</SVCURRENTCOMPANY>
    </STATICVARIABLES>
   </REQUESTDESC>
   <REQUESTDATA>
    <TALLYMESSAGE xmlns:UDF="TallyUDF">
{$message}
    </TALLYMESSAGE>
   </REQUESTDATA>
  </IMPORTDATA>
 </BODY>
</ENVELOPE>
XML;
    }

    private function voucher(
        string $voucherType,
        string $voucherNumber,
        string $date,
        string $party,
        string $narration,
        string $entries,
    ): string {
        $type = $this->xml($voucherType);
        $number = $this->xml($voucherNumber);
        $partyXml = $this->xml($party);
        $narrationXml = $this->xml($narration);

        return <<<XML
     <VOUCHER VCHTYPE="{$type}" ACTION="Create">
      <DATE>{$date}</DATE>
      <VOUCHERTYPENAME>{$type}</VOUCHERTYPENAME>
      <VOUCHERNUMBER>{$number}</VOUCHERNUMBER>
      <PARTYLEDGERNAME>{$partyXml}</PARTYLEDGERNAME>
      <NARRATION>{$narrationXml}</NARRATION>
{$entries}
     </VOUCHER>
XML;
    }

    private function ledgerEntry(string $name, float $amount, bool $debit): string
    {
        $isDeemed = $debit ? 'Yes' : 'No';
        $signed = $debit ? -$amount : $amount;
        $ledger = $this->xml($name);
        $amt = number_format($signed, 2, '.', '');

        return <<<XML
      <ALLLEDGERENTRIES.LIST>
       <LEDGERNAME>{$ledger}</LEDGERNAME>
       <ISDEEMEDPOSITIVE>{$isDeemed}</ISDEEMEDPOSITIVE>
       <AMOUNT>{$amt}</AMOUNT>
      </ALLLEDGERENTRIES.LIST>
XML;
    }

    private function tallyDate(mixed $date): string
    {
        return Carbon::parse($date)->format('Ymd');
    }

    private function xml(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
