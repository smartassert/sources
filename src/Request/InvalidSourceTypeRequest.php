<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class InvalidSourceTypeRequest implements SourceRequestInterface
{
    public function __construct(
        #[Assert\Choice(['file', 'git'])]
        private string $sourceType,
    ) {
    }

    public function getType(): string
    {
        return $this->sourceType;
    }
}
