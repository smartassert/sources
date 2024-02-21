<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Exception\StorageExceptionFactory;
use App\Request\AddYamlFileRequest;
use App\Response\EmptyResponse;
use App\Response\YamlResponse;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;
use SmartAssert\ServiceRequest\Exception\ErrorResponseException;
use SmartAssert\ServiceRequest\Exception\ErrorResponseExceptionFactory;
use SmartAssert\ServiceRequest\Parameter\Parameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/file-source/' . SourceRoutes::ROUTE_SOURCE_ID_PATTERN . '/{filename<.*>}', name: 'file_source_file_')]
readonly class FileSourceFileController
{
    public function __construct(
        private FilesystemWriter $fileSourceWriter,
        private FilesystemReader $fileSourceReader,
        private ErrorResponseExceptionFactory $errorResponseExceptionFactory,
        private StorageExceptionFactory $storageExceptionFactory,
    ) {
    }

    /**
     * @throws ErrorResponseException
     */
    #[Route(name: 'store', methods: ['POST', 'PUT'])]
    public function store(FileSource $source, AddYamlFileRequest $request, Request $symfonyRequest): Response
    {
        $yamlFile = $request->file;
        $path = $source->getDirectoryPath() . '/' . $yamlFile->name;

        try {
            if ('POST' === $symfonyRequest->getMethod() && $this->fileSourceReader->fileExists($path)) {
                throw $this->errorResponseExceptionFactory->createForDuplicateObject(
                    new Parameter('filename', (string) $yamlFile->name)
                );
            }

            $this->fileSourceWriter->write($path, $yamlFile->content);
        } catch (FilesystemException $e) {
            throw $this->errorResponseExceptionFactory->createForStorageError(
                $this->storageExceptionFactory->createForEntityStorageFailure($source, $e)
            );
        }

        return new EmptyResponse();
    }

    /**
     * @throws ErrorResponseException
     */
    #[Route(name: 'read', methods: ['GET'])]
    public function read(FileSource $source, string $filename): Response
    {
        $location = $source->getDirectoryPath() . '/' . $filename;

        try {
            if (false === $this->fileSourceReader->fileExists($location)) {
                return new Response('', 404);
            }

            return new YamlResponse($this->fileSourceReader->read($location));
        } catch (FilesystemException $e) {
            throw $this->errorResponseExceptionFactory->createForStorageError(
                $this->storageExceptionFactory->createForEntityStorageFailure($source, $e)
            );
        }
    }

    /**
     * @throws ErrorResponseException
     */
    #[Route(name: 'remove', methods: ['DELETE'])]
    public function remove(FileSource $source, string $filename): Response
    {
        try {
            $this->fileSourceWriter->delete($source->getDirectoryPath() . '/' . $filename);
        } catch (FilesystemException $e) {
            throw $this->errorResponseExceptionFactory->createForStorageError(
                $this->storageExceptionFactory->createForEntityStorageFailure($source, $e)
            );
        }

        return new EmptyResponse();
    }
}
