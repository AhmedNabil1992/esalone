<?php

namespace App\Support\Tenant;

class WhatsAppPhoneNumber
{
    public static function resolve(?string $phone, ?string $countryCode): ?string
    {
        $phone = preg_replace('/\D/', '', $phone ?? '') ?? '';

        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }

        if ($phone === '') {
            return null;
        }

        $countryCode = preg_replace('/\D/', '', $countryCode ?? '') ?? '';

        if ($countryCode === '2' && (str_starts_with($phone, '01') || str_starts_with($phone, '1'))) {
            $countryCode = '20';
        }

        if ($countryCode !== '' && ! str_starts_with($phone, $countryCode)) {
            if (str_starts_with($phone, '0')) {
                $phone = substr($phone, 1);
            }

            $phone = $countryCode.$phone;
        }

        return $phone;
    }
}
