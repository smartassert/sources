<?php

declare(strict_types=1);

namespace App\Exception;

class NonUniqueEntityLabelException extends \Exception
{
    /**
     * @param non-empty-string $objectType
     */
    public function __construct(
        public readonly string $objectType,
    ) {
        parent::__construct();
    }
}
