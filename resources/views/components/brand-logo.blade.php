@props(['size' => 40])

{{--
    Guru Traders monogram — a stand-in until a real logo file is supplied.
    Plain HTML/CSS, not inline SVG: dompdf's SVG handling turned out to
    silently drop the shape and render bare "GT" text with no box at all
    (tested — a background-color + border-radius <div> is the one thing that
    renders identically in a browser (sidebar, login) and in dompdf (PDF
    export), so this is the version to swap out once a real logo file exists.
--}}
<div style="
    display:inline-block;width:{{ $size }}px;height:{{ $size }}px;line-height:{{ $size }}px;
    background:linear-gradient(135deg, #2563eb, #1d4ed8);border-radius:{{ (int) round($size * 0.25) }}px;
    color:#ffffff;font-family:'Source Sans 3', Arial, sans-serif;font-weight:800;
    font-size:{{ (int) round($size * 0.38) }}px;letter-spacing:.5px;text-align:center;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
">GE</div>

