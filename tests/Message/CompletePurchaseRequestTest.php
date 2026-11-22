<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Tests\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\Eplatby\Message\CompletePurchaseRequest;
use Omnipay\Eplatby\Message\CompletePurchaseResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

final class CompletePurchaseRequestTest extends TestCase
{
    private const string SECRET = '1111111111111111111111111111111111111111111111111111111111111111';

    // HMAC-SHA256 values verified with the openssl CLI
    private const string SIGN_OK = 'E3883B183BD85936CC417562F97B0D619B692BBCED477E84989E4272A0E21BD4';
    private const string SIGN_FAIL = 'B090D9AFE61306B9C060AEF7340BB1DE6CA1641661625AC3E705C96AE9AB8179';
    private const string SIGN_TOUT = '246F3F79A7F5B235D2A0E8A6020EB65110B78F052C1ADD51843B2E06C4F8E78C';

    /** @var array<mixed> */
    private array $originalGet;

    #[\Override]
    protected function setUp(): void
    {
        $this->originalGet = $_GET;
    }

    #[\Override]
    protected function tearDown(): void
    {
        $_GET = $this->originalGet;
    }

    /**
     * @param array<string, mixed> $query
     */
    private function send(array $query, string $secret = self::SECRET): CompletePurchaseResponse
    {
        $_GET = $query;
        $request = new CompletePurchaseRequest(self::createStub(ClientInterface::class), new HttpRequest());
        $request->initialize(['sharedSecret' => $secret]);

        return $request->sendData($request->getData());
    }

    public function testSuccessfulPayment(): void
    {
        $response = $this->send(['VS' => '123456', 'RES' => 'OK', 'SIGN' => self::SIGN_OK]);

        self::assertTrue($response->isSuccessful());
        self::assertFalse($response->isRedirect());
        self::assertSame('OK', $response->getRes());
        self::assertSame('123456', $response->getVs());
        self::assertSame(['RES' => 'OK', 'VS' => '123456'], $response->getData());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function failedResults(): iterable
    {
        yield 'FAIL' => ['FAIL', self::SIGN_FAIL];
        yield 'TOUT' => ['TOUT', self::SIGN_TOUT];
    }

    #[DataProvider('failedResults')]
    public function testSignedFailedPayment(string $res, string $sign): void
    {
        $response = $this->send(['VS' => '123456', 'RES' => $res, 'SIGN' => $sign]);

        self::assertFalse($response->isSuccessful());
        self::assertSame($res, $response->getRes());
        self::assertSame('123456', $response->getVs());
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidSignatures(): iterable
    {
        yield 'tampered VS' => [['VS' => '123457', 'RES' => 'OK', 'SIGN' => self::SIGN_OK]];
        yield 'tampered RES' => [['VS' => '123456', 'RES' => 'OK', 'SIGN' => self::SIGN_FAIL]];
        yield 'lower-case SIGN' => [['VS' => '123456', 'RES' => 'OK', 'SIGN' => strtolower(self::SIGN_OK)]];
        yield 'SIGN with trailing space' => [['VS' => '123456', 'RES' => 'OK', 'SIGN' => self::SIGN_OK . ' ']];
        yield 'truncated SIGN' => [['VS' => '123456', 'RES' => 'OK', 'SIGN' => substr(self::SIGN_OK, 0, 63)]];
        yield 'empty SIGN' => [['VS' => '123456', 'RES' => 'OK', 'SIGN' => '']];
        yield 'missing SIGN' => [['VS' => '123456', 'RES' => 'OK']];
        yield 'array SIGN' => [['VS' => '123456', 'RES' => 'OK', 'SIGN' => [self::SIGN_OK]]];
        yield 'no query' => [[]];
    }

    /**
     * @param array<string, mixed> $query
     */
    #[DataProvider('invalidSignatures')]
    public function testInvalidSignatureThrows(array $query): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('incorect signature');

        $this->send($query);
    }

    public function testWrongSecretThrows(): void
    {
        $this->expectException(InvalidRequestException::class);

        $this->send(['VS' => '123456', 'RES' => 'OK', 'SIGN' => self::SIGN_OK], 'another secret');
    }

    public function testResponseWithoutData(): void
    {
        $request = new CompletePurchaseRequest(self::createStub(ClientInterface::class), new HttpRequest());
        $response = new CompletePurchaseResponse($request, null);

        self::assertFalse($response->isSuccessful());
        self::assertNull($response->getRes());
        self::assertNull($response->getVs());
    }
}
