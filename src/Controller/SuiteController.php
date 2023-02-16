<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SourceInterface;
use App\Entity\SourceOriginInterface;
use App\Request\SuiteRequest;
use App\Security\UserSourceAccessChecker;
use App\Services\Suite\Factory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SuiteController
{
    public function __construct(
        private readonly UserSourceAccessChecker $userSourceAccessChecker,
        private readonly Factory $factory,
    ) {
    }

    /**
     * @throws AccessDeniedException
     */
    #[Route(SuiteRoutes::ROUTE_SUITE_BASE, name: 'user_suite_create', methods: ['POST'])]
    public function create(
        SourceInterface $source,
        SuiteRequest $request,
    ): Response {
        $this->userSourceAccessChecker->denyAccessUnlessGranted($source);
        \assert($source instanceof SourceOriginInterface);

        return new JsonResponse($this->factory->createFromSuiteRequest($source, $request));
    }
}
