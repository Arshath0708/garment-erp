<?php

namespace App\Http\Requests\Masters;

class UpdateDocumentFormatRequest extends DocumentFormatRequest
{
    protected function permission(): string
    {
        return 'po-format.edit';
    }

    protected function ignoreId(): ?int
    {
        return $this->route('format')?->id;
    }
}
