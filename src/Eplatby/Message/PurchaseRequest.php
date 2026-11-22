<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Eplatby\Sign\HmacSign;
use Omnipay\Eplatby\StringParameterTrait;

final class PurchaseRequest extends AbstractRequest
{
    use StringParameterTrait;

    /**
     * @return array<string, ?string>
     */
    #[\Override]
    public function getData(): array
    {
        $this->validate('sharedSecret', 'mid', 'vs', 'rurl');
        $data = [];

        $data['MID'] = $this->getMid();
        $data['AMT'] = $this->getAmount();
        $data['VS'] = $this->getVs();
        $data['CS'] = $this->getCs();
        $data['RURL'] = $this->getRurl();
        $data['SS'] = $this->getSs();
        $data['REM'] = $this->getRem();
        $data['RSMS'] = $this->getRsms();

        return $data;
    }

    public function generateSignature(string $data): string
    {
        $sign = new HmacSign();
        return $sign->sign($data, $this->getSharedSecret() ?? '');
    }

    #[\Override]
    public function send(): PurchaseResponse
    {
        return $this->sendData($this->getData());
    }

    #[\Override]
    public function sendData(mixed $data): PurchaseResponse
    {
        if (!is_array($data)) {
            throw new InvalidRequestException('Purchase data must be an array');
        }

        // VÚB e-platby HMAC: MID + AMT + VS + SS + CS + RURL (an empty SS or CS adds nothing)
        $input = $this->getMid() . $this->getAmount() . $this->getVs() . $this->getSs() . $this->getCs() . $this->getRurl();
        $data['SIGN'] = $this->generateSignature($input);

        return $this->response = new PurchaseResponse($this, $data);
    }

    public function getEndpoint(): string
    {
        if ($this->getTestMode()) {
            $host = $this->getTestHost();
            if ($host === null || $host === '') {
                $host = 'https://platby.tomaj.sk';
            }
            return $host . '/payment/eplatby-hmac';
        }

        return 'https://ib.vub.sk/e-platbyeuro.aspx';
    }

    public function getMid(): ?string
    {
        return $this->getStringParameter('mid');
    }

    public function setMid(?string $value): static
    {
        $this->setParameter('mid', $value);
        return $this;
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

    public function getTestHost(): ?string
    {
        return $this->getStringParameter('testHost');
    }

    public function setTestHost(?string $value): static
    {
        $this->setParameter('testHost', $value);
        return $this;
    }

    public function getVs(): ?string
    {
        return $this->getStringParameter('vs');
    }

    public function setVs(?string $value): static
    {
        $this->setParameter('vs', $value);
        return $this;
    }

    public function getCs(): ?string
    {
        return $this->getStringParameter('cs');
    }

    public function setCs(?string $value): static
    {
        $this->setParameter('cs', $value);
        return $this;
    }

    public function getSs(): ?string
    {
        return $this->getStringParameter('ss');
    }

    public function setSs(?string $value): static
    {
        $this->setParameter('ss', $value);
        return $this;
    }

    public function getRsms(): ?string
    {
        return $this->getStringParameter('rsms');
    }

    public function setRsms(?string $value): static
    {
        $this->setParameter('rsms', $value);
        return $this;
    }

    public function getRem(): ?string
    {
        return $this->getStringParameter('rem');
    }

    public function setRem(?string $value): static
    {
        $this->setParameter('rem', $value);
        return $this;
    }

    public function getRurl(): ?string
    {
        return $this->getStringParameter('rurl');
    }

    public function setRurl(?string $value): static
    {
        $this->setParameter('rurl', $value);
        return $this;
    }
}
