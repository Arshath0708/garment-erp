<?php

namespace App\Http\Requests\Procurement;

class StorePurchaseOrderRequest extends PurchaseOrderRequest
{
    protected function permission(): string
    {
        return 'purchase-order.create';
    }
}
