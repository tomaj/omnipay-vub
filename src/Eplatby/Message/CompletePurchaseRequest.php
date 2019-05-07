<?php

namespace Omnipay\Eplatby\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Eplatby\Sign\HmacSign;

class CompletePurchaseRequest extends AbstractRequest
{
    public function getData()
    {
        $sharedSecret = $this->getParameter('sharedSecret');

        $vs = $_GET['VS'];
        $ss = isset($_GET['SS']) ? $_GET['SS'] : null;
        $res = $_GET['RES'];

        $data = "{$vs}{$ss}{$res}";
        $sign = new HmacSign();

        if ($sign->sign($data, $sharedSecret) != $_GET['SIGN']) {
            throw new InvalidRequestException('incorect signature');
        }

        return [
            'RES' => $res,
            'VS' => $vs,
            'SS' => $ss
        ];
    }

    public function sendData($data)
    {
        return $this->response = new CompletePurchaseResponse($this, $data);
    }

    public function getSharedSecret()
    {
        return $this->getParameter('sharedSecret');
    }

    public function setSharedSecret($value)
    {
        return $this->setParameter('sharedSecret', $value);
    }
}
