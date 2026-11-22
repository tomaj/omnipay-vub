<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Tests;

use Omnipay\Common\Http\ClientInterface;
use Omnipay\Eplatby\Gateway;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

final class GatewayTest extends TestCase
{
    private Gateway $gateway;

    #[\Override]
    protected function setUp(): void
    {
        $this->gateway = new Gateway(self::createStub(ClientInterface::class), new HttpRequest());
    }

    public function testNameAndDefaults(): void
    {
        self::assertSame('VUB ePlatby Gateway', $this->gateway->getName());
        self::assertSame('Eplatby', $this->gateway->getShortName());

        $this->gateway->initialize();
        self::assertSame('', $this->gateway->getMid());
        self::assertSame('', $this->gateway->getSharedSecret());
        self::assertSame('', $this->gateway->getTestHost());
        self::assertNotTrue($this->gateway->getTestMode());
    }

    public function testParametersArePassedToPurchase(): void
    {
        $this->gateway
            ->setMid('11111111')
            ->setSharedSecret('1111111111111111111111111111111111111111111111111111111111111111')
            ->setTestHost('https://custom.example')
            ->setTestMode(true);

        $request = $this->gateway->purchase([
            'amount' => '10.00',
            'VS' => '123456',
            'CS' => '0321',
            'rurl' => 'http://localhost:4444/testserver.php',
        ]);
        self::assertSame('11111111', $request->getMid());
        self::assertSame('123456', $request->getVs());

        $response = $request->send();
        self::assertSame('https://custom.example/payment/eplatby-hmac', $response->getRedirectUrl());
        self::assertSame(
            'E227E97C520A502C45B77AEF4ABE5252A3F3065DB96C471C36FAD186FA50E62E',
            $response->getRedirectData()['SIGN'],
        );
    }

    public function testCompletePurchaseGetsSharedSecret(): void
    {
        $this->gateway->setSharedSecret('secret');

        $request = $this->gateway->completePurchase();
        self::assertSame('secret', $request->getSharedSecret());
    }

    public function testCompletePurchaseReadsTheGatewayHttpRequest(): void
    {
        $query = ['VS' => '123456', 'RES' => 'OK', 'SIGN' => 'E3883B183BD85936CC417562F97B0D619B692BBCED477E84989E4272A0E21BD4'];
        $gateway = new Gateway(self::createStub(ClientInterface::class), new HttpRequest($query));
        $gateway->setSharedSecret('1111111111111111111111111111111111111111111111111111111111111111');

        $response = $gateway->completePurchase()->send();

        self::assertTrue($response->isSuccessful());
        self::assertSame('123456', $response->getVs());
    }

    public function testRequestParametersOverrideGatewayParameters(): void
    {
        $this->gateway->setSharedSecret('gateway secret');

        self::assertSame('request secret', $this->gateway->completePurchase(['sharedSecret' => 'request secret'])->getSharedSecret());
        self::assertSame('request secret', $this->gateway->purchase(['sharedSecret' => 'request secret'])->getSharedSecret());
    }

    public function testIntegerMidIsReadAsString(): void
    {
        $this->gateway->initialize(['mid' => 11111111]);

        self::assertSame('11111111', $this->gateway->getMid());
    }
}
