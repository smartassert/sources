<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Controller\SerializedSuiteRoutes;
use App\Controller\SuiteRoutes;
use App\Entity\Suite;
use App\Exception\EntityNotFoundException;
use App\Repository\SuiteRepository;
use App\Request\CreateSerializedSuiteRequest;
use App\Security\EntityAccessChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CreateSerializedSuiteRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly SuiteRepository $suiteRepository,
        private readonly EntityAccessChecker $entityAccessChecker,
    ) {
    }

    /**
     * @return CreateSerializedSuiteRequest[]
     *
     * @throws AccessDeniedException
     * @throws EntityNotFoundException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (CreateSerializedSuiteRequest::class !== $argument->getType()) {
            return [];
        }

        $suiteId = $request->attributes->get(SuiteRoutes::ATTRIBUTE_SUITE_ID);
        if (!is_string($suiteId)) {
            $suiteId = '';
        }

        $suite = $this->suiteRepository->find($suiteId);
        if (null === $suite) {
            throw new EntityNotFoundException($suiteId, 'Suite');
        }

        $this->entityAccessChecker->denyAccessUnlessGranted($suite);

        $serializedSuiteId = $request->attributes->get(SerializedSuiteRoutes::ATTRIBUTE_SUITE_ID);
        if (!is_string($serializedSuiteId) || '' === $serializedSuiteId) {
            throw new BadRequestHttpException('Serialized suite id cannot be empty.');
        }

        return [new CreateSerializedSuiteRequest(
            $serializedSuiteId,
            $suite,
            $this->createRunParameters($suite, $request)
        )];
    }

    /**
     * @return array<non-empty-string, string>
     */
    private function createRunParameters(Suite $suite, Request $request): array
    {
        $source = $suite->getSource();

        $parameters = [];
        foreach ($source->getRunParameterNames() as $runParameterName) {
            if ($request->request->has($runParameterName)) {
                $parameters[$runParameterName] = (string) $request->request->get($runParameterName);
            }
        }

        return $parameters;
    }
}
