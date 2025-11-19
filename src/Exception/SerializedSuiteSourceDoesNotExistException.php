<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\SerializedSuiteInterface;

class SerializedSuiteSourceDoesNotExistException extends \Exception
{
    public function __construct(
        public readonly SerializedSuiteInterface $source
    ) {
        parent::__construct();
    }
}
