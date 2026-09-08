<?php

namespace App\Services\Manufacturing;

use App\Models\JobWorkVoucher;
use App\Models\NumberSeries;
use App\Models\ProductionOrder;
use App\Services\NumberSeriesService;
use App\Support\FinancialYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JobWorkVoucherService
{
    public function __construct(private readonly NumberSeriesService $numbers)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): JobWorkVoucher
    {
        return DB::transaction(function () use ($data) {
            $order = ! empty($data['production_order_id'])
                ? ProductionOrder::query()->with('garmentStyle')->findOrFail($data['production_order_id'])
                : null;

            $sizes = $this->normalizeSizes($data['sizes'] ?? []);
            $total = array_sum($sizes);
            $damaged = (int) ($data['damaged_qty'] ?? 0);
            $type = $data['type'];

            if ($total <= 0) {
                throw ValidationException::withMessages([
                    'sizes' => 'Enter how many pieces you are sending or receiving.',
                ]);
            }

            if ($type === 'receive') {
                if ($damaged > $total) {
                    throw ValidationException::withMessages([
                        'damaged_qty' => 'Damage cannot be more than pieces received.',
                    ]);
                }
                if ($order) {
                    $out = $this->outstanding($order);
                    if ($total > $out) {
                        throw ValidationException::withMessages([
                            'sizes' => "Only {$out} pcs are still with the jobber.",
                        ]);
                    }
                }
            } else {
                $damaged = 0;
            }

            $financialYear = FinancialYear::current();
            $this->ensureNumberSeries($financialYear);

            $voucher = new JobWorkVoucher([
                'voucher_date'         => $data['voucher_date'] ?? now()->toDateString(),
                'type'                 => $type,
                'jobber_id'            => $data['jobber_id'],
                'production_order_id'  => $order?->id,
                'garment_style_id'     => $order?->garment_style_id,
                'process'              => $data['process'] ?? $order?->job_work_type,
                'vehicle_no'           => $data['vehicle_no'] ?? null,
                'total_qty'            => $total,
                'damaged_qty'          => $damaged,
                'rate_per_pc'          => $type === 'receive' ? (float) ($data['rate_per_pc'] ?? 0) : 0,
                'charge_amount'        => 0,
                'size_qty'             => $sizes,
                'notes'                => $data['notes'] ?? null,
            ]);
            if ($type === 'receive') {
                $rate = (float) ($data['rate_per_pc'] ?? 0);
                $voucher->charge_amount = round(max(0, $total - $damaged) * $rate, 2);
            }
            $voucher->financial_year = $financialYear;
            $voucher->voucher_num = $this->numbers->next('job-work', $financialYear);
            $voucher->save();

            return $voucher->fresh(['jobber', 'productionOrder', 'garmentStyle']);
        });
    }

    public function outstanding(?ProductionOrder $order): int
    {
        if (! $order) {
            return 0;
        }

        $issued = (int) JobWorkVoucher::query()
            ->where('production_order_id', $order->id)
            ->where('type', 'issue')
            ->sum('total_qty');

        $back = (int) JobWorkVoucher::query()
            ->where('production_order_id', $order->id)
            ->where('type', 'receive')
            ->sum('total_qty');

        return max(0, $issued - $back);
    }

    public function delete(JobWorkVoucher $voucher): void
    {
        if ($voucher->isIssue()) {
            $laterReceive = JobWorkVoucher::query()
                ->where('production_order_id', $voucher->production_order_id)
                ->where('type', 'receive')
                ->where('id', '>', $voucher->id)
                ->exists();
            if ($laterReceive) {
                throw new \RuntimeException('Cannot delete this issue — pieces have already been received against it.');
            }
        }

        $voucher->delete();
    }

    /**
     * @param  array<string, mixed>  $posted
     * @return array<string, int>
     */
    private function normalizeSizes(array $posted): array
    {
        $out = [];
        foreach (ProductionOrder::SIZES as $size) {
            $qty = (int) ($posted[$size] ?? 0);
            if ($qty > 0) {
                $out[$size] = $qty;
            }
        }

        return $out;
    }

    private function ensureNumberSeries(string $financialYear): void
    {
        NumberSeries::firstOrCreate(
            ['module' => 'job-work', 'financial_year' => $financialYear],
            ['prefix' => 'JW/', 'padding' => 3, 'current_number' => 0, 'reset_yearly' => true]
        );
    }
}
