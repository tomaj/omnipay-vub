<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Eplatby\Sign\HmacSign;
use Omnipay\Eplatby\StringParameterTrait;

class CompletePurchaseRequest extends AbstractRequest
{
    use StringParameterTrait;

    /**
     * @return array{RES: string, VS: string}
     */
    #[\Override]
    public function getData(): array
    {
        $vs = self::queryString('VS');
        $res = self::queryString('RES');
        $receivedSign = self::queryString('SIGN');

        $sign = new HmacSign();
        $expectedSign = $sign->sign($vs . $res, $this->getSharedSecret() ?? '');

        if (!hash_equals($expectedSign, $receivedSign)) {
            throw new InvalidRequestException('incorect signature');
        }

        return [
            'RES' => $res,
            'VS' => $vs,
        ];
    }

    #[\Override]
    public function sendData(mixed $data): CompletePurchaseResponse
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }

    public function getSharedSecret(): ?string
    {
        return $this->getStringParameter('sharedSecret');
    }

    public function setSharedSecret(?string $value): static
    {
        $this->setParameter('sharedSecret', $value);
        return $this;
    }

    private static function queryString(string $key): string
    {
        $value = $_GET[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
