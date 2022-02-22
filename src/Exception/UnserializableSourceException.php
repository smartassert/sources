<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\SourceOriginInterface;

class UnserializableSourceException extends \Exception
{
    public function __construct(
        private SourceOriginInterface $source
    ) {
        parent::__construct();
    }

    public function getOriginSource(): SourceOriginInterface
    {
        return $this->source;
    }
}
