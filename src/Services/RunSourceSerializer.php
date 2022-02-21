<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Exception\GitRepositoryException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;
use Symfony\Component\Yaml\Exception\ParseException;

class RunSourceSerializer
{
    public const SERIALIZED_FILENAME = 'source.yaml';

    public function __construct(
        private SourceSerializer $sourceSerializer,
        private GitRepositoryStore $gitRepositoryStore,
        private SerializableSourceLister $sourceLister,
        private FilesystemReader $fileSourceReader,
        private FilesystemReader $gitRepositoryReader,
        private FilesystemWriter $gitRepositoryWriter,
        private FilesystemReader $runSourceReader,
        private FilesystemWriter $runSourceWriter,
    ) {
    }

    /**
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
            $files = $this->sourceLister->list($this->fileSourceReader, (string) $source);
            $content = $this->sourceSerializer->serialize($this->fileSourceReader, $files);
        }

        if ($source instanceof GitSource) {
            $gitRepository = $this->gitRepositoryStore->initialize($source, $target->getParameters()['ref'] ?? null);

            $sourcePath = rtrim(
                sprintf('%s/%s', $gitRepository, ltrim($source->getPath(), '/')),
                '/'
            );

            $files = $this->sourceLister->list($this->gitRepositoryReader, $sourcePath);
            $content = $this->sourceSerializer->serialize($this->gitRepositoryReader, $files);

            try {
                $this->gitRepositoryWriter->deleteDirectory((string) $gitRepository);
            } catch (FilesystemException) {
            }
        }

        if (is_string($content)) {
            $this->runSourceWriter->write($serializedSourcePath, $content);
        }
    }

    /**
     * @throws FilesystemException
     */
    public function read(RunSource $runSource): string
    {
        return trim($this->runSourceReader->read($runSource . '/' . self::SERIALIZED_FILENAME));
    }
}
