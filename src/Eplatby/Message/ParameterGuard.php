<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Message;

use Omnipay\Common\Exception\InvalidRequestException;

/**
 * Checks shared by the purchase and the complete purchase request.
 *
 * @internal
 */
final readonly class ParameterGuard
{
    /**
     * Variable and specific symbol: digits only, 1 to 10 (the Slovak payment symbol format).
     */
    private const string SYMBOL_PATTERN = '/\A[0-9]{1,10}\z/';

    /**
     * Constant symbol: digits only, 1 to 4.
     */
    private const string CONSTANT_SYMBOL_PATTERN = '/\A[0-9]{1,4}\z/';

    /**
     * Returns the shared secret, or throws when it is missing, empty or only whitespace:
     * an empty HMAC key would sign (and verify) with a key anyone knows.
     *
     * @throws InvalidRequestException
     */
    public static function sharedSecret(#[\SensitiveParameter] ?string $sharedSecret): string
    {
        if ($sharedSecret === null || trim($sharedSecret) === '') {
            throw new InvalidRequestException('The sharedSecret parameter is required');
        }

        return $sharedSecret;
    }

    /**
     * @throws InvalidRequestException when the variable symbol is not 1 to 10 digits
     */
    public static function variableSymbol(string $vs): void
    {
        if (preg_match(self::SYMBOL_PATTERN, $vs) !== 1) {
            throw new InvalidRequestException('The vs parameter must be up to 10 digits');
        }
    }

    /**
     * The constant symbol is optional: `null` and `''` are not sent.
     *
     * @throws InvalidRequestException when a constant symbol is set and is not 1 to 4 digits
     */
    public static function constantSymbol(?string $cs): void
    {
        if ($cs !== null && $cs !== '' && preg_match(self::CONSTANT_SYMBOL_PATTERN, $cs) !== 1) {
            throw new InvalidRequestException('The cs parameter must be up to 4 digits');
        }
    }

    /**
     * The payment request signs VS, SS and CS concatenated without a separator. With a constant symbol, the
     * variable symbol must have the full length of 10 digits, so that the signed digits can only be split one way.
     *
     * @throws InvalidRequestException
     */
    public static function variableSymbolWithConstantSymbol(string $vs, ?string $cs): void
    {
        if ($cs === null || $cs === '') {
            return;
        }
        if (preg_match('/\A[0-9]{10}\z/', $vs) !== 1) {
            throw new InvalidRequestException('The vs parameter must be exactly 10 digits when cs is set');
        }
    }

    /**
     * The return URL must be absolute, so the bank can send the customer back, and use HTTPS
     * (plain HTTP only in test mode, for a shop running locally).
     *
     * @throws InvalidRequestException when the URL is not absolute, has another scheme, or contains whitespace
     *                                 or control characters
     */
    public static function returnUrl(string $rurl, bool $testMode): void
    {
        $message = $testMode
            ? 'The rurl parameter must be an absolute https:// or http:// URL'
            : 'The rurl parameter must be an absolute https:// URL';
        if (preg_match('/[\x00-\x20\x7F]/', $rurl) === 1) {
            throw new InvalidRequestException($message);
        }
        $scheme = parse_url($rurl, PHP_URL_SCHEME);
        $host = parse_url($rurl, PHP_URL_HOST);
        if (!is_string($scheme) || !is_string($host) || $host === '') {
            throw new InvalidRequestException($message);
        }
        $scheme = strtolower($scheme);
        if ($scheme !== 'https' && ($scheme !== 'http' || !$testMode)) {
            throw new InvalidRequestException($message);
        }
    }

    /**
     * VÚB signs VS and SS concatenated without a separator. With a specific symbol, the variable symbol
     * must have the full length of 10 digits, so that a signed VS + SS can only be split one way.
     *
     * @throws InvalidRequestException
     */
    public static function variableSymbolWithSpecificSymbol(string $vs, ?string $ss): void
    {
        if ($ss === null || $ss === '') {
            return;
        }
        if (preg_match(self::SYMBOL_PATTERN, $ss) !== 1) {
            throw new InvalidRequestException('The ss parameter must be up to 10 digits');
        }
        if (preg_match('/\A[0-9]{10}\z/', $vs) !== 1) {
            throw new InvalidRequestException('The vs parameter must be exactly 10 digits when ss is set');
        }
    }
}
