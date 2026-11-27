<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Sign;

final readonly class HmacSign
{
    public function sign(string $input, #[\SensitiveParameter] string $secret): string
    {
        $inputString = pack('A*', $input);
        $sharedSecret = pack('A*', $secret);
        return strtoupper(hash_hmac('sha256', $inputString, $sharedSecret));
    }
}
