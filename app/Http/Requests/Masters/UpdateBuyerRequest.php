<?php

namespace App\Http\Requests\Masters;

class UpdateBuyerRequest extends BuyerRequest
{
    protected function permission(): string
    {
        return 'buyer.edit';
    }
}
