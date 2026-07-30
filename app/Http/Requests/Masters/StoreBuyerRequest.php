<?php

namespace App\Http\Requests\Masters;

class StoreBuyerRequest extends BuyerRequest
{
    protected function permission(): string
    {
        return 'buyer.create';
    }

    protected function ignoreId(): ?int
    {
        return null;
    }
}
