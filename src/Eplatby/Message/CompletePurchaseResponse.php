<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Message;

use Omnipay\Common\Message\AbstractResponse;

final class CompletePurchaseResponse extends AbstractResponse
{
    public const string SUCCESS = 'OK';

    #[\Override]
    public function isSuccessful(): bool
    {
        return self::SUCCESS === $this->getRes();
    }

    public function getRes(): ?string
    {
        return $this->dataString('RES');
    }

    public function getVs(): ?string
    {
        return $this->dataString('VS');
    }

    /**
     * The specific symbol returned (and signed) by the bank; '' when the payment had none.
     */
    public function getSs(): ?string
    {
        return $this->dataString('SS');
    }

    private function dataString(string $key): ?string
    {
        if (is_array($this->data) && isset($this->data[$key]) && is_string($this->data[$key])) {
            return $this->data[$key];
        }

        return null;
    }
}
