<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manufacturing\JobWorkVoucherRequest;
use App\Models\JobWorkVoucher;
use App\Models\ProductionOrder;
use App\Models\Supplier;
use App\Services\Manufacturing\JobWorkVoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use RuntimeException;

class JobWorkVoucherController extends Controller implements HasMiddleware
{
    public function __construct(private readonly JobWorkVoucherService $vouchers)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:job-work.view', only: ['index', 'show']),
            new Middleware('permission:job-work.create', only: ['create', 'store']),
            new Middleware('permission:job-work.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $query = JobWorkVoucher::query()
            ->with(['jobber', 'productionOrder', 'garmentStyle'])
            ->latest('id');

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('voucher_num', 'like', $term)
                    ->orWhereHas('jobber', fn ($j) => $j->where('company_name', 'like', $term))
                    ->orWhereHas('productionOrder', fn ($p) => $p->where('order_number', 'like', $term));
            });
        }

        return view('job-work.index', [
            'vouchers' => $query->paginate(20)->withQueryString(),
            'filters'  => $request->only('search', 'type'),
        ]);
    }

    public function create(Request $request): View
    {
        $orderId = $request->integer('production_order_id') ?: null;
        $order = $orderId
            ? ProductionOrder::query()->with(['garmentStyle', 'jobber'])->find($orderId)
            : null;

        return view('job-work.create', [
            'selectedOrder' => $order,
            'outstanding'   => $this->vouchers->outstanding($order),
            'type'          => $request->string('type', 'issue')->toString() === 'receive' ? 'receive' : 'issue',
            'orders'        => ProductionOrder::query()->with('garmentStyle')->latest('id')->limit(80)->get(),
            'jobbers'       => Supplier::query()->ofParty('jobber')->orderBy('company_name')->get(),
        ]);
    }

    public function store(JobWorkVoucherRequest $request): RedirectResponse
    {
        $voucher = $this->vouchers->create($request->validated());

        $msg = $voucher->isIssue()
            ? "Issue {$voucher->voucher_num}: sent {$voucher->total_qty} pcs to {$voucher->jobber?->company_name}."
            : "Receive {$voucher->voucher_num}: {$voucher->goodQty()} good, {$voucher->damaged_qty} damaged.";

        return redirect()->route('job-work.show', $voucher)->with('success', $msg);
    }

    public function show(JobWorkVoucher $jobWorkVoucher): View
    {
        $jobWorkVoucher->load(['jobber', 'productionOrder', 'garmentStyle', 'creator', 'debitNotes']);

        return view('job-work.show', [
            'voucher'     => $jobWorkVoucher,
            'outstanding' => $this->vouchers->outstanding($jobWorkVoucher->productionOrder),
        ]);
    }

    public function destroy(JobWorkVoucher $jobWorkVoucher): RedirectResponse
    {
        try {
            $num = $jobWorkVoucher->voucher_num;
            $this->vouchers->delete($jobWorkVoucher);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return redirect()->route('job-work.index')->with('success', "Voucher {$num} deleted.");
    }
}
