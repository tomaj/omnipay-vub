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
            'testMode' => true, // plain http is accepted in test mode only
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
            'testMode' => true, // plain http is accepted in test mode only
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
                'vs' => '0000123456',
                'cs' => '0321',
                'ss' => $ss,
                'rurl' => 'http://localhost:4444/testserver.php',
                'testMode' => true, // plain http is accepted in test mode only
            ])->getRedirectData();

            // HMAC-SHA256 of "11111111" . "10.00" . "0000123456" . "0321" . rurl, verified with the openssl CLI
            self::assertSame('352B04E313B7B696D0119B753BB109D9972AD14CE3F77CD1AE37B659DBBC7451', $data['SIGN']);
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
            'rurl' => 'http://localhost:4444/testserver.php',
            'testMode' => true, // plain http is accepted in test mode only
        ])->getRedirectData();

        self::assertSame('123456', $data['VS']);
        self::assertSame('10.00', $data['AMT']);
        // HMAC-SHA256 of "11111111" . "10.00" . "123456" . rurl, verified with the openssl CLI
        self::assertSame('C3D677608AF2E2AEE458937B9BDB38D5B9985B969CA22FE6E4E7BE7F504002BB', $data['SIGN']);
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
            'rurl' => 'https://shop.example/return',
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
        $parameters = ['amount' => '1.00', 'vs' => '1', 'rurl' => 'https://shop.example/return', $parameter => null];

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

        $this->request(['amount' => '1.00', 'vs' => '1', 'rurl' => 'https://shop.example/return', 'sharedSecret' => $secret])->getData();
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

        $gateway->purchase(['amount' => '1.00', 'vs' => '1', 'rurl' => 'https://shop.example/return'])->send();
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function invalidSymbols(): iterable
    {
        $vsLength = 'The vs parameter must be exactly 10 digits when ss is set';
        // a malformed vs is rejected by the format check before the 10-digit rule for ss
        $vsFormat = 'The vs parameter must be up to 10 digits';
        yield 'short vs with ss' => ['123456', '77', $vsLength];
        yield '9 digit vs with ss' => ['000123456', '77', $vsLength];
        yield '11 digit vs with ss' => ['00001234567', '77', $vsFormat];
        yield 'vs with a space' => ['000012345 ', '77', $vsFormat];
        yield 'vs with a newline' => ["0000123456\n", '77', $vsFormat];
        yield 'non-digit vs' => ['00001234a6', '77', $vsFormat];
        yield 'non-digit ss' => ['0000123456', '7a', 'The ss parameter must be up to 10 digits'];
        yield '11 digit ss' => ['0000123456', '12345678901', 'The ss parameter must be up to 10 digits'];
        yield 'ss with a newline' => ['0000123456', "77\n", 'The ss parameter must be up to 10 digits'];
        yield 'ss with a leading letter' => ['0000123456', 'a77', 'The ss parameter must be up to 10 digits'];
        yield 'vs with a leading letter' => ['x0000123456', '77', $vsFormat];
    }

    #[DataProvider('invalidSymbols')]
    public function testSpecificSymbolRequiresTenDigitVariableSymbol(string $vs, string $ss, string $message): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs($message);

        $this->request(['amount' => '1.00', 'vs' => $vs, 'ss' => $ss, 'rurl' => 'https://shop.example/return'])->getData();
    }

    public function testVariableSymbolOfAnyLengthWithoutSpecificSymbol(): void
    {
        foreach ([null, ''] as $ss) {
            $data = $this->request(['amount' => '1.00', 'vs' => '1', 'ss' => $ss, 'rurl' => 'https://shop.example/return'])->getData();
            self::assertSame('1', $data['VS']);
        }
        $data = $this->request(['amount' => '1.00', 'vs' => '9999999999', 'ss' => '1234567890', 'rurl' => 'https://shop.example/return'])->getData();
        self::assertSame('1234567890', $data['SS']);
        $data = $this->request(['amount' => '1.00', 'vs' => '0000000001', 'ss' => '7', 'rurl' => 'https://shop.example/return'])->getData();
        self::assertSame('7', $data['SS']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidVariableSymbols(): iterable
    {
        yield 'empty' => [''];
        yield '11 digits' => ['12345678901'];
        yield 'letters' => ['abc'];
        yield 'letter and digits' => ['2024a00123'];
        yield 'inner space' => ['12 34'];
        yield 'leading space' => [' 1234'];
        yield 'trailing newline' => ["1234\n"];
        yield 'negative' => ['-1'];
        yield 'plus sign' => ['+1'];
        yield 'decimal' => ['1.5'];
        yield 'hex' => ['0x1A'];
        yield 'non-ASCII digits' => ['١٢٣'];
    }

    #[DataProvider('invalidVariableSymbols')]
    public function testInvalidVariableSymbolIsRejectedBeforeSigning(string $vs): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The vs parameter must be up to 10 digits');

        $this->send(['amount' => '1.00', 'vs' => $vs, 'rurl' => 'https://shop.example/return']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validVariableSymbols(): iterable
    {
        yield 'one digit' => ['1'];
        yield 'zero' => ['0'];
        yield '10 digits' => ['9999999999'];
        yield 'leading zeros' => ['0000000123'];
    }

    #[DataProvider('validVariableSymbols')]
    public function testValidVariableSymbol(string $vs): void
    {
        $data = $this->send(['amount' => '1.00', 'vs' => $vs, 'rurl' => 'https://shop.example/return'])->getRedirectData();

        self::assertSame($vs, $data['VS']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidConstantSymbols(): iterable
    {
        yield '5 digits' => ['03080'];
        yield 'letters' => ['abcd'];
        yield 'inner space' => ['03 8'];
        yield 'trailing newline' => ["0308\n"];
        yield 'leading space' => [' 308'];
        yield 'negative' => ['-308'];
        yield 'leading letter' => ['x308'];
        yield 'trailing letter' => ['308x'];
    }

    #[DataProvider('invalidConstantSymbols')]
    public function testInvalidConstantSymbolIsRejectedBeforeSigning(string $cs): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The cs parameter must be up to 4 digits');

        $this->send(['amount' => '1.00', 'vs' => '2024000123', 'cs' => $cs, 'rurl' => 'https://shop.example/return']);
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function validConstantSymbols(): iterable
    {
        yield 'not set' => [null];
        yield 'empty' => [''];
        yield 'one digit' => ['8'];
        yield '4 digits' => ['0308'];
    }

    #[DataProvider('validConstantSymbols')]
    public function testValidConstantSymbol(?string $cs): void
    {
        $data = $this->send(['amount' => '1.00', 'vs' => '2024000123', 'cs' => $cs, 'rurl' => 'https://shop.example/return'])->getRedirectData();

        self::assertSame($cs, $data['CS']);
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function shortVariableSymbolsWithConstantSymbol(): iterable
    {
        yield 'short vs' => ['123456', null];
        yield '9 digit vs' => ['000123456', null];
        yield 'short vs with ss' => ['123456', '77'];
    }

    /**
     * The request signs VS + SS + CS without a separator, so with a constant symbol the variable symbol has a fixed length.
     */
    #[DataProvider('shortVariableSymbolsWithConstantSymbol')]
    public function testConstantSymbolRequiresTenDigitVariableSymbol(string $vs, ?string $ss): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The vs parameter must be exactly 10 digits when cs is set');

        $this->send(['amount' => '1.00', 'vs' => $vs, 'ss' => $ss, 'cs' => '0308', 'rurl' => 'https://shop.example/return']);
    }

    public function testVariableSymbolOfAnyLengthWithoutConstantSymbol(): void
    {
        foreach ([null, ''] as $cs) {
            $data = $this->send(['amount' => '1.00', 'vs' => '123456', 'cs' => $cs, 'rurl' => 'https://shop.example/return'])->getRedirectData();
            self::assertSame('123456', $data['VS']);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidReturnUrls(): iterable
    {
        yield 'empty' => [''];
        yield 'relative path' => ['/return'];
        yield 'no scheme' => ['shop.example/return'];
        yield 'protocol-relative' => ['//shop.example/return'];
        yield 'scheme only' => ['https://'];
        yield 'no host' => ['https:///return'];
        yield 'opaque' => ['https:shop.example'];
        yield 'ftp' => ['ftp://shop.example/return'];
        yield 'javascript' => ['javascript:alert(1)'];
        yield 'https without slashes' => ['https:/shop.example/return'];
        yield 'space' => ['https://shop.example/re turn'];
        yield 'leading space' => [' https://shop.example/return'];
        yield 'trailing newline' => ["https://shop.example/return\n"];
        yield 'tab' => ["https://shop.example/\treturn"];
        yield 'NUL' => ["https://shop.example/\0return"];
        yield 'DEL' => ["https://shop.example/\x7Freturn"];
    }

    #[DataProvider('invalidReturnUrls')]
    public function testInvalidReturnUrlIsRejectedBeforeSigning(string $rurl): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The rurl parameter must be an absolute https:// URL');

        $this->send(['amount' => '1.00', 'vs' => '2024000123', 'rurl' => $rurl]);
    }

    #[DataProvider('invalidReturnUrls')]
    public function testInvalidReturnUrlIsRejectedInTestMode(string $rurl): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The rurl parameter must be an absolute https:// or http:// URL');

        $this->send(['amount' => '1.00', 'vs' => '2024000123', 'rurl' => $rurl, 'testMode' => true]);
    }

    public function testPlainHttpReturnUrlIsRejectedInProduction(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessageIs('The rurl parameter must be an absolute https:// URL');

        $this->send(['amount' => '1.00', 'vs' => '2024000123', 'rurl' => 'http://shop.example/return']);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function validReturnUrls(): iterable
    {
        yield 'https' => ['https://shop.example/return', false];
        yield 'https with query and fragment' => ['https://shop.example:8443/return?order=1&a=b#x', false];
        yield 'upper-case scheme' => ['HTTPS://shop.example/return', false];
        yield 'non-ASCII path' => ['https://obchod.example/návrat', false];
        yield 'https in test mode' => ['https://shop.example/return', true];
        yield 'http in test mode' => ['http://localhost:8080/return', true];
        yield 'upper-case http in test mode' => ['HTTP://127.0.0.1/return', true];
    }

    #[DataProvider('validReturnUrls')]
    public function testValidReturnUrl(string $rurl, bool $testMode): void
    {
        $data = $this->send(['amount' => '1.00', 'vs' => '2024000123', 'rurl' => $rurl, 'testMode' => $testMode])->getRedirectData();

        self::assertSame($rurl, $data['RURL']);
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
