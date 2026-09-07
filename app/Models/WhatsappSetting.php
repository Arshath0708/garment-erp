<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class WhatsappSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'phone_number_id',
        'access_token_encrypted',
        'graph_version',
        'country_code',
    ];

    protected $hidden = [
        'access_token_encrypted',
    ];

    protected $attributes = [
        'is_enabled' => false,
        'graph_version' => 'v21.0',
        'country_code' => '91',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->first() ?? static::create([]);
    }

    public function hasToken(): bool
    {
        return filled($this->access_token_encrypted) || filled(config('services.whatsapp.token'));
    }

    public function accessToken(): ?string
    {
        if (filled($this->access_token_encrypted)) {
            try {
                return Crypt::decryptString($this->access_token_encrypted);
            } catch (\Throwable) {
                return null;
            }
        }

        $fromEnv = config('services.whatsapp.token');

        return filled($fromEnv) ? (string) $fromEnv : null;
    }

    public function storeToken(?string $plain): void
    {
        if (! filled($plain)) {
            return;
        }

        $this->access_token_encrypted = Crypt::encryptString($plain);
        $this->save();
    }
}
