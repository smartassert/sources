<?php

declare(strict_types=1);

namespace App\Exception;

use App\Model\SerializableSourceInterface;

class SerializableSourceReaderNotFoundException extends \Exception
{
    public function __construct(
        private SerializableSourceInterface $originSource
    ) {
        parent::__construct();
    }

    public function getSerializableSource(): SerializableSourceInterface
    {
        return $this->originSource;
    }
}
