<?php

namespace App\Http\Requests\Sales;

class UpdateOrderConfirmationRequest extends OrderConfirmationRequest
{
    protected function permission(): string
    {
        return 'order-confirmation.edit';
    }
}
