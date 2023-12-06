<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Exception\DuplicateObjectException;
use App\Exception\EntityStorageException;
use App\Request\AddYamlFileRequest;
use App\Request\YamlFileRequest;
use App\RequestField\Field\Field;
use App\Response\YamlResponse;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    path: '/file-source/' . SourceRoutes::ROUTE_SOURCE_ID_PATTERN . '/' . self::ROUTE_FILENAME_PATTERN,
    name: 'file_source_file_'
)]
class FileSourceFileController
{
    private const ROUTE_FILENAME_PATTERN = '{filename<.*\.yaml>}';

    public function __construct(
        private readonly FilesystemWriter $fileSourceWriter,
        private readonly FilesystemReader $fileSourceReader,
    ) {
    }

    /**
     * @throws DuplicateObjectException
     * @throws EntityStorageException
     */
    #[Route(name: 'add', methods: ['POST'])]
    public function add(FileSource $source, AddYamlFileRequest $request): Response
    {
        $yamlFile = $request->file;
        $path = $source->getDirectoryPath() . '/' . $yamlFile->name;

        try {
            if ($this->fileSourceReader->fileExists($path)) {
                throw new DuplicateObjectException(new Field('filename', (string) $yamlFile->name));
            }

            $this->fileSourceWriter->write($path, $yamlFile->content);
        } catch (FilesystemException $e) {
            throw new EntityStorageException($source, $e);
        }

        return new Response();
    }

    /**
     * @throws EntityStorageException
     */
    #[Route(name: 'update', methods: ['PUT'])]
    public function update(FileSource $source, AddYamlFileRequest $request): Response
    {
        $yamlFile = $request->file;

        try {
            $this->fileSourceWriter->write($source->getDirectoryPath() . '/' . $yamlFile->name, $yamlFile->content);
        } catch (FilesystemException $e) {
            throw new EntityStorageException($source, $e);
        }

        return new Response();
    }

    /**
     * @throws EntityStorageException
     */
    #[Route(name: 'read', methods: ['GET'])]
    public function read(FileSource $source, YamlFileRequest $request): Response
    {
        $location = $source->getDirectoryPath() . '/' . $request->filename;

        try {
            if (false === $this->fileSourceReader->fileExists($location)) {
                return new Response('', 404);
            }

            return new YamlResponse($this->fileSourceReader->read($location));
        } catch (FilesystemException $e) {
            throw new EntityStorageException($source, $e);
        }
    }

    /**
     * @throws EntityStorageException
     */
    #[Route(name: 'remove', methods: ['DELETE'])]
    public function remove(FileSource $source, YamlFileRequest $request): Response
    {
        try {
            $this->fileSourceWriter->delete($source->getDirectoryPath() . '/' . $request->filename);
        } catch (FilesystemException $e) {
            throw new EntityStorageException($source, $e);
        }

        return new Response();
    }
}
