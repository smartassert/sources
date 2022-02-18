<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Exception\GitRepositoryException;
use App\Exception\SourceRead\SourceReadExceptionInterface;
use App\Exception\Storage\ReadException;
use App\Exception\Storage\RemoveException;
use App\Exception\Storage\WriteException;

class RunSourceSerializer
{
    public const SERIALIZED_FILENAME = 'source.yaml';

    public function __construct(
        private SourceSerializer $sourceSerializer,
        private FileStoreInterface $fileSourceFileStore,
        private FileStoreInterface $gitRepositoryFileStore,
        private FileStoreInterface $runSourceFileStore,
        private GitRepositoryStore $gitRepositoryStore,
    ) {
    }

    /**
     * @throws WriteException
     * @throws SourceReadExceptionInterface
     * @throws GitRepositoryException
     */
    public function write(RunSource $target): void
    {
        $source = $target->getParent();
        $serializedSourcePath = $target . '/' . self::SERIALIZED_FILENAME;

        if ($source instanceof FileSource) {
            $content = $this->sourceSerializer->serialize($this->fileSourceFileStore, (string) $source);
            $this->runSourceFileStore->write($serializedSourcePath, $content);
        }

        if ($source instanceof GitSource) {
            $gitRepository = $this->gitRepositoryStore->initialize($source, $target->getParameters()['ref'] ?? null);
            $content = $this->sourceSerializer->serialize(
                $this->gitRepositoryFileStore,
                (string) $gitRepository,
                $source->getPath()
            );
            $this->runSourceFileStore->write($serializedSourcePath, $content);

            try {
                $this->gitRepositoryFileStore->remove((string) $gitRepository);
            } catch (RemoveException) {
            }
        }
    }

    /**
     * @throws ReadException
     */
    public function read(RunSource $runSource): string
    {
        return trim($this->runSourceFileStore->read($runSource . '/' . self::SERIALIZED_FILENAME));
    }
}
