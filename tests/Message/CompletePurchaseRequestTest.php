<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Tests\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\Eplatby\Gateway;
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
    private const string SIGN_SS_OK = 'C2C5788EC4165F6A1EB5F13FE66A527D1448433009182AD87A2EA52C9C21374E';

    /**
     * @param array<string, mixed> $query
     */
    private function send(array $query, string $secret = self::SECRET, ?string $expectedVs = '123456', ?string $expectedSs = null): CompletePurchaseResponse
    {
        $request = new CompletePurchaseRequest(self::createStub(ClientInterface::class), new HttpRequest($query));
        $request->initialize(['sharedSecret' => $secret, 'vs' => $expectedVs, 'ss' => $expectedSs]);

        return $request->sendData($request->getData());
    }

    public function testSuccessfulPayment(): void
    {
        $response = $this->send(['VS' => '123456', 'RES' => 'OK', 'SIGN' => self::SIGN_OK]);

        self::assertTrue($response->isSuccessful());
        self::assertFalse($response->isRedirect());
        self::assertSame('OK', $response->getRes());
        self::assertSame('123456', $response->getVs());
        self::assertSame('', $response->getSs());
        self::assertSame(['RES' => 'OK', 'VS' => '123456', 'SS' => ''], $response->getData());
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

    public function testValidReturnForAnotherOrderThrows(): void
    {
        // a genuine, correctly signed success return of the order with VS 111 (openssl dgst -sha256 -hmac)
        $query = ['VS' => '111', 'RES' => 'OK', 'SIGN' => '4C8AA6626570495FE62F9A4C84BBFD5829C40B8735809A990DE152967955B76A'];

        self::assertTrue($this->send($query, expectedVs: '111')->isSuccessful());

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('Variable symbol mismatch');

        $this->send($query, expectedVs: '222');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function differentVariableSymbols(): iterable
    {
        yield 'leading zero' => ['0123456'];
        yield 'trailing space' => ['123456 '];
        yield 'prefix' => ['12345'];
    }

    #[DataProvider('differentVariableSymbols')]
    public function testVariableSymbolIsComparedExactly(string $expectedVs): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('Variable symbol mismatch');

        $this->send(['VS' => '123456', 'RES' => 'OK', 'SIGN' => self::SIGN_OK], expectedVs: $expectedVs);
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function missingVariableSymbols(): iterable
    {
        yield 'null' => [null];
        yield 'empty' => [''];
    }

    #[DataProvider('missingVariableSymbols')]
    public function testExpectedVariableSymbolIsRequired(?string $expectedVs): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The vs parameter is required');

        $this->send(['VS' => '', 'RES' => 'OK', 'SIGN' => self::SIGN_OK], expectedVs: $expectedVs);
    }

    public function testMismatchedVariableSymbolOfFailedPaymentThrows(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('Variable symbol mismatch');

        $this->send(['VS' => '123456', 'RES' => 'FAIL', 'SIGN' => self::SIGN_FAIL], expectedVs: '654321');
    }

    public function testSpecificSymbolIsSigned(): void
    {
        // HMAC-SHA256 of "0000123456" . "77" . "OK" / "FAIL", verified with the openssl CLI
        $response = $this->send(['VS' => '0000123456', 'SS' => '77', 'RES' => 'OK', 'SIGN' => self::SIGN_SS_OK], expectedVs: '0000123456', expectedSs: '77');

        self::assertTrue($response->isSuccessful());
        self::assertSame('77', $response->getSs());
        self::assertSame(['RES' => 'OK', 'VS' => '0000123456', 'SS' => '77'], $response->getData());

        $response = $this->send(['VS' => '0000123456', 'SS' => '77', 'RES' => 'FAIL', 'SIGN' => 'DEB8E9F6B107A6E88F60F95F1A6AB4A6869F212336728E6360655A2016A259CF'], expectedVs: '0000123456', expectedSs: '77');
        self::assertFalse($response->isSuccessful());
    }

    public function testSpecificSymbolOutsideTheSignatureThrows(): void
    {
        // SIGN covers "0000123456" . "OK" only, so an added SS breaks it (openssl CLI)
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('incorect signature');

        $this->send(['VS' => '0000123456', 'SS' => '77', 'RES' => 'OK', 'SIGN' => 'CB690773E8028A21F40A3A49AEA6F7E8C0ED11A34737168ACFAD74B6669A870A'], expectedVs: '0000123456', expectedSs: '77');
    }

    public function testUnexpectedSpecificSymbolThrows(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('Specific symbol mismatch');

        $this->send(['VS' => '123456', 'SS' => '77', 'RES' => 'OK', 'SIGN' => '129A0AB7D7683C36DC1F9D60EBB97F5814F91A79D40CADA0C3D08C1C763DD065']);
    }

    public function testMissingExpectedSpecificSymbolThrows(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('Specific symbol mismatch');

        $this->send(['VS' => '0000123456', 'RES' => 'OK', 'SIGN' => 'CB690773E8028A21F40A3A49AEA6F7E8C0ED11A34737168ACFAD74B6669A870A'], expectedVs: '0000123456', expectedSs: '77');
    }

    public function testDigitsCannotMoveBetweenVsAndSs(): void
    {
        // "12345" . "6" . "OK" is the same signed string as "123456" . "" . "OK"
        $query = ['VS' => '12345', 'SS' => '6', 'RES' => 'OK', 'SIGN' => self::SIGN_OK];

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('Specific symbol mismatch');

        $this->send($query, expectedVs: '12345');
    }

    public function testDigitsCannotMoveFromTenDigitVsToSs(): void
    {
        // "000012345" . "677" . "OK" is the same signed string as "0000123456" . "77" . "OK"
        $query = ['VS' => '000012345', 'SS' => '677', 'RES' => 'OK', 'SIGN' => self::SIGN_SS_OK];

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('Variable symbol mismatch');

        $this->send($query, expectedVs: '0000123456', expectedSs: '77');
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function invalidExpectedSymbols(): iterable
    {
        $vsLength = 'The vs parameter must be exactly 10 digits when ss is set';
        yield 'short vs with ss' => ['123456', '77', $vsLength];
        yield '11 digit vs with ss' => ['01234567890', '77', $vsLength];
        yield 'non-digit ss' => ['0000123456', '7 ', 'The ss parameter must be up to 10 digits'];
    }

    #[DataProvider('invalidExpectedSymbols')]
    public function testSpecificSymbolRequiresTenDigitVariableSymbol(string $expectedVs, string $expectedSs, string $message): void
    {
        // a correctly signed return does not help: the order's own symbols are not unambiguous
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs($message);

        $this->send(['VS' => '123456', 'SS' => '77', 'RES' => 'OK', 'SIGN' => '129A0AB7D7683C36DC1F9D60EBB97F5814F91A79D40CADA0C3D08C1C763DD065'], expectedVs: $expectedVs, expectedSs: $expectedSs);
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function emptySecrets(): iterable
    {
        yield 'null' => [null];
        yield 'empty' => [''];
        yield 'spaces' => ['  '];
        yield 'tab and newline' => ["\t\n"];
    }

    #[DataProvider('emptySecrets')]
    public function testEmptySharedSecretThrows(?string $secret): void
    {
        // HMAC-SHA256 of "123456OK" with an empty key (openssl dgst -sha256 -hmac ''): a return anyone can sign
        $query = ['VS' => '123456', 'RES' => 'OK', 'SIGN' => '88D9C286AD0A642E7172B768F831894F68C488804908D158D98AF630C20ED6F1'];
        $request = new CompletePurchaseRequest(self::createStub(ClientInterface::class), new HttpRequest($query));
        $request->initialize(['sharedSecret' => $secret, 'vs' => '123456']);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The sharedSecret parameter is required');

        $request->getData();
    }

    public function testDefaultSharedSecretOfTheGatewayIsRejected(): void
    {
        $query = ['VS' => '123456', 'RES' => 'OK', 'SIGN' => '88D9C286AD0A642E7172B768F831894F68C488804908D158D98AF630C20ED6F1'];
        $gateway = new Gateway(self::createStub(ClientInterface::class), new HttpRequest($query));

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The sharedSecret parameter is required');

        $gateway->completePurchase(['vs' => '123456'])->send();
    }

    public function testWrongSecretThrows(): void
    {
        $this->expectException(InvalidRequestException::class);

        $this->send(['VS' => '123456', 'RES' => 'OK', 'SIGN' => self::SIGN_OK], 'another secret');
    }

    public function testAccessors(): void
    {
        $request = new CompletePurchaseRequest(self::createStub(ClientInterface::class), new HttpRequest());
        $request->initialize(['sharedSecret' => 'secret', 'vs' => '1', 'ss' => '2']);

        self::assertSame('secret', $request->getSharedSecret());
        self::assertSame('1', $request->getVs());
        self::assertSame('2', $request->getSs());
    }

    public function testResponseWithNonStringData(): void
    {
        $request = new CompletePurchaseRequest(self::createStub(ClientInterface::class), new HttpRequest());
        $response = new CompletePurchaseResponse($request, ['RES' => ['OK'], 'VS' => 123456]);

        self::assertFalse($response->isSuccessful());
        self::assertNull($response->getRes());
        self::assertNull($response->getVs());
        self::assertNull($response->getSs());
    }

    public function testResponseWithoutData(): void
    {
        $request = new CompletePurchaseRequest(self::createStub(ClientInterface::class), new HttpRequest());
        $response = new CompletePurchaseResponse($request, null);

        self::assertFalse($response->isSuccessful());
        self::assertNull($response->getRes());
        self::assertNull($response->getVs());
        self::assertNull($response->getSs());
    }
}
