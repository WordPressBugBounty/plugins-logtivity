<?php

/**
 * @package   Logtivity
 * @copyright 2025-2026 Logtivity. All rights reserved
 * @contact   logtivity.io, hello@logtivity.io
 *
 * This file is part of Logtivity
 */

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

class Logtivity_JsonWebToken
{
    /**
     * @var string
     */
    protected string $algorithm = 'sha512';

    /**
     * @var int
     */
    protected int $expireSeconds = 5;

    /**
     * @param string  $secret
     * @param array   $payload
     * @param ?string $algorithm
     *
     * @return string
     * @throws Exception
     */
    public function create(string $secret, array $payload = [], ?string $algorithm = null): string
    {
        $header = [
            'alg' => $this->algorithm,
            'typ' => 'JWT',
        ];

        if (empty($payload['exp'])) {
            $payload['exp'] = time() + $this->expireSeconds;
        }

        $signInput = join('.', [
            static::base64Encode(static::jsonEncode($header)),
            static::base64Encode(static::jsonEncode($payload)),
        ]);

        if ($algorithm) {
            $this->useAlgorithm($algorithm);
        }
        $signature = hash_hmac($this->algorithm, $signInput, $secret, true);

        return $signInput . '.' . static::base64Encode($signature);
    }

    /**
     * Parse a JWT token created by this helper
     *
     * @param string $token
     * @param string $secret
     *
     * @return object
     * @throws Exception
     */
    public function parse(string $token, string $secret): object
    {
        $atoms = explode('.', $token);
        if (count($atoms) != 3) {
            throw new Exception('Invalid JWT Token');
        } else {
            [$header64, $payload64, $signature64] = $atoms;

            $signature = $this->base64Decode($signature64);
            if (!$signature) {
                throw new Exception('Invalid JWT Signature');
            }

            $payloadJson = $this->base64Decode($payload64);
            if ($payloadJson) {
                $hash = hash_hmac($this->algorithm, "{$header64}.{$payload64}", $secret, true);
                if (hash_equals($hash, $signature)) {
                    $payload = $this->jsonDecode($payloadJson);
                    if (empty($payload->exp) == false && $payload->exp < time()) {
                        throw new Exception('Expired JWT token');
                    }

                    return $payload;
                } else {
                    throw new Exception('Invalid JWT Token');
                }
            } else {
                throw new Exception('Invalid JWT Payload');
            }
        }
    }

    /**
     * @param string $algorithm
     *
     * @return $this
     * @throws Exception
     */
    public function useAlgorithm(string $algorithm): self
    {
        if (in_array($algorithm, hash_algos(), true)) {
            $this->algorithm = $algorithm;

            return $this;
        }

        throw new Exception('Invalid Hash Algorithm');
    }

    /**
     * Base64url encode JWT segments.
     *
     * JWT compact serialization requires base64url encoding for the header,
     * payload, and binary signature. This is protocol encoding, not
     * obfuscation.
     *
     * @param string $input
     *
     * @return string
     */
    protected function base64Encode(string $input): string
    {
        return str_replace(
            '=',
            '',
            strtr(
                // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- JWT base64url encoding requires Base64.
                base64_encode($input),
                '+/',
                '-_'
            )
        );
    }

    /**
     * Base64url decode JWT segments.
     *
     * JWT compact serialization stores header, payload, and signature as
     * base64url. Strict Base64 decoding is required after translating the URL
     * safe alphabet.
     *
     * @param string $input
     *
     * @return string
     */
    protected function base64Decode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padding = 4 - $remainder;
            $input   .= str_repeat('=', $padding);
        }

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- JWT base64url decoding requires Base64 with strict mode enabled.
        $decoded = base64_decode(strtr($input, '-_', '+/'), true);

        return false === $decoded ? '' : $decoded;
    }

    /**
     * JSON Encode without escape characters
     *
     * @param array $input
     *
     * @return string
     */
    protected function jsonEncode(array $input): string
    {
        return wp_json_encode($input, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param ?string $input
     *
     * @return ?object
     * @throws Exception
     */
    protected function jsonDecode(?string $input): ?object
    {
        $result = json_decode((string)$input, false, 512, JSON_BIGINT_AS_STRING);
        if ($result && is_array($result)) {
            $result = (object)$result;
        }

        if ($input && empty($result)) {
            throw new Exception('Invalid JSON');
        }

        return $result ?: null;
    }
}
