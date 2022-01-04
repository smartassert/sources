<?php

declare(strict_types=1);

namespace App\Exception\DirectoryDuplicator;

use App\Model\FileLocatorInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class TargetCreationException extends AbstractTargetMutationException
{
    public function __construct(FileLocatorInterface $fileLocator, IOExceptionInterface $IOException)
    {
        parent::__construct($fileLocator, $IOException, sprintf('Unable to create target "%s"', $fileLocator));
    }
}
