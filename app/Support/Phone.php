<?php

namespace App\Support;

class Phone
{
    public static function normalize(?string $phone): string
    {
        return preg_replace('/\D+/', '', $phone ?? '') ?? '';
    }

    public static function normalizeForBrazil(string $phone, bool $removeNinthDigit = false): string
    {
        $normalized = self::normalize($phone);
        if ($normalized === '') {
            return '';
        }

        if (str_starts_with($normalized, '55')) {
            if (strlen($normalized) === 13) {
                return $removeNinthDigit ? self::removeNinthDigitIfMobile($normalized) : $normalized;
            }

            return $normalized;
        }

        // Treat local BR mobile numbers (DDD + 9 digits) as Brazilian and add country code.
        if (preg_match('/^(?:1[1-9]|2[12478]|3[1-578]|4[1-69]|5[1345]|6[1-9]|7[134579]|8[1-9]|9[1-9])9\d{8}$/', $normalized) !== 1) {
            return $normalized;
        }

        $withCountryCode = '55' . $normalized;

        return $removeNinthDigit ? self::removeNinthDigitIfMobile($withCountryCode) : $withCountryCode;
    }

    private static function removeNinthDigitIfMobile(string $phone): string
    {
        if (! str_starts_with($phone, '55') || strlen($phone) !== 13) {
            return $phone;
        }

        if (substr($phone, 4, 1) !== '9') {
            return $phone;
        }

        return substr($phone, 0, 4) . substr($phone, 5);
    }

    public static function format(?string $phone): string
    {
        $phone = self::normalize($phone);

        if (strlen($phone) === 11) {
            return sprintf(
                '(%s) %s-%s',
                substr($phone, 0, 2),
                substr($phone, 2, 5),
                substr($phone, 7, 4)
            );
        }

        if (strlen($phone) === 10) {
            return sprintf(
                '(%s) %s-%s',
                substr($phone, 0, 2),
                substr($phone, 2, 4),
                substr($phone, 6, 4)
            );
        }

        return $phone;
    }
}
