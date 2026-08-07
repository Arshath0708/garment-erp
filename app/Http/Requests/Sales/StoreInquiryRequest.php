<?php

namespace App\Http\Requests\Sales;

class StoreInquiryRequest extends InquiryRequest
{
    protected function permission(): string
    {
        return 'inquiry.create';
    }
}
