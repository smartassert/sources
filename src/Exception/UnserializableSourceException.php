<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\OriginSourceInterface;

class UnserializableSourceException extends \Exception
{
    public function __construct(
        private OriginSourceInterface $originSource
    ) {
        parent::__construct();
    }

    public function getOriginSource(): OriginSourceInterface
    {
        return $this->originSource;
    }
}
