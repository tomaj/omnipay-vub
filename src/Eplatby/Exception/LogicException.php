<?php

declare(strict_types=1);

namespace Omnipay\Eplatby\Exception;

/**
 * A programming error, for example a response created for the wrong request type.
 */
final class LogicException extends \LogicException implements EplatbyException {}
