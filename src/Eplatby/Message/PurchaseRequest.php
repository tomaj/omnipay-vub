<?php

namespace Omnipay\Eplatby\Message;

use Omnipay\Common\Currency;
use Omnipay\Eplatby\Sign\HmacSign;
use Omnipay\Common\Message\AbstractRequest;

class PurchaseRequest extends AbstractRequest
{
    public function initialize(array $parameters = array())
    {
        parent::initialize($parameters);
        return $this;
    }

    public function getData()
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

    public function generateSignature($data)
    {
        $sign = new HmacSign();
        return $sign->sign($data, $this->getParameter('sharedSecret'));
    }

    public function sendData($data)
    {
        $sharedSecret = $this->getParameter('sharedSecret');

        $input = "{$this->getMid()}{$this->getAmount()}{$this->getVs()}{$this->getCs()}{$this->getRurl()}";
        $data['SIGN'] = $this->generateSignature($input);

        return $this->response = new PurchaseResponse($this, $data);
    }

    public function getEndpoint()
    {
        $sharedSecret = $this->getParameter('sharedSecret');

        if ($this->getTestmode()) {
            return 'https://platby.tomaj.sk/payment/eplatby-hmac';
        } else {
            // return 'https://nib.vub.sk/epay/merchant'; // vub test server
            return 'https://ib.vub.sk/e-platbyeuro.aspx';
        }
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

    public function getVs()
    {
        return $this->getParameter('vs');
    }

    public function setVs($value)
    {
        return $this->setParameter('vs', $value);
    }

    public function getCs()
    {
        return $this->getParameter('cs');
    }

    public function setCs($value)
    {
        return $this->setParameter('cs', $value);
    }

    public function getSs()
    {
        return $this->getParameter('ss');
    }

    public function setSs($value)
    {
        return $this->setParameter('ss', $value);
    }

    public function getRsms()
    {
        return $this->getParameter('rsms');
    }

    public function setRsms($value)
    {
        return $this->setParameter('rsms', $value);
    }

    public function getRem()
    {
        return $this->getParameter('rem');
    }

    public function setRem($value)
    {
        return $this->setParameter('rem', $value);
    }

    public function getRurl()
    {
        return $this->getParameter('rurl');
    }
    
    public function setRurl($value)
    {
        return $this->setParameter('rurl', $value);
    }
}