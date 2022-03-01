<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Exception\InvalidRequestException;
use App\Request\AddYamlFileRequest;
use App\Request\YamlFileRequest;
use App\Security\UserSourceAccessChecker;
use App\Services\RequestValidator;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemWriter;
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
    #[Route(self::ROUTE_SOURCE_FILE, name: 'file_source_file_remove', methods: ['DELETE'])]
    public function remove(FileSource $source, YamlFileRequest $request): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);
        $this->requestValidator->validate($request, ['filename.']);
        $this->fileSourceWriter->delete($source->getDirectoryPath() . '/' . $request->getFilename());

        return new Response();
    }
}
