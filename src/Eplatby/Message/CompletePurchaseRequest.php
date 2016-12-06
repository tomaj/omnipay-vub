<?php

namespace Omnipay\Eplatby\Message;

use Omnipay\Common\Currency;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Core\Message\AbstractRequest;
use Omnipay\Eplatby\Sign\CompletePurchaseRequest;

class CompletePurchaseRequest extends AbstractRequest
{
    public function getData()
    {
        $sharedSecret = $this->getParameter('sharedSecret');

        $data = "{$_GET['VS']}{$_GET['RES']}";
    	$sign = new HmacSign();

        if ($sign->sign($data, $sharedSecret) != $_GET['SIGN']) {
            throw new InvalidRequestException('incorect signature');
        }

        return [
            'RES' => $_GET['RES'],
            'VS' => $_GET['VS'],
        ];
    }

    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }
}