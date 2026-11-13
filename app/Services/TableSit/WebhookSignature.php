<?php

namespace App\Services\TableSit;

class WebhookSignature
{
    public function valid(string $body, string $header, string $secret, int $now): bool
    {
        if ($secret === '' || strlen($header) > 2048) {
            return false;
        }
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key === 't') {
                if ($timestamp !== null || ! ctype_digit($value)) {
                    return false;
                }
                $timestamp = $value;
            } elseif ($key === 'v1' && preg_match('/^[a-f0-9]{64}$/i', $value)) {
                $signatures[] = strtolower($value);
            }
        }
        if ($timestamp === null || abs($now - (int) $timestamp) > 300) {
            return false;
        }
        $expected = hash_hmac('sha256', $timestamp.'.'.$body, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }
        return false;
    }
}
