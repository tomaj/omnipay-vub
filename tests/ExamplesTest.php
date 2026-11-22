<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Runs the examples that the documentation includes, so the snippets keep working.
 */
final class ExamplesTest extends TestCase
{
    /**
     * @param array<string, string> $query
     */
    private function runExample(string $example, array $query = []): string
    {
        $get = $_GET;
        $_GET = $query;
        ob_start();
        try {
            (static function (string $file): void {
                require $file;
            })(__DIR__ . '/../examples/' . $example);
        } finally {
            $output = (string) ob_get_clean();
            $_GET = $get;
            http_response_code(200);
        }

        return $output;
    }

    public function testPurchaseRendersTheSignedForm(): void
    {
        $output = $this->runExample('purchase.php');

        self::assertStringContainsString('action="https://platby.tomaj.sk/payment/eplatby-hmac"', $output);
        self::assertStringContainsString('name="VS" value="2024000123"', $output);
        // HMAC-SHA256 of "12345678" . "10.00" . "2024000123" . "0308" . rurl (openssl dgst -sha256 -hmac)
        self::assertStringContainsString('name="SIGN" value="C45FB95A0136DC2DF289920255B955FCB38CC03B777AA26D34393BF7F3926F68"', $output);
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function returns(): iterable
    {
        // signatures computed with openssl dgst -sha256 -hmac 1111...1
        yield 'paid' => [['VS' => '2024000123', 'RES' => 'OK', 'SIGN' => '6ADD04387C7A8787B51C1A5E48C546F66335BCF3DF18E93735A618470609C87F'], "Order 2024000123 paid, 10.00 EUR\n"];
        yield 'failed' => [['VS' => '2024000123', 'RES' => 'FAIL', 'SIGN' => '5FED9EBFEFE10FF3D5BFF4E32A2041F1DF8A1FD701683DCAE4846A6EFBD275D6'], "Order 2024000123 not paid: FAIL\n"];
        yield 'tampered' => [['VS' => '2024000123', 'RES' => 'OK', 'SIGN' => '5FED9EBFEFE10FF3D5BFF4E32A2041F1DF8A1FD701683DCAE4846A6EFBD275D6'], "Invalid return: incorect signature\n"];
        yield 'unexpected SS' => [['VS' => '2024000123', 'SS' => '1', 'RES' => 'OK', 'SIGN' => '6ADD04387C7A8787B51C1A5E48C546F66335BCF3DF18E93735A618470609C87F'], "Invalid return: incorect signature\n"];
        yield 'unknown order' => [['VS' => '999', 'RES' => 'OK', 'SIGN' => '6ADD04387C7A8787B51C1A5E48C546F66335BCF3DF18E93735A618470609C87F'], "Unknown order\n"];
    }

    /**
     * @param array<string, string> $query
     */
    #[DataProvider('returns')]
    public function testReturn(array $query, string $expected): void
    {
        self::assertSame($expected, $this->runExample('return.php', $query));
    }
}
