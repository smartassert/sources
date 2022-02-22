<?php

declare(strict_types=1);

namespace App\Exception;

use App\Model\SourceRepositoryInterface;

class SourceRepositoryReaderNotFoundException extends \Exception
{
    public function __construct(
        private SourceRepositoryInterface $originSource
    ) {
        parent::__construct();
    }

    public function getSourceRepository(): SourceRepositoryInterface
    {
        return $this->originSource;
    }
}
