<?php

namespace App\Http\Requests\Masters;

class StoreProductRequest extends ProductRequest
{
    protected function permission(): string
    {
        return 'product.create';
    }

    protected function ignoreId(): ?int
    {
        return null;
    }
}
