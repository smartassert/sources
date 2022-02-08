<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Exception\File\ReadException;
use App\Message\Prepare;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\FooFileSourceRequest;
use App\Request\FooGitSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\SourceRequestInterface;
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

class SourceController
{
    public const ROUTE_SOURCE = '/';
    public const ROUTE_SOURCE_LIST = '/list';

    public function __construct(
        private Factory $factory,
        private Store $store,
        private Mutator $mutator,
        private SourceRepository $repository,
        private MessageBusInterface $messageBus,
    ) {
    }

    #[Route(self::ROUTE_SOURCE, name: 'create', methods: ['POST'])]
    public function create(UserInterface $user, ?SourceRequestInterface $sourceRequest): JsonResponse
    {
        if (!($sourceRequest instanceof FooFileSourceRequest || $sourceRequest instanceof FooGitSourceRequest)) {
            return new JsonResponse(
                [
                    'error' => [
                        'type' => 'invalid_source_type',
                    ],
                ],
                400
            );
        }

        if (false === $sourceRequest->isValid()) {
            return new JsonResponse(
                [
                    'error' => [
                        'type' => 'required_fields_missing',
                        'missing_fields' => $sourceRequest->getMissingRequiredFields(),
                    ],
                ],
                400
            );
        }

        return new JsonResponse($this->factory->createFromSourceRequest($user, $sourceRequest));
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}', name: 'get', methods: ['GET'])]
    public function get(?SourceInterface $source, UserInterface $user): Response
    {
        return $this->doUserSourceAction($source, $user, function (SourceInterface $source) {
            return new JsonResponse($source);
        });
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}', name: 'update', methods: ['PUT'])]
    public function update(null|FileSource|GitSource $source, Request $request, UserInterface $user): Response
    {
        return $this->doUserSourceAction($source, $user, function (FileSource|GitSource $source) use ($request) {
            $source = $source instanceof FileSource
                ? $this->mutator->updateFileSource($source, FileSourceRequest::create($request))
                : $this->mutator->updateGitSource($source, GitSourceRequest::create($request));

            return new JsonResponse($source);
        });
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}', name: 'delete', methods: ['DELETE'])]
    public function delete(?SourceInterface $source, UserInterface $user): Response
    {
        return $this->doUserSourceAction($source, $user, function (SourceInterface $source) {
            $this->store->remove($source);

            return new JsonResponse();
        });
    }

    #[Route(self::ROUTE_SOURCE_LIST, name: 'list', methods: ['GET'])]
    public function list(UserInterface $user): JsonResponse
    {
        return new JsonResponse($this->repository->findByUserAndType($user, [
            SourceInterface::TYPE_FILE,
            SourceInterface::TYPE_GIT,
        ]));
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}/prepare', name: 'prepare', methods: ['POST'])]
    public function prepare(Request $request, null|FileSource|GitSource $source, UserInterface $user): Response
    {
        return $this->doUserSourceAction(
            $source,
            $user,
            function (FileSource|GitSource $source) use ($request): JsonResponse {
                $parameters = [];
                if ($source instanceof GitSource && $request->request->has('ref')) {
                    $parameters['ref'] = (string) $request->request->get('ref');
                }

                $runSource = new RunSource($source, $parameters);
                $this->store->add($runSource);

                $this->messageBus->dispatch(Prepare::createFromRunSource($runSource));

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
                return new JsonResponse(
                    [
                        'error' => [
                            'type' => 'source_read_exception',
                            'payload' => [
                                'file' => $exception->getPath(),
                                'message' => $exception->getMessage(),
                            ],
                        ],
                    ],
                    500
                );
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
}
