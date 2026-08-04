<?php

namespace App\Http\Requests\Masters;

class UpdateMarkupRequest extends MarkupRequest
{
    protected function permission(): string
    {
        return 'markup.edit';
    }

    protected function ignoreId(): ?int
    {
        return $this->route('markup')?->id;
    }
}
