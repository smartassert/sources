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
        private SerializableSourceLister $sourceLister,
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

        $content = null;

        if ($source instanceof FileSource) {
            $files = $this->sourceLister->list($this->fileSourceFileStore, (string) $source);
            $content = $this->sourceSerializer->serialize($this->fileSourceFileStore, $files);
        }

        if ($source instanceof GitSource) {
            $gitRepository = $this->gitRepositoryStore->initialize($source, $target->getParameters()['ref'] ?? null);

            $sourcePath = rtrim(
                sprintf('%s/%s', $gitRepository, ltrim($source->getPath(), '/')),
                '/'
            );

            $files = $this->sourceLister->list($this->gitRepositoryFileStore, $sourcePath);
            $content = $this->sourceSerializer->serialize($this->gitRepositoryFileStore, $files);

            try {
                $this->gitRepositoryFileStore->remove((string) $gitRepository);
            } catch (RemoveException) {
            }
        }

        if (is_string($content)) {
            $this->runSourceFileStore->write($serializedSourcePath, $content);
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
