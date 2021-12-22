<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Services\Source\Factory;
use App\Services\Source\Mutator;
use App\Services\Source\Store;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class SourceController
{
    public const ROUTE_GIT_SOURCE_CREATE = '/git';
    public const ROUTE_FILE_SOURCE_CREATE = '/file';
    public const ROUTE_SOURCE = '/';
    public const ROUTE_SOURCE_LIST = '/list';

    public function __construct(
        private Factory $factory,
        private Store $store,
        private Mutator $mutator,
        private SourceRepository $repository,
    ) {
    }

    #[Route(self::ROUTE_GIT_SOURCE_CREATE, name: 'create_git', methods: ['POST'])]
    public function createGitSource(UserInterface $user, GitSourceRequest $request): JsonResponse
    {
        return new JsonResponse($this->factory->createGitSourceFromRequest($user, $request));
    }

    #[Route(self::ROUTE_FILE_SOURCE_CREATE, name: 'create_file', methods: ['POST'])]
    public function createFileSource(UserInterface $user, FileSourceRequest $request): JsonResponse
    {
        return new JsonResponse($this->factory->createFileSourceFromRequest($user, $request));
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}', name: 'get', methods: ['GET'])]
    public function get(?SourceInterface $source, UserInterface $user): JsonResponse
    {
        return $this->doAction($source, $user, function (SourceInterface $source): JsonResponse {
            return new JsonResponse($source);
        });
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}', name: 'update', methods: ['PUT'])]
    public function update(?SourceInterface $source, Request $request, UserInterface $user): JsonResponse
    {
        return $this->doAction($source, $user, function (SourceInterface $source) use ($request): JsonResponse {
            if ($source instanceof FileSource) {
                $source = $this->mutator->updateFileSource($source, FileSourceRequest::create($request));

                return new JsonResponse($source);
            }

            if ($source instanceof GitSource) {
                $source = $this->mutator->updateGitSource($source, GitSourceRequest::create($request));

                return new JsonResponse($source);
            }

            return new JsonResponse(null, 404);
        });
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}', name: 'delete', methods: ['DELETE'])]
    public function delete(?SourceInterface $source, UserInterface $user): JsonResponse
    {
        return $this->doAction($source, $user, function (SourceInterface $source): JsonResponse {
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

    /**
     * @param callable(SourceInterface): JsonResponse $action
     */
    private function doAction(?SourceInterface $source, UserInterface $user, callable $action): JsonResponse
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
