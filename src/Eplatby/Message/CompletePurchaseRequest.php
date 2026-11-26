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
     * Verifies the bank's return request and that it belongs to the expected order.
     *
     * The bank signs only VS + SS + RES (variable symbol, specific symbol, result), not the amount: load the
     * order by its variable symbol and take the amount from your own records.
     *
     * @return array{RES: string, VS: string, SS: string}
     * @throws InvalidRequestException when the shared secret is empty, the vs parameter is missing (or not
     *                                 10 digits while ss is set), the signature is invalid, or the returned
     *                                 VS or SS is not the expected one
     */
    #[\Override]
    public function getData(): array
    {
        $sharedSecret = ParameterGuard::sharedSecret($this->getSharedSecret());
        $expectedVs = $this->getVs();
        if ($expectedVs === null || $expectedVs === '') {
            throw new InvalidRequestException('The vs parameter is required');
        }
        ParameterGuard::variableSymbolWithSpecificSymbol($expectedVs, $this->getSs());

        $vs = $this->queryString('VS');
        $ss = $this->queryString('SS');
        $res = $this->queryString('RES');
        $receivedSign = $this->queryString('SIGN');

        $sign = new HmacSign();
        $expectedSign = $sign->sign($vs . $ss . $res, $sharedSecret);

        if (!hash_equals($expectedSign, $receivedSign)) {
            throw new InvalidRequestException('incorect signature');
        }

        if ($vs !== $expectedVs) {
            throw new InvalidRequestException('Variable symbol mismatch');
        }

        // VS and SS are concatenated without a separator, so SS is pinned too: otherwise digits
        // could move between them ("12345" + "6" signs the same as "123456" + ""). With an SS, the
        // expected VS has 10 digits (checked above), so another order's VS + SS cannot sign the same string
        if ($ss !== ($this->getSs() ?? '')) {
            throw new InvalidRequestException('Specific symbol mismatch');
        }

        return [
            'RES' => $res,
            'VS' => $vs,
            'SS' => $ss,
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

    public function setSharedSecret(#[\SensitiveParameter] ?string $value): static
    {
        $this->setParameter('sharedSecret', $value);
        return $this;
    }

    /**
     * The variable symbol of the order being completed; the returned VS must be equal to it.
     */
    public function getVs(): ?string
    {
        return $this->getStringParameter('vs');
    }

    public function setVs(?string $value): static
    {
        $this->setParameter('vs', $value);
        return $this;
    }

    /**
     * The specific symbol sent in purchase(), if any; the returned SS must be equal to it ('' when null).
     */
    public function getSs(): ?string
    {
        return $this->getStringParameter('ss');
    }

    public function setSs(?string $value): static
    {
        $this->setParameter('ss', $value);
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
