<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Self-contained Time-based One-Time Password (TOTP) Service (RFC 6238)
 */
class TOTPService
{
    private static string $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a secret key (Base32, 16 characters)
     */
    public function generateSecret(): string
    {
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= self::$base32Chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Calculate TOTP code for a secret at a specific time step
     */
    public function getCode(string $secret, int $timeSlice = null): string
    {
        if ($timeSlice === null) {
            $timeSlice = (int) floor(time() / 30);
        }

        $secretKey = $this->base32Decode($secret);

        // Pack time counter into 8-byte binary string
        $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N', $timeSlice);

        // HMAC-SHA1
        $hmac = hash_hmac('sha1', $time, $secretKey, true);

        // Dynamic truncation
        $offset = ord($hmac[19]) & 0xf;
        $hashPart = substr($hmac, $offset, 4);

        // Unpack value
        $value = unpack('N', $hashPart);
        $value = $value[1];
        $value = $value & 0x7fffffff;

        $modulo = pow(10, 6);
        $code = strval($value % $modulo);

        // Pad code if necessary
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a user-submitted code against a secret
     */
    public function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $currentTimeSlice = (int) floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = $this->getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate URI for Authenticator apps
     */
    public function getQRUri(string $username, string $secret, string $issuer = 'Quizzapp'): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($username) . 
               '?secret=' . $secret . '&issuer=' . rawurlencode($issuer);
    }

    /**
     * Decode a base32 string to standard binary representation
     */
    private function base32Decode(string $base32): string
    {
        $base32 = strtoupper($base32);
        $base32 = preg_replace('/[^A-Z2-7]/', '', $base32);
        
        if (empty($base32)) {
            return '';
        }

        $binary = '';
        $buffer = 0;
        $bufferSize = 0;

        $lookup = array_flip(str_split(self::$base32Chars));

        foreach (str_split($base32) as $char) {
            if (!isset($lookup[$char])) {
                continue;
            }
            $buffer = ($buffer << 5) | $lookup[$char];
            $bufferSize += 5;

            if ($bufferSize >= 8) {
                $bufferSize -= 8;
                $binary .= chr(($buffer >> $bufferSize) & 0xFF);
            }
        }

        return $binary;
    }
}
