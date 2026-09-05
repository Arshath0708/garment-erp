<?php

namespace Tests\Unit;

use App\Support\WhatsappPhone;
use PHPUnit\Framework\TestCase;

class WhatsappPhoneTest extends TestCase
{
    public function test_ten_digit_number_gets_the_country_code(): void
    {
        $this->assertSame('919876543210', WhatsappPhone::digits('98765 43210'));
        $this->assertSame('919876543210', WhatsappPhone::digits('+91 98765-43210'));
    }

    public function test_rejects_short_or_empty_numbers(): void
    {
        $this->assertNull(WhatsappPhone::digits(null));
        $this->assertNull(WhatsappPhone::digits(''));
        $this->assertNull(WhatsappPhone::digits('12345'));
    }

    public function test_chat_url_encodes_the_message(): void
    {
        $url = WhatsappPhone::chatUrl('919876543210', 'PO GT/PO/1 confirm please');

        $this->assertStringStartsWith('https://wa.me/919876543210?text=', $url);
        $this->assertStringContainsString('PO', $url);
    }
}
