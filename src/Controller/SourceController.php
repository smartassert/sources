<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Entity\OriginSourceInterface as OriginSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Exception\File\ReadException;
use App\Exception\File\RemoveException;
use App\Exception\File\WriteException;
use App\Message\Prepare;
use App\Repository\SourceRepository;
use App\Request\AddYamlFileRequest;
use App\Request\RemoveYamlFileRequest;
use App\Request\SourceRequestInterface;
use App\ResponseBody\FileExceptionResponse;
use App\Security\UserSourceAccessChecker;
use App\Services\FileStoreManager;
use App\Services\InvalidRequestResponseFactory;
use App\Services\ResponseFactory;
use App\Services\RunSourceFactory;
use App\Services\RunSourceSerializer;
use App\Services\Source\Factory;
use App\Services\Source\Mutator;
use App\Services\Source\Store;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SourceController
{
    private const ROUTE_SOURCE_ID_PATTERN = '{sourceId<[A-Z90-9]{26}>}';
    private const ROUTE_FILENAME_PATTERN = '{filename<.*\.yaml>}';
    private const ROUTE_SOURCE = '/' . self::ROUTE_SOURCE_ID_PATTERN;
    private const ROUTE_SOURCE_FILE = self::ROUTE_SOURCE . '/' . self::ROUTE_FILENAME_PATTERN;
    private const ROUTE_SOURCE_LIST = '/list';

    public function __construct(
        private ResponseFactory $responseFactory,
        private ValidatorInterface $validator,
        private InvalidRequestResponseFactory $invalidRequestResponseFactory,
        private UserSourceAccessChecker $userSourceAccessChecker,
    ) {
    }

    #[Route('/', name: 'create', methods: ['POST'])]
    public function create(UserInterface $user, Factory $factory, SourceRequestInterface $request): JsonResponse
    {
        if (($response = $this->validateRequest($request)) instanceof JsonResponse) {
            return $response;
        }

        return new JsonResponse($factory->createFromSourceRequest($user, $request));
    }

    #[Route(self::ROUTE_SOURCE, name: 'get', methods: ['GET'])]
    public function get(SourceInterface $source): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        return new JsonResponse($source);
    }

    #[Route(self::ROUTE_SOURCE, name: 'update', methods: ['PUT'])]
    public function update(OriginSource $source, Mutator $mutator, SourceRequestInterface $request): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        if (($response = $this->validateRequest($request)) instanceof JsonResponse) {
            return $response;
        }

        return new JsonResponse($mutator->update($source, $request));
    }

    #[Route(self::ROUTE_SOURCE, name: 'delete', methods: ['DELETE'])]
    public function delete(SourceInterface $source, Store $store): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        $store->remove($source);

        return new JsonResponse();
    }

    #[Route(self::ROUTE_SOURCE_LIST, name: 'list', methods: ['GET'])]
    public function list(UserInterface $user, SourceRepository $repository): JsonResponse
    {
        return new JsonResponse($repository->findByUserAndType($user, [Type::FILE, Type::GIT]));
    }

    #[Route(self::ROUTE_SOURCE . '/prepare', name: 'prepare', methods: ['POST'])]
    public function prepare(
        Request $request,
        OriginSource $source,
        MessageBusInterface $messageBus,
        RunSourceFactory $runSourceFactory,
    ): Response {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        $runSource = $runSourceFactory->createFromRequest($source, $request);
        $messageBus->dispatch(Prepare::createFromRunSource($runSource));

        return new JsonResponse($runSource, 202);
    }

    #[Route(self::ROUTE_SOURCE . '/read', name: 'read', methods: ['GET'])]
    public function read(RunSource $source, RunSourceSerializer $runSourceSerializer): Response
    {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        try {
            return new Response(
                $runSourceSerializer->read($source),
                200,
                [
                    'content-type' => 'text/x-yaml; charset=utf-8',
                ]
            );
        } catch (ReadException $exception) {
            return $this->responseFactory->createErrorResponse(new FileExceptionResponse($exception), 500);
        }
    }

    #[Route(self::ROUTE_SOURCE_FILE, name: 'add_file', methods: ['POST'])]
    public function addFile(
        FileSource $source,
        AddYamlFileRequest $request,
        FileStoreManager $fileStoreManager,
    ): Response {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        if (($response = $this->validateRequest($request, ['filename.', 'file.'])) instanceof JsonResponse) {
            return $response;
        }

        $yamlFile = $request->getYamlFile();

        try {
            $fileStoreManager->write($source . '/' . $yamlFile->name, $yamlFile->content);
        } catch (WriteException $exception) {
            return $this->responseFactory->createErrorResponse(new FileExceptionResponse($exception), 500);
        }

        return new Response();
    }

    #[Route(self::ROUTE_SOURCE_FILE, name: 'remove_file', methods: ['DELETE'])]
    public function removeFile(
        FileSource $source,
        RemoveYamlFileRequest $request,
        FileStoreManager $fileStoreManager,
    ): Response {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);

        if (($response = $this->validateRequest($request, ['filename.'])) instanceof JsonResponse) {
            return $response;
        }

        try {
            $fileStoreManager->removeFile($source . '/' . $request->getFilename());
        } catch (RemoveException $exception) {
            return $this->responseFactory->createErrorResponse(new FileExceptionResponse($exception), 500);
        }

        return new Response();
    }

    /**
     * @param string[] $propertyNamePrefixesToRemove
     */
    private function validateRequest(object $request, array $propertyNamePrefixesToRemove = []): ?JsonResponse
    {
        $errors = $this->validator->validate($request);
        if (0 !== count($errors)) {
            return $this->responseFactory->createErrorResponse(
                $this->invalidRequestResponseFactory->createFromConstraintViolations(
                    $errors,
                    $propertyNamePrefixesToRemove
                ),
                400
            );
        }

        return null;
    }
}
