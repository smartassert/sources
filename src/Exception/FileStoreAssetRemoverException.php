<?php

declare(strict_types=1);

namespace App\Exception;

use App\Model\FileStore;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class FileStoreAssetRemoverException extends \Exception
{
    public const CODE_PATH_NOT_ABSOLUTE = 100;
    public const MESSAGE_PATH_NO_ABSOLUTE = 'Path "%s" is not absolute';
    public const CODE_PATH_IS_OUTSIDE_BASE_PATH = 200;
    public const MESSAGE_PATH_IS_OUTSIDE_BASE_PATH = 'Path "%s" outside of the base path "%s"';
    public const CODE_FILESYSTEM_ERROR = 300;
    public const MESSAGE_FILESYSTEM_ERROR = 'Filesystem error, see previous exception for details';

    public function __construct(
        private FileStore $fileStore,
        string $message,
        int $code,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function createPathNotAbsoluteException(FileStore $fileStore): self
    {
        return new self(
            $fileStore,
            sprintf(self::MESSAGE_PATH_NO_ABSOLUTE, (string) $fileStore),
            self::CODE_PATH_NOT_ABSOLUTE
        );
    }

    public static function createPathIsOutsideBasePathException(FileStore $fileStore): self
    {
        return new self(
            $fileStore,
            sprintf(
                self::MESSAGE_PATH_IS_OUTSIDE_BASE_PATH,
                $fileStore->getPath(),
                $fileStore->getBasePath()
            ),
            self::CODE_PATH_IS_OUTSIDE_BASE_PATH
        );
    }

    public static function createFilesystemErrorException(FileStore $fileStore, IOExceptionInterface $exception): self
    {
        return new self(
            $fileStore,
            self::MESSAGE_FILESYSTEM_ERROR,
            self::CODE_PATH_IS_OUTSIDE_BASE_PATH,
            $exception
        );
    }

    public function getFileStore(): FileStore
    {
        return $this->fileStore;
    }
}
