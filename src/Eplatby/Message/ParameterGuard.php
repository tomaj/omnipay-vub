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
        if (preg_match('/^\d{1,10}$/D', $ss) !== 1) {
            throw new InvalidRequestException('The ss parameter must be up to 10 digits');
        }
        if (preg_match('/^\d{10}$/D', $vs) !== 1) {
            throw new InvalidRequestException('The vs parameter must be exactly 10 digits when ss is set');
        }
    }
}
