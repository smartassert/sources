<?php

declare(strict_types=1);

namespace App\Exception\FileStore;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class CreateException extends \Exception implements FileStoreExceptionInterface
{
    use GetIOExceptionTrait;
    use GetPathTrait;

    public function __construct(
        private string $path,
        private IOExceptionInterface $IOException
    ) {
        parent::__construct(sprintf('Unable to create "%s"', $path));
    }
}
