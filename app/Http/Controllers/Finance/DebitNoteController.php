<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\DebitNote;
use App\Models\JobWorkVoucher;
use App\Models\PurchaseOrder;
use App\Services\Finance\DebitNoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DebitNoteController extends Controller implements HasMiddleware
{
    public function __construct(private readonly DebitNoteService $debitNotes)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:debit-note.view', only: ['index']),
            new Middleware('permission:debit-note.create', only: ['store']),
        ];
    }

    public function index(): View
    {
        $notes = DebitNote::query()
            ->with(['supplier:id,company_name', 'jobWorkVoucher:id,voucher_num,type'])
            ->latest('id')
            ->paginate(20);

        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier:id,company_name', 'items:id,purchase_order_id,amount'])
            ->latest('id')
            ->limit(10)
            ->get();

        return view('finance.debit-notes.index', compact('notes', 'purchaseOrders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'job_work_voucher_id' => ['required', 'integer', 'exists:job_work_vouchers,id'],
        ]);

        $voucher = JobWorkVoucher::query()->findOrFail($request->integer('job_work_voucher_id'));

        try {
            $note = $this->debitNotes->fromJobWorkReceive($voucher);
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->with('warning', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('job-work.show', $voucher)
            ->with('success', "Debit note {$note->debit_note_num} raised for {$note->qty} damaged pcs ({$note->amount}).");
    }
}
