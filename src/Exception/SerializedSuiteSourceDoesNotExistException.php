<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\SerializedSuite;

class SerializedSuiteSourceDoesNotExistException extends \Exception
{
    public function __construct(
        public readonly SerializedSuite $source
    ) {
        parent::__construct();
    }
}
