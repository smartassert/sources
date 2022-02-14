<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Exception\File\RemoveException;
use App\Exception\File\WriteException;
use App\Exception\InvalidRequestException;
use App\Request\AddYamlFileRequest;
use App\Request\RemoveYamlFileRequest;
use App\ResponseBody\FileExceptionResponse;
use App\Security\UserSourceAccessChecker;
use App\Services\FileStoreManager;
use App\Services\RequestValidator;
use App\Services\ResponseFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FileSourceFileController
{
    private const ROUTE_FILENAME_PATTERN = '{filename<.*\.yaml>}';
    private const ROUTE_SOURCE_FILE = SourceRoutes::ROUTE_SOURCE . '/' . self::ROUTE_FILENAME_PATTERN;

    public function __construct(
        private UserSourceAccessChecker $userSourceAccessChecker,
        private RequestValidator $requestValidator,
        private FileStoreManager $fileStoreManager,
        private ResponseFactory $responseFactory,
    ) {
    }

    /**
     * @throws InvalidRequestException
     */
    #[Route(self::ROUTE_SOURCE_FILE, name: 'add_file', methods: ['POST'])]
    public function add(FileSource $source, AddYamlFileRequest $request): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);
        $this->requestValidator->validate($request, ['filename.', 'file.']);

        $yamlFile = $request->getYamlFile();

        try {
            $this->fileStoreManager->write($source . '/' . $yamlFile->name, $yamlFile->content);
        } catch (WriteException $exception) {
            return $this->responseFactory->createErrorResponse(new FileExceptionResponse($exception), 500);
        }

        return new Response();
    }

    /**
     * @throws InvalidRequestException
     */
    #[Route(self::ROUTE_SOURCE_FILE, name: 'remove_file', methods: ['DELETE'])]
    public function remove(FileSource $source, RemoveYamlFileRequest $request): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);
        $this->requestValidator->validate($request, ['filename.']);

        try {
            $this->fileStoreManager->removeFile($source . '/' . $request->getFilename());
        } catch (RemoveException $exception) {
            return $this->responseFactory->createErrorResponse(new FileExceptionResponse($exception), 500);
        }

        return new Response();
    }
}
