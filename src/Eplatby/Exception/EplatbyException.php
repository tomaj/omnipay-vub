<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Exception;

use Omnipay\Common\Exception\OmnipayException;

/**
 * Implemented by every exception this package throws itself.
 *
 * Invalid requests and bank returns are reported with Omnipay's own
 * `InvalidRequestException`; catch `OmnipayException` to handle both.
 */
interface EplatbyException extends OmnipayException, \Throwable {}
