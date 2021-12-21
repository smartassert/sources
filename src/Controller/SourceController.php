<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Repository\SourceRepository as Repository;
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
    public const ROUTE_SOURCE = '/';

    public function __construct(
        private Factory $factory,
        private Repository $repository,
        private Store $store,
        private Mutator $mutator
    ) {
    }

    #[Route(self::ROUTE_GIT_SOURCE_CREATE, name: 'create_git', methods: ['POST'])]
    public function createGitSource(UserInterface $user, GitSourceRequest $request): JsonResponse
    {
        return new JsonResponse($this->factory->createGitSourceFromRequest($user, $request));
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}', name: 'get', methods: ['GET'])]
    public function get(string $sourceId, UserInterface $user): JsonResponse
    {
        return $this->doAction($sourceId, $user, function (SourceInterface $source): JsonResponse {
            return new JsonResponse($source);
        });
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}', name: 'update', methods: ['PUT'])]
    public function update(string $sourceId, Request $request, UserInterface $user): JsonResponse
    {
        return $this->doAction($sourceId, $user, function (SourceInterface $source) use ($request): JsonResponse {
            if (!$source instanceof GitSource) {
                return new JsonResponse(null, 404);
            }

            $source = $this->mutator->updateGitSource($source, GitSourceRequest::create($request));

            return new JsonResponse($source);
        });
    }

    #[Route(self::ROUTE_SOURCE . '{sourceId<[A-Z90-9]{26}>}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $sourceId, UserInterface $user): JsonResponse
    {
        return $this->doAction($sourceId, $user, function (SourceInterface $source): JsonResponse {
            $this->store->remove($source);

            return new JsonResponse(null, 200);
        });
    }

    /**
     * @param callable(SourceInterface): JsonResponse $action
     */
    private function doAction(string $sourceId, UserInterface $user, callable $action): JsonResponse
    {
        $source = $this->repository->find($sourceId);
        if (null === $source) {
            return new JsonResponse(null, 404);
        }

        if ($user->getUserIdentifier() !== $source->getUserId()) {
            return new JsonResponse(null, 401);
        }

        return $action($source);
    }
}
