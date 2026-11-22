<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Eplatby\Sign\HmacSign;
use Omnipay\Eplatby\StringParameterTrait;

final class CompletePurchaseRequest extends AbstractRequest
{
    use StringParameterTrait;

    /**
     * @return array{RES: string, VS: string}
     */
    #[\Override]
    public function getData(): array
    {
        $vs = $this->queryString('VS');
        $res = $this->queryString('RES');
        $receivedSign = $this->queryString('SIGN');

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
    public function send(): CompletePurchaseResponse
    {
        return $this->sendData($this->getData());
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

    /**
     * A query parameter of the bank's return request; '' when it is missing or not a string.
     */
    private function queryString(string $key): string
    {
        $value = $this->httpRequest->query->all()[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
