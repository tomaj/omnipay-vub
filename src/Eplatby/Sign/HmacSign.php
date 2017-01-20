<?php

namespace Omnipay\Eplatby\Sign;

class HmacSign
{
    public function sign($input, $secret)
    {
        $inputString = pack('A*', $input);
        $sharedSecret = pack('A*', $secret);
        return strtoupper(hash_hmac('sha256', $inputString, $sharedSecret));
    }
}
