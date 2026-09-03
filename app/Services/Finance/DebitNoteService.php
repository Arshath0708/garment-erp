<?php

namespace App\Services\Finance;

use App\Models\DebitNote;
use App\Models\JobWorkVoucher;
use App\Models\NumberSeries;
use App\Services\NumberSeriesService;
use App\Support\FinancialYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DebitNoteService
{
    public function __construct(private readonly NumberSeriesService $numbers)
    {
    }

    public function fromJobWorkReceive(JobWorkVoucher $voucher): DebitNote
    {
        return DB::transaction(function () use ($voucher) {
            if (! $voucher->isReceive()) {
                throw ValidationException::withMessages([
                    'job_work_voucher_id' => 'Debit notes for damage are raised from a receive voucher.',
                ]);
            }

            if ($voucher->damaged_qty <= 0) {
                throw ValidationException::withMessages([
                    'job_work_voucher_id' => 'This receive has no damaged pieces.',
                ]);
            }

            $amount = round((float) $voucher->damaged_qty * (float) $voucher->rate_per_pc, 2);
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'job_work_voucher_id' => 'Enter a rate per piece on the receive voucher before raising a debit note.',
                ]);
            }

            $exists = DebitNote::query()
                ->where('source_type', DebitNote::SOURCE_JOB_WORK)
                ->where('source_id', $voucher->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'job_work_voucher_id' => 'A debit note is already raised for this receive.',
                ]);
            }

            $financialYear = FinancialYear::current();
            $this->ensureNumberSeries($financialYear);

            $note = new DebitNote([
                'note_date'    => now()->toDateString(),
                'supplier_id'  => $voucher->jobber_id,
                'source_type'  => DebitNote::SOURCE_JOB_WORK,
                'source_id'    => $voucher->id,
                'amount'       => $amount,
                'qty'          => $voucher->damaged_qty,
                'reason'       => 'job_work_damage',
                'notes'        => "Damage on {$voucher->voucher_num}: {$voucher->damaged_qty} pcs × {$voucher->rate_per_pc}",
                'status'       => 'issued',
            ]);
            $note->financial_year = $financialYear;
            $note->debit_note_num = $this->numbers->next('debit-note', $financialYear);
            $note->save();

            return $note->fresh('supplier');
        });
    }

    private function ensureNumberSeries(string $financialYear): void
    {
        NumberSeries::firstOrCreate(
            ['module' => 'debit-note', 'financial_year' => $financialYear],
            ['prefix' => 'DN/', 'padding' => 3, 'current_number' => 0, 'reset_yearly' => true]
        );
    }
}
