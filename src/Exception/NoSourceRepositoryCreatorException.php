<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\SourceInterface;

class NoSourceRepositoryCreatorException extends \Exception
{
    public function __construct(
        public readonly SourceInterface $source
    ) {
        parent::__construct(sprintf(
            'No source repository creator is available for source "%s" of type "%s"',
            $this->source->getId(),
            $this->source->getType()->value
        ));
    }
}
