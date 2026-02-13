<?php

namespace App\Services;

use Illuminate\Support\Str;

class InviteTokenService
{
    public function generateRawToken(int $length = 48): string
    {
        return Str::random($length);
    }

    public function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }
}
