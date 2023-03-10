<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\SourceInterface;

class UnserializableSourceException extends \Exception
{
    public function __construct(
        private readonly SourceInterface $source
    ) {
        parent::__construct();
    }

    public function getOriginSource(): SourceInterface
    {
        return $this->source;
    }
}
