<?php

namespace App\Http\Requests\Procurement;

class UpdatePurchaseOrderRequest extends PurchaseOrderRequest
{
    protected function permission(): string
    {
        return 'purchase-order.edit';
    }
}
