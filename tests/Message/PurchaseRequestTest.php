<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Tests\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\Eplatby\Message\PurchaseRequest;
use Omnipay\Eplatby\Message\PurchaseResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request as HttpRequest;

final class PurchaseRequestTest extends TestCase
{
    private const string SECRET = '1111111111111111111111111111111111111111111111111111111111111111';

    /**
     * @param array<string, mixed> $parameters
     */
    private function request(array $parameters): PurchaseRequest
    {
        $request = new PurchaseRequest(self::createStub(ClientInterface::class), new HttpRequest());

        return $request->initialize($parameters + [
            'mid' => '11111111',
            'sharedSecret' => self::SECRET,
        ]);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function send(array $parameters): PurchaseResponse
    {
        return $this->request($parameters)->send();
    }

    public function testGetData(): void
    {
        $data = $this->request([
            'amount' => '10.00',
            'vs' => '123456',
            'cs' => '0321',
            'ss' => '77',
            'rem' => 'merchant@example.sk',
            'rsms' => '0900123456',
            'rurl' => 'http://localhost:4444/testserver.php',
        ])->getData();

        self::assertSame([
            'MID' => '11111111',
            'AMT' => '10.00',
            'VS' => '123456',
            'CS' => '0321',
            'RURL' => 'http://localhost:4444/testserver.php',
            'SS' => '77',
            'REM' => 'merchant@example.sk',
            'RSMS' => '0900123456',
        ], $data);
    }

    public function testSendSignsMidAmountVsCsRurl(): void
    {
        $response = $this->send([
            'amount' => '10.00',
            'vs' => '123456',
            'cs' => '0321',
            'ss' => '77',
            'rurl' => 'http://localhost:4444/testserver.php',
        ]);

        self::assertFalse($response->isSuccessful());
        self::assertTrue($response->isRedirect());
        self::assertSame('POST', $response->getRedirectMethod());

        $data = $response->getRedirectData();
        // HMAC-SHA256 of "11111111" . "10.00" . "123456" . "0321" . rurl, verified with the openssl CLI
        self::assertSame('E227E97C520A502C45B77AEF4ABE5252A3F3065DB96C471C36FAD186FA50E62E', $data['SIGN']);
        self::assertSame('77', $data['SS']);
    }

    public function testSignatureSkipsMissingOptionalCs(): void
    {
        $response = $this->send([
            'amount' => '0.01',
            'vs' => '12',
            'rurl' => 'https://example.sk/return?a=1&b=2',
        ]);

        $data = $response->getRedirectData();
        self::assertNull($data['CS']);
        self::assertSame('3E6E0304206EF9DDECCAD9B5A169F994D5EFAAF0DD7DC1A7E3F51177290F8B5D', $data['SIGN']);
    }

    public function testIntegerParametersAreAcceptedThroughInitialize(): void
    {
        $data = $this->send([
            'amount' => 10,
            'vs' => 123456,
            'cs' => '0321',
            'rurl' => 'http://localhost:4444/testserver.php',
        ])->getRedirectData();

        self::assertSame('123456', $data['VS']);
        self::assertSame('10.00', $data['AMT']);
        self::assertSame('E227E97C520A502C45B77AEF4ABE5252A3F3065DB96C471C36FAD186FA50E62E', $data['SIGN']);
    }

    public function testProductionEndpoint(): void
    {
        $request = $this->request(['testHost' => 'https://custom.example']);

        self::assertSame('https://ib.vub.sk/e-platbyeuro.aspx', $request->getEndpoint());
    }

    public function testDefaultTestEndpoint(): void
    {
        self::assertSame(
            'https://platby.tomaj.sk/payment/eplatby-hmac',
            $this->request(['testMode' => true])->getEndpoint(),
        );
        self::assertSame(
            'https://platby.tomaj.sk/payment/eplatby-hmac',
            $this->request(['testMode' => true, 'testHost' => ''])->getEndpoint(),
        );
    }

    public function testCustomTestHost(): void
    {
        $response = $this->send([
            'testMode' => true,
            'testHost' => 'https://custom.example',
            'amount' => '1.00',
            'vs' => '1',
            'rurl' => 'http://x',
        ]);

        self::assertSame('https://custom.example/payment/eplatby-hmac', $response->getRedirectUrl());
    }

    /**
     * @return iterable<array{string}>
     */
    public static function requiredParameters(): iterable
    {
        yield ['sharedSecret'];
        yield ['mid'];
        yield ['vs'];
        yield ['rurl'];
    }

    #[DataProvider('requiredParameters')]
    public function testRequiredParameter(string $parameter): void
    {
        $parameters = ['amount' => '1.00', 'vs' => '1', 'rurl' => 'http://x', $parameter => null];

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs("The {$parameter} parameter is required");

        $this->request($parameters)->getData();
    }

    public function testSendDataRejectsNonArray(): void
    {
        $this->expectException(InvalidRequestException::class);

        $this->request([])->sendData('nope');
    }
}
