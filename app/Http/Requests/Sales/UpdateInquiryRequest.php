<?php

namespace App\Http\Requests\Sales;

class UpdateInquiryRequest extends InquiryRequest
{
    protected function permission(): string
    {
        return 'inquiry.edit';
    }
}
