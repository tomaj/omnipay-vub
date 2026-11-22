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
        $value = is_array($this->data) ? $this->data[$key] ?? null : null;

        return is_string($value) ? $value : null;
    }
}
