<?php

namespace App\Http\Requests\Sales;

class StoreOrderConfirmationRequest extends OrderConfirmationRequest
{
    protected function permission(): string
    {
        return 'order-confirmation.create';
    }
}
