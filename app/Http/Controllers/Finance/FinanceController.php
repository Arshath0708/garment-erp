<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ExportDocument;
use App\Models\ExportDocumentChecklist;
use App\Models\PurchaseOrder;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function purchaseBills(): View
    {
        $rows = ExportDocumentChecklist::query()
            ->whereHas('type', fn ($q) => $q->where('code', 'purchase_bills'))
            ->with(['type:id,code,name,variant_labels', 'exportDocument:id,doc_num,buyer_id', 'exportDocument.buyer:id,company_name'])
            ->latest('id')
            ->paginate(20);

        return view('finance.purchase-bills.index', compact('rows'));
    }

    public function debitNotes(): View
    {
        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier:id,company_name', 'items:id,purchase_order_id,amount'])
            ->latest('id')
            ->paginate(20);

        return view('finance.debit-notes.index', compact('purchaseOrders'));
    }

    public function supplierPayments(): View
    {
        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier:id,company_name', 'items:id,purchase_order_id,amount'])
            ->latest('id')
            ->paginate(20);

        return view('finance.supplier-payments.index', compact('purchaseOrders'));
    }

    public function buyerReceipts(): View
    {
        $documents = ExportDocument::query()
            ->with(['buyer:id,company_name', 'items:id,export_document_id,amount', 'checklist' => fn ($q) => $q->with('type:id,code')])
            ->latest('id')
            ->paginate(20);

        return view('finance.buyer-receipts.index', compact('documents'));
    }

    public function agentCommission(): View
    {
        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier:id,company_name,agent_id', 'supplier.agent:id,name', 'items:id,purchase_order_id,qty,amount'])
            ->latest('id')
            ->paginate(20);

        return view('finance.agent-commission.index', compact('purchaseOrders'));
    }
}

