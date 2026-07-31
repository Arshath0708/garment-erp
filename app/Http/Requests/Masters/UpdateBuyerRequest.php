<?php

namespace App\Http\Requests\Masters;

class UpdateBuyerRequest extends BuyerRequest
{
    protected function permission(): string
    {
        return 'buyer.edit';
    }

    protected function ignoreId(): ?int
    {
        return $this->route('buyer')->id;
    }
}
