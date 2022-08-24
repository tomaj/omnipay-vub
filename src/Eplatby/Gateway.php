<?php

namespace Omnipay\Eplatby;

use Omnipay\Common\AbstractGateway;

class Gateway extends AbstractGateway
{
    public function getName()
    {
        return 'VUB ePlatby Gateway';
    }

    public function getDefaultParameters()
    {
        return [
            'mid' => '',
            'sharedSecret' => '',
            'testHost' => '',
        ];
    }

    public function getMid()
    {
        return $this->getParameter('mid');
    }

    public function setMid($value)
    {
        return $this->setParameter('mid', $value);
    }

    public function getSharedSecret()
    {
        return $this->getParameter('sharedSecret');
    }

    public function setSharedSecret($value)
    {
        return $this->setParameter('sharedSecret', $value);
    }

    public function getTestHost()
    {
        return $this->getParameter('testHost');
    }

    public function setTestHost($value)
    {
        return $this->setParameter('testHost', $value);
    }

    public function purchase(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\Eplatby\Message\PurchaseRequest::class, $parameters);
    }

    public function completePurchase(array $parameters = array())
    {
        return $this->createRequest(\Omnipay\Eplatby\Message\CompletePurchaseRequest::class, $parameters);
    }
}
