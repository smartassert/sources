<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\ReadException;
use App\Exception\File\RemoveException;
use App\Exception\File\WriteException;
use App\Exception\SourceRead\SourceReadExceptionInterface;
use App\Exception\UserGitRepositoryException;

class RunSourceSerializer
{
    public const SERIALIZED_FILENAME = 'source.yaml';

    public function __construct(
        private UserGitRepositoryPreparer $gitRepositoryPreparer,
        private FileStoreManager $fileStoreManager,
        private SourceSerializer $sourceSerializer,
    ) {
    }

    /**
     * @throws WriteException
     * @throws SourceReadExceptionInterface
     * @throws UserGitRepositoryException
     */
    public function write(RunSource $target): void
    {
        $source = $target->getParent();
        $serializedSourcePath = $target . '/' . self::SERIALIZED_FILENAME;

        if ($source instanceof FileSource) {
            $content = $this->sourceSerializer->serialize($source);
            $this->fileStoreManager->write($serializedSourcePath, $content);
        }

        if ($source instanceof GitSource) {
            $gitRepository = $this->gitRepositoryPreparer->prepare($source, $target->getParameters()['ref'] ?? null);
            $content = $this->sourceSerializer->serialize($gitRepository, $source->getPath());
            $this->fileStoreManager->write($serializedSourcePath, $content);

            try {
                $this->fileStoreManager->remove((string) $gitRepository);
            } catch (OutOfScopeException | RemoveException) {
            }
        }
    }

    /**
     * @throws ReadException
     */
    public function read(RunSource $runSource): string
    {
        return $this->fileStoreManager->read($runSource . '/' . self::SERIALIZED_FILENAME);
    }
}
