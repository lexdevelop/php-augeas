<?php declare(strict_types=1);

namespace AugeasLib\Exception;

class AugeasIOError extends \Exception
{
    public function __construct(string $message, int $code, \Exception $previous = null)
    {
        parent::__construct($code, $message, $previous);
    }
}
