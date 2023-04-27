<?php

declare(strict_types=1);

namespace App\Exception;

use App\Model\SourceRepositoryInterface;

class SourceRepositoryReaderNotFoundException extends \Exception
{
    public function __construct(
        public readonly SourceRepositoryInterface $source
    ) {
        parent::__construct();
    }
}
