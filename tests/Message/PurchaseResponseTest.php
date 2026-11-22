<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Tests\Message;

use Omnipay\Common\Exception\OmnipayException;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\Eplatby\Exception\EplatbyException;
use Omnipay\Eplatby\Exception\LogicException;
use Omnipay\Eplatby\Message\CompletePurchaseRequest;
use Omnipay\Eplatby\Message\PurchaseRequest;
use Omnipay\Eplatby\Message\PurchaseResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

final class PurchaseResponseTest extends TestCase
{
    public function testRedirectUrlNeedsAPurchaseRequest(): void
    {
        $response = new PurchaseResponse(
            new CompletePurchaseRequest(self::createStub(ClientInterface::class), new HttpRequest()),
            [],
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIs('PurchaseResponse requires a PurchaseRequest');

        $response->getRedirectUrl();
    }

    public function testPackageExceptionHierarchy(): void
    {
        self::assertContains(EplatbyException::class, class_implements(LogicException::class));
        self::assertContains(OmnipayException::class, class_implements(LogicException::class));
        self::assertContains(\LogicException::class, class_parents(LogicException::class));
    }

    public function testRedirectDataWithoutArray(): void
    {
        $request = new PurchaseRequest(self::createStub(ClientInterface::class), new HttpRequest());
        $response = new PurchaseResponse($request, null);

        self::assertSame([], $response->getRedirectData());
        self::assertSame('POST', $response->getRedirectMethod());
        self::assertTrue($response->isRedirect());
        self::assertFalse($response->isSuccessful());
        self::assertSame('https://ib.vub.sk/e-platbyeuro.aspx', $response->getRedirectUrl());
    }
}
