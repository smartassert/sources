<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Exception\DirectoryDuplicationException;
use App\Exception\File\OutOfScopeException;
use App\Exception\File\RemoveException;
use App\Exception\File\WriteException;
use App\Exception\SourceRead\SourceReadExceptionInterface;
use App\Exception\UserGitRepositoryException;
use Symfony\Component\Filesystem\Path;

class RunSourcePreparer
{
    public function __construct(
        private DirectoryDuplicator $directoryDuplicator,
        private UserGitRepositoryPreparer $gitRepositoryPreparer,
        private FileStoreManager $fileStoreManager,
        private SourceSerializer $sourceSerializer,
    ) {
    }

    /**
     * @throws DirectoryDuplicationException
     * @throws UserGitRepositoryException
     */
    public function prepare(RunSource $target): void
    {
        $source = $target->getParent();
        if ($source instanceof FileSource) {
            $this->directoryDuplicator->duplicate((string) $source, (string) $target);
        }

        if ($source instanceof GitSource) {
            $gitRepository = $this->gitRepositoryPreparer->prepare($source, $target->getParameters()['ref'] ?? null);
            $copyableSourcePath = Path::canonicalize($gitRepository->getPath() . '/' . $source->getPath());

            $this->directoryDuplicator->duplicate($copyableSourcePath, (string) $target);

            try {
                $this->fileStoreManager->remove((string) $gitRepository);
            } catch (OutOfScopeException | RemoveException) {
            }
        }
    }

    /**
     * @throws WriteException
     * @throws SourceReadExceptionInterface
     * @throws UserGitRepositoryException
     */
    public function prepareAndSerialize(RunSource $target): void
    {
        $source = $target->getParent();
        $serializedSourcePath = $target . '/serialized.yaml';

        if ($source instanceof FileSource) {
            $content = $this->sourceSerializer->serialize($source);
            $this->fileStoreManager->add($serializedSourcePath, $content);
        }

        if ($source instanceof GitSource) {
            $gitRepository = $this->gitRepositoryPreparer->prepare($source, $target->getParameters()['ref'] ?? null);
            $content = $this->sourceSerializer->serialize($gitRepository, $source->getPath());
            $this->fileStoreManager->add($serializedSourcePath, $content);

            try {
                $this->fileStoreManager->remove((string) $gitRepository);
            } catch (OutOfScopeException | RemoveException) {
            }
        }
    }
}
