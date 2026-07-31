<?php

namespace App\Http\Requests\Masters;

class StoreSupplierRequest extends SupplierRequest
{
    protected function permission(): string
    {
        return 'supplier.create';
    }

    protected function ignoreId(): ?int
    {
        return null;
    }
}
