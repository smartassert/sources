<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Exception\File\FileExceptionInterface;
use App\Exception\InvalidRequestException;
use App\Request\AddYamlFileRequest;
use App\Request\RemoveYamlFileRequest;
use App\Security\UserSourceAccessChecker;
use App\Services\FileStoreManager;
use App\Services\RequestValidator;
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
        private FileStoreManager $fileStoreManager,
    ) {
    }

    /**
     * @throws AccessDeniedException
     * @throws InvalidRequestException
     * @throws FileExceptionInterface
     */
    #[Route(self::ROUTE_SOURCE_FILE, name: 'file_source_file_add', methods: ['POST'])]
    public function add(FileSource $source, AddYamlFileRequest $request): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);
        $this->requestValidator->validate($request, ['filename.', 'file.']);

        $yamlFile = $request->getYamlFile();

        $this->fileStoreManager->write($source . '/' . $yamlFile->name, $yamlFile->content);

        return new Response();
    }

    /**
     * @throws AccessDeniedException
     * @throws InvalidRequestException
     * @throws FileExceptionInterface
     */
    #[Route(self::ROUTE_SOURCE_FILE, name: 'file_source_file_remove', methods: ['DELETE'])]
    public function remove(FileSource $source, RemoveYamlFileRequest $request): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);
        $this->requestValidator->validate($request, ['filename.']);
        $this->fileStoreManager->removeFile($source . '/' . $request->getFilename());

        return new Response();
    }
}
