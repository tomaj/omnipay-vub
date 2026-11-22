<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Tests\Sign;

use Omnipay\Eplatby\Sign\HmacSign;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HmacSignTest extends TestCase
{
    /**
     * Expected values were computed independently with
     * `printf '%s' "$input" | openssl dgst -sha256 -hmac "$secret"` (upper-cased).
     *
     * @return iterable<string, array{string, string, string}>
     */
    public static function vectors(): iterable
    {
        $secret = str_repeat('1', 64);

        yield 'purchase' => [
            '1111111110.001234560321http://localhost:4444/testserver.php',
            $secret,
            'E227E97C520A502C45B77AEF4ABE5252A3F3065DB96C471C36FAD186FA50E62E',
        ];
        yield 'complete ok' => ['123456OK', $secret, 'E3883B183BD85936CC417562F97B0D619B692BBCED477E84989E4272A0E21BD4'];
        yield 'base64-like secret' => [
            '123456OK',
            '36fmtvHtjyRAieZ9k+x9zldZIYhSx/vh22iaBG9n1aOd1ncFKrUCeR7gAE5Dg2D1',
            'F3FF413684B9F5E732751FEEF2F3DEF0F77BE4716E59DE7F2C811FD6F78FE038',
        ];
        yield 'trailing spaces are kept' => ['data ', 'sec ret  ', 'C26D372D1392BCAD5430399824AC2A20927C32D8F393928FB50F60E8B5FEAD6D'];
        yield 'utf-8 bytes' => ['žluťoučký kůň', 'šľťžý', '48D092323695259DB05DF85C582F17A90AE945E61D11E99F0385CD9D53D2211F'];
    }

    #[DataProvider('vectors')]
    public function testSign(string $input, string $secret, string $expected): void
    {
        self::assertSame($expected, (new HmacSign())->sign($input, $secret));
    }
}
