<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Tests\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Http\ClientInterface;
use Omnipay\Eplatby\Gateway;
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
            'vs' => '0000123456',
            'cs' => '0321',
            'ss' => '77',
            'rem' => 'merchant@example.sk',
            'rsms' => '0900123456',
            'rurl' => 'http://localhost:4444/testserver.php',
        ])->getData();

        self::assertSame([
            'MID' => '11111111',
            'AMT' => '10.00',
            'VS' => '0000123456',
            'CS' => '0321',
            'RURL' => 'http://localhost:4444/testserver.php',
            'SS' => '77',
            'REM' => 'merchant@example.sk',
            'RSMS' => '0900123456',
        ], $data);
    }

    public function testSendSignsMidAmountVsSsCsRurl(): void
    {
        $response = $this->send([
            'amount' => '10.00',
            'vs' => '0000123456',
            'cs' => '0321',
            'ss' => '77',
            'rurl' => 'http://localhost:4444/testserver.php',
        ]);

        self::assertFalse($response->isSuccessful());
        self::assertTrue($response->isRedirect());
        self::assertSame('POST', $response->getRedirectMethod());

        $data = $response->getRedirectData();
        // HMAC-SHA256 of "11111111" . "10.00" . "0000123456" . "77" . "0321" . rurl, verified with the openssl CLI
        self::assertSame('79F26AFA0F37FE430D16DED09C2F69CC228BD7C14B74F528D647EA2B803061FE', $data['SIGN']);
        self::assertSame('77', $data['SS']);
    }

    public function testSignatureWithoutSs(): void
    {
        foreach ([null, ''] as $ss) {
            $data = $this->send([
                'amount' => '10.00',
                'vs' => '123456',
                'cs' => '0321',
                'ss' => $ss,
                'rurl' => 'http://localhost:4444/testserver.php',
            ])->getRedirectData();

            // HMAC-SHA256 of "11111111" . "10.00" . "123456" . "0321" . rurl, verified with the openssl CLI
            self::assertSame('E227E97C520A502C45B77AEF4ABE5252A3F3065DB96C471C36FAD186FA50E62E', $data['SIGN']);
        }
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
        yield ['amount'];
    }

    #[DataProvider('requiredParameters')]
    public function testRequiredParameter(string $parameter): void
    {
        $parameters = ['amount' => '1.00', 'vs' => '1', 'rurl' => 'http://x', $parameter => null];

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs("The {$parameter} parameter is required");

        $this->request($parameters)->getData();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function emptySecrets(): iterable
    {
        yield 'empty' => [''];
        yield 'spaces' => ['   '];
        yield 'newline' => ["\n"];
    }

    #[DataProvider('emptySecrets')]
    public function testEmptySharedSecretThrows(string $secret): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The sharedSecret parameter is required');

        $this->request(['amount' => '1.00', 'vs' => '1', 'rurl' => 'http://x', 'sharedSecret' => $secret])->getData();
    }

    #[DataProvider('emptySecrets')]
    public function testEmptySharedSecretIsNotUsedForSigning(string $secret): void
    {
        $request = $this->request(['sharedSecret' => $secret]);

        try {
            $request->generateSignature('data');
            self::fail('generateSignature() signed with an empty secret');
        } catch (InvalidRequestException $e) {
            self::assertSame('The sharedSecret parameter is required', $e->getMessage());
        }

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The sharedSecret parameter is required');

        $request->sendData(['MID' => '11111111']);
    }

    public function testDefaultSharedSecretOfTheGatewayIsRejected(): void
    {
        $gateway = new Gateway(self::createStub(ClientInterface::class), new HttpRequest());
        $gateway->setMid('11111111');

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The sharedSecret parameter is required');

        $gateway->purchase(['amount' => '1.00', 'vs' => '1', 'rurl' => 'http://x'])->send();
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function invalidSymbols(): iterable
    {
        $vsLength = 'The vs parameter must be exactly 10 digits when ss is set';
        yield 'short vs with ss' => ['123456', '77', $vsLength];
        yield '11 digit vs with ss' => ['00001234567', '77', $vsLength];
        yield 'vs with a space' => ['000012345 ', '77', $vsLength];
        yield 'vs with a newline' => ["0000123456\n", '77', $vsLength];
        yield 'non-digit vs' => ['00001234a6', '77', $vsLength];
        yield 'non-digit ss' => ['0000123456', '7a', 'The ss parameter must be up to 10 digits'];
        yield '11 digit ss' => ['0000123456', '12345678901', 'The ss parameter must be up to 10 digits'];
        yield 'ss with a newline' => ['0000123456', "77\n", 'The ss parameter must be up to 10 digits'];
        yield 'ss with a leading letter' => ['0000123456', 'a77', 'The ss parameter must be up to 10 digits'];
        yield 'vs with a leading letter' => ['x0000123456', '77', $vsLength];
    }

    #[DataProvider('invalidSymbols')]
    public function testSpecificSymbolRequiresTenDigitVariableSymbol(string $vs, string $ss, string $message): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs($message);

        $this->request(['amount' => '1.00', 'vs' => $vs, 'ss' => $ss, 'rurl' => 'http://x'])->getData();
    }

    public function testVariableSymbolOfAnyLengthWithoutSpecificSymbol(): void
    {
        foreach ([null, ''] as $ss) {
            $data = $this->request(['amount' => '1.00', 'vs' => '1', 'ss' => $ss, 'rurl' => 'http://x'])->getData();
            self::assertSame('1', $data['VS']);
        }
        $data = $this->request(['amount' => '1.00', 'vs' => '9999999999', 'ss' => '1234567890', 'rurl' => 'http://x'])->getData();
        self::assertSame('1234567890', $data['SS']);
        $data = $this->request(['amount' => '1.00', 'vs' => '0000000001', 'ss' => '7', 'rurl' => 'http://x'])->getData();
        self::assertSame('7', $data['SS']);
    }

    public function testAccessors(): void
    {
        $request = $this->request([]);
        $request->setMid('m')->setSharedSecret('s')->setTestHost('h')->setVs('v')->setCs('c')
            ->setSs('ss')->setRsms('r')->setRem('e')->setRurl('u');

        self::assertSame('m', $request->getMid());
        self::assertSame('s', $request->getSharedSecret());
        self::assertSame('h', $request->getTestHost());
        self::assertSame('v', $request->getVs());
        self::assertSame('c', $request->getCs());
        self::assertSame('ss', $request->getSs());
        self::assertSame('r', $request->getRsms());
        self::assertSame('e', $request->getRem());
        self::assertSame('u', $request->getRurl());

        $request->setSs(null);
        self::assertNull($request->getSs());
    }

    public function testGenerateSignature(): void
    {
        // printf '%s' 'abc' | openssl dgst -sha256 -hmac <SECRET>
        self::assertSame('09C24226F71A400C66F61065D815D97F4A8C07055426B277031136EFAE34D63D', $this->request([])->generateSignature('abc'));
    }

    public function testSendDataRejectsNonArray(): void
    {
        $this->expectException(InvalidRequestException::class);

        $this->request([])->sendData('nope');
    }
}
