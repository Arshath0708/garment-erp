<?php

namespace App\Http\Requests\Masters;

class StoreDocumentFormatRequest extends DocumentFormatRequest
{
    protected function permission(): string
    {
        return 'po-format.create';
    }

    protected function ignoreId(): ?int
    {
        return null;
    }
}
