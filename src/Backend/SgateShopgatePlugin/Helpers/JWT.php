<?php
/**
 * Copyright Shopgate Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Shopgate Inc, 804 Congress Ave, Austin, Texas 78701 <interfaces@shopgate.com>
 * @copyright Shopgate Inc
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

namespace Shopgate\Helpers;

class JWT {
    /**
     * Decodes a JWT string into a PHP object.
     *
     * @param string        $jwt            The JWT
     * @param string|array  $key            The key, or map of keys.
     *                                      If the algorithm used is asymmetric, this is the public key
     *
     * @return object The JWT's payload as a PHP object
     *
     * @throws UnexpectedValueException     Provided JWT was invalid
     *
     * @uses urlsafeB64Decode
     */
    public static function jwtDecode($jwt, $key)
    {
        $timestamp = time();
        if (empty($key)) {
            throw new InvalidArgumentException('Key may not be empty');
        }
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            throw new UnexpectedValueException('Wrong number of segments');
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        if (null === ($header = json_decode(static::urlsafeB64Decode($headb64), false, 512, JSON_BIGINT_AS_STRING))) {
            throw new UnexpectedValueException('Invalid header encoding');
        }
        if (null === $payload = json_decode(static::urlsafeB64Decode($bodyb64), false, 512, JSON_BIGINT_AS_STRING)) {
            throw new UnexpectedValueException('Invalid claims encoding');
        }
        if (false === ($sig = static::urlsafeB64Decode($cryptob64))) {
            throw new UnexpectedValueException('Invalid signature encoding');
        }
        if (is_array($key) || $key instanceof \ArrayAccess) {
            if (isset($header->kid)) {
                if (!isset($key[$header->kid])) {
                    throw new UnexpectedValueException('"kid" invalid, unable to lookup correct key');
                }
                $key = $key[$header->kid];
            } else {
                throw new UnexpectedValueException('"kid" empty, unable to lookup correct key');
            }
        }
        // Check the signature
        if (!static::jwtVerify("$headb64.$bodyb64", $sig, $key)) {
            throw new UnexpectedValueException('Signature verification failed');
        }
        // Check if the nbf if it is defined. This is the time that the
        // token can actually be used. If it's not yet that time, abort.
        if (isset($payload->nbf) && $payload->nbf > ($timestamp + 100)) {
            throw new UnexpectedValueException(
                'Cannot handle token prior to ' . date(DateTime::ATOM, $payload->nbf)
            );
        }
        // Check that this token has been created before 'now'. This prevents
        // using tokens that have been created for later use (and haven't
        // correctly used the nbf claim).
        if (isset($payload->iat) && $payload->iat > ($timestamp + 100)) {
            throw new UnexpectedValueException(
                'Cannot handle token prior to ' . date(DateTime::ATOM, $payload->iat)
            );
        }
        // Check if this token has expired.
        if (isset($payload->exp) && ($timestamp - 100) >= $payload->exp) {
            throw new UnexpectedValueException('Expired token');
        }
        return $payload;
    }

    /**
     * Verify a signature with the message, key and method. Not all methods
     * are symmetric, so we must have a separate verify and sign method.
     *
     * @param string            $msg        The original message (header and body)
     * @param string            $signature  The original signature
     * @param string|resource   $key        For HS*, a string key works. for RS*, must be a resource of an openssl public key
     *
     * @return bool
     *
     * @throws DomainException Invalid Algorithm or OpenSSL failure
     */
    private static function jwtVerify($msg, $signature, $key)
    {
        $hash = hash_hmac('SHA256', $msg, $key, true);
        if (function_exists('hash_equals')) {
            return hash_equals($signature, $hash);
        }
        $len = min(static::safeStrlen($signature), static::safeStrlen($hash));
        $status = 0;
        for ($i = 0; $i < $len; $i++) {
            $status |= (ord($signature[$i]) ^ ord($hash[$i]));
        }
        $status |= (static::safeStrlen($signature) ^ static::safeStrlen($hash));
        return ($status === 0);
    }

    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     */
    private static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }
}