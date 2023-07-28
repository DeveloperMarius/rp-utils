<?php

namespace utils;

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

class JWT{

    /**
     * @param string $secret
     * @param array $payload
     * @param string|null $issuer - Der Aussteller des Tokens  z.B. https://repaste.de
     * @param string|null $subject - Definiert für welches Subjekt die Claims gelten. Das sub-Feld definiert also für wen oder was die Claims getätigt werden.
     * @param string|null $audience - Die Zieldomäne, für die das Token ausgestellt wurde.
     * @param int|null $expire - Das Ablaufdatum des Tokens in Unixzeit, also der Anzahl der Sekunden seit 1970-01-01T00:00:00Z.
     * @param string|int $not_before - Die Unixzeit, ab der das Token gültig ist.
     * @return string
     */
    public static function generateJWT(string $secret, array $payload, ?int $expire = null, ?string $issuer = null, ?string $subject = null, ?string $audience = null, string|int $not_before = 'now'): string{
        if($issuer !== null)
            $payload['iss'] = $issuer;
        if($subject !== null)
            $payload['sub'] = $subject;
        if($audience !== null)
            $payload['aud'] = $audience;
        if($expire !== null)
            $payload['exp'] = $expire;
        $payload['nbf'] = $not_before === 'now' ? Time::getCurrentTimestamp(false) : $not_before;
        $payload['iat'] = Time::getCurrentTimestamp(false);

        return \Firebase\JWT\JWT::encode($payload, $secret, 'HS256');
        /*$header = json_encode(array(
            'typ' => 'JWT',
            'alg' => 'HS256'
        ));

        if($expire !== null)
            $payload['exp'] = $expire;

        $payload = json_encode($payload);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, $secret, true);

        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;*/
    }

    public static function validate(string $secret, string $jwt): bool{
        return self::parse($secret, $jwt) !== null;
        /*$tokenParts = explode('.', $jwt);
        $header = base64_decode($tokenParts[0]);
        $payload = base64_decode($tokenParts[1]);
        $signatureProvided = $tokenParts[2];

        // check the expiration time - note this will cause an error if there is no 'exp' claim in the token
        $expiration = Carbon::createFromTimestamp(json_decode($payload)->exp);
        $tokenExpired = (Carbon::now()->diffInSeconds($expiration, false) < 0);

        // build a signature based on the header and payload using the secret
        $base64UrlHeader = base64UrlEncode($header);
        $base64UrlPayload = base64UrlEncode($payload);
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = base64UrlEncode($signature);

        // verify it matches the signature provided in the token
        $signatureValid = ($base64UrlSignature === $signatureProvided);

        return $signatureValid && (!$validate_expire || !$tokenExpired);*/
    }

    public static function parse(string $secret, string $jwt): ?array{
        try {
            $decoded = \Firebase\JWT\JWT::decode($jwt, new Key($secret, 'HS256'));
            return (array) $decoded;
        } catch (\InvalidArgumentException $e) {
            // provided key/key-array is empty or malformed.
        } catch (\DomainException $e) {
            // provided algorithm is unsupported OR
            // provided key is invalid OR
            // unknown error thrown in openSSL or libsodium OR
            // libsodium is required but not available.
        } catch (SignatureInvalidException $e) {
            // provided JWT signature verification failed.
        } catch (BeforeValidException $e) {
            // provided JWT is trying to be used before "nbf" claim OR
            // provided JWT is trying to be used before "iat" claim.
        } catch (ExpiredException $e) {
            // provided JWT is trying to be used after "exp" claim.
        } catch (\UnexpectedValueException $e) {
            // provided JWT is malformed OR
            // provided JWT is missing an algorithm / using an unsupported algorithm OR
            // provided JWT algorithm does not match provided key OR
            // provided key ID in key/key-array is empty or invalid.
        }
        return null;
    }

    public static function parseFromHeader(string $header): string{
        if(str_starts_with($header, 'Bearer '))
            $header = str_replace('Bearer ', '', $header);
        return $header;
    }
}