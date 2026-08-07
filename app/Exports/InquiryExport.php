<?php

namespace App\Exports;

use App\Models\Inquiry;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

/**
 * Reuses the exact same Blade view the PDF download renders — one column
 * plan, not two kept in sync by hand. PhpSpreadsheet's HTML reader (which
 * FromView delegates to) converts every <table> in the view into sheet rows,
 * which is why pdf.blade.php keeps its header block and item grid as
 * <table>s rather than <div> layout.
 */
class InquiryExport implements FromView
{
    public function __construct(private readonly Inquiry $inquiry)
    {
    }

    public function view(): View
    {
        return view('sales.inquiries.pdf', ['inquiry' => $this->inquiry, 'forExcel' => true]);
    }
}
