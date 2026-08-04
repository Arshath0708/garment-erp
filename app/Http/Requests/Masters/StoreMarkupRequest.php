<?php

namespace App\Http\Requests\Masters;

class StoreMarkupRequest extends MarkupRequest
{
    protected function permission(): string
    {
        return 'markup.create';
    }

    protected function ignoreId(): ?int
    {
        return null;
    }
}
