<?php

namespace App\Http\Requests\Masters;

class UpdateProductRequest extends ProductRequest
{
    protected function permission(): string
    {
        return 'product.edit';
    }

    protected function ignoreId(): ?int
    {
        return $this->route('product')->id;
    }
}
