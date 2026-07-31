<?php

namespace App\Http\Requests\Masters;

class UpdateSupplierRequest extends SupplierRequest
{
    protected function permission(): string
    {
        return 'supplier.edit';
    }

    protected function ignoreId(): ?int
    {
        return $this->route('supplier')->id;
    }
}
