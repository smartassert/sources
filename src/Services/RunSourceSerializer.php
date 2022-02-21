<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Exception\GitRepositoryException;
use App\Exception\Storage\RemoveException;
use App\Exception\Storage\WriteException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\FilesystemReader;
use Symfony\Component\Yaml\Exception\ParseException;

class RunSourceSerializer
{
    public const SERIALIZED_FILENAME = 'source.yaml';

    public function __construct(
        private SourceSerializer $sourceSerializer,
        private FileStoreInterface $gitRepositoryFileStore,
        private FileStoreInterface $runSourceFileStore,
        private GitRepositoryStore $gitRepositoryStore,
        private SerializableSourceLister $sourceLister,
        private FilesystemReader $fileSourceStorage,
        private FilesystemReader $gitRepositoryStorage,
        private FilesystemOperator $runSourceStorage,
    ) {
    }

    /**
     * @throws WriteException
     * @throws ParseException
     * @throws FilesystemException
     * @throws GitRepositoryException
     */
    public function write(RunSource $target): void
    {
        $source = $target->getParent();
        $serializedSourcePath = $target . '/' . self::SERIALIZED_FILENAME;

        $content = null;

        if ($source instanceof FileSource) {
            $files = $this->sourceLister->list($this->fileSourceStorage, (string) $source);
            $content = $this->sourceSerializer->serialize($this->fileSourceStorage, $files);
        }

        if ($source instanceof GitSource) {
            $gitRepository = $this->gitRepositoryStore->initialize($source, $target->getParameters()['ref'] ?? null);

            $sourcePath = rtrim(
                sprintf('%s/%s', $gitRepository, ltrim($source->getPath(), '/')),
                '/'
            );

            $files = $this->sourceLister->list($this->gitRepositoryStorage, $sourcePath);
            $content = $this->sourceSerializer->serialize($this->gitRepositoryStorage, $files);

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
     * @throws FilesystemException
     */
    public function read(RunSource $runSource): string
    {
        return trim($this->runSourceStorage->read($runSource . '/' . self::SERIALIZED_FILENAME));
    }
}
