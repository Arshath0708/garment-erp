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
        $supplier = $this->route('supplier') ?? $this->route('jobber');

        return $supplier instanceof \App\Models\Supplier ? $supplier->id : (int) $supplier;
    }
}
