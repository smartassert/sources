<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Exception\InvalidRequestException;
use App\Request\AddYamlFileRequest;
use App\Request\YamlFileRequest;
use App\Response\YamlResponse;
use App\Security\UserSourceAccessChecker;
use App\Services\RequestValidator;
use App\Services\SourceRepository\Reader\FileSourceDirectoryLister;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use League\Flysystem\FilesystemWriter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class FileSourceFileController
{
    private const ROUTE_FILENAME_PATTERN = '{filename<.*\.yaml>}';
    private const ROUTE_SOURCE_FILE = SourceRoutes::ROUTE_SOURCE . '/' . self::ROUTE_FILENAME_PATTERN;

    public function __construct(
        private UserSourceAccessChecker $userSourceAccessChecker,
        private RequestValidator $requestValidator,
        private FilesystemWriter $fileSourceWriter,
        private FilesystemReader $fileSourceReader,
    ) {
    }

    /**
     * @throws AccessDeniedException
     * @throws InvalidRequestException
     * @throws FilesystemException
     */
    #[Route(self::ROUTE_SOURCE_FILE, name: 'file_source_file_add', methods: ['POST'])]
    public function add(FileSource $source, AddYamlFileRequest $request): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);
        $this->requestValidator->validate($request, ['filename.', 'file.']);

        $yamlFile = $request->getYamlFile();

        $this->fileSourceWriter->write($source->getDirectoryPath() . '/' . $yamlFile->name, $yamlFile->content);

        return new Response();
    }

    /**
     * @throws AccessDeniedException
     * @throws InvalidRequestException
     * @throws FilesystemException
     */
    #[Route(self::ROUTE_SOURCE_FILE, name: 'file_source_file_read', methods: ['GET'])]
    public function read(FileSource $source, YamlFileRequest $request): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);
        $this->requestValidator->validate($request, ['filename.']);

        $location = $source->getDirectoryPath() . '/' . $request->getFilename();

        if (false === $this->fileSourceReader->fileExists($location)) {
            return new Response('', 404);
        }

        return new YamlResponse($this->fileSourceReader->read($location));
    }

    /**
     * @throws AccessDeniedException
     * @throws InvalidRequestException
     * @throws FilesystemException
     */
    #[Route(self::ROUTE_SOURCE_FILE, name: 'file_source_file_remove', methods: ['DELETE'])]
    public function remove(FileSource $source, YamlFileRequest $request): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);
        $this->requestValidator->validate($request, ['filename.']);
        $this->fileSourceWriter->delete($source->getDirectoryPath() . '/' . $request->getFilename());

        return new Response();
    }

    /**
     * @throws AccessDeniedException
     * @throws FilesystemException
     */
    #[Route(SourceRoutes::ROUTE_SOURCE . '/list', name: 'file_source_list_filenames', methods: ['GET'])]
    public function listFilenames(
        FileSource $source,
        FileSourceDirectoryLister $lister,
    ): Response {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        return new JsonResponse($lister->list($source));
    }
}
