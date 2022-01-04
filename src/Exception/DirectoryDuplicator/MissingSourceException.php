<?php

declare(strict_types=1);

namespace App\Exception\DirectoryDuplicator;

use App\Model\FileLocatorInterface;

class MissingSourceException extends AbstractFileLocatorException
{
    public function __construct(FileLocatorInterface $fileLocator)
    {
        parent::__construct($fileLocator, sprintf('Missing source "%s"', $fileLocator));
    }
}
