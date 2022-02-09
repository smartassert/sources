<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\OriginSourceInterface as OriginSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Exception\File\ReadException;
use App\Message\Prepare;
use App\Repository\SourceRepository;
use App\Request\SourceRequestInterface;
use App\ResponseBody\SourceReadExceptionResponse;
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
    public const ROUTE_SOURCE = '/';
    public const ROUTE_SOURCE_LIST = '/list';

    public function __construct(
        private ResponseFactory $responseFactory,
        private ValidatorInterface $validator,
        private InvalidRequestResponseFactory $invalidRequestResponseFactory,
    ) {
    }

    #[Route(self::ROUTE_SOURCE, name: 'create', methods: ['POST'])]
    public function create(
        UserInterface $user,
        Factory $factory,
        SourceRequestInterface $request,
    ): JsonResponse {
        if (($response = $this->validateRequest($request)) instanceof JsonResponse) {
            return $response;
        }

        return new JsonResponse($factory->createFromSourceRequest($user, $request));
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}', name: 'get', methods: ['GET'])]
    public function get(?SourceInterface $source, UserInterface $user): Response
    {
        return $this->doUserSourceAction($source, $user, function (SourceInterface $source) {
            return new JsonResponse($source);
        });
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}', name: 'update', methods: ['PUT'])]
    public function update(
        ?OriginSource $source,
        UserInterface $user,
        Mutator $mutator,
        SourceRequestInterface $request
    ): Response {
        return $this->doUserSourceAction($source, $user, function (OriginSource $source) use ($request, $mutator) {
            if (($response = $this->validateRequest($request)) instanceof JsonResponse) {
                return $response;
            }

            return new JsonResponse($mutator->update($source, $request));
        });
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}', name: 'delete', methods: ['DELETE'])]
    public function delete(?SourceInterface $source, Store $store, UserInterface $user): Response
    {
        return $this->doUserSourceAction($source, $user, function (SourceInterface $source) use ($store) {
            $store->remove($source);

            return new JsonResponse();
        });
    }

    #[Route(self::ROUTE_SOURCE_LIST, name: 'list', methods: ['GET'])]
    public function list(UserInterface $user, SourceRepository $repository): JsonResponse
    {
        return new JsonResponse($repository->findByUserAndType($user, [Type::FILE, Type::GIT]));
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}/prepare', name: 'prepare', methods: ['POST'])]
    public function prepare(
        Request $request,
        ?OriginSource $source,
        UserInterface $user,
        MessageBusInterface $messageBus,
        RunSourceFactory $runSourceFactory,
    ): Response {
        return $this->doUserSourceAction(
            $source,
            $user,
            function (OriginSource $source) use ($request, $messageBus, $runSourceFactory): JsonResponse {
                $runSource = $runSourceFactory->createFromRequest($source, $request);
                $messageBus->dispatch(Prepare::createFromRunSource($runSource));

                return new JsonResponse($runSource, 202);
            }
        );
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}/read', name: 'read', methods: ['GET'])]
    public function read(
        ?RunSource $source,
        UserInterface $user,
        RunSourceSerializer $runSourceSerializer,
    ): Response {
        return $this->doUserSourceAction($source, $user, function (RunSource $source) use ($runSourceSerializer) {
            try {
                return new Response(
                    $runSourceSerializer->read($source),
                    200,
                    [
                        'content-type' => 'text/x-yaml; charset=utf-8',
                    ]
                );
            } catch (ReadException $exception) {
                return $this->responseFactory->createErrorResponse(new SourceReadExceptionResponse($exception), 500);
            }
        });
    }

    private function doUserSourceAction(?SourceInterface $source, UserInterface $user, callable $action): Response
    {
        if (null === $source) {
            return new JsonResponse(null, 404);
        }

        if ($user->getUserIdentifier() !== $source->getUserId()) {
            return new JsonResponse(null, 401);
        }

        return $action($source);
    }

    private function validateRequest(object $request): ?JsonResponse
    {
        $errors = $this->validator->validate($request);
        if (0 !== count($errors)) {
            return $this->responseFactory->createErrorResponse(
                $this->invalidRequestResponseFactory->createFromConstraintViolations($errors),
                400
            );
        }

        return null;
    }
}
