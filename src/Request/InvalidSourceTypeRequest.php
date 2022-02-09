<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class InvalidSourceTypeRequest implements SourceRequestInterface
{
    public const ERROR_MESSAGE = 'type must be one of ["file", "git"]';

    public function __construct(
        #[Assert\Choice(['file', 'git'], message: self::ERROR_MESSAGE)]
        private string $type,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }
}
