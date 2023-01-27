<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\SourceInterface;
use App\Exception\SourceNotFoundException;
use App\Exception\UnexpectedSourceTypeException;
use App\Repository\SourceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

abstract class AbstractSourceResolver implements ArgumentValueResolverInterface
{
    public function __construct(
        private SourceRepository $repository,
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return $this->supportsArgumentType((string) $argument->getType());
    }

    /**
     * @return SourceInterface[]
     *
     * @throws SourceNotFoundException
     * @throws UnexpectedSourceTypeException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$this->supports($request, $argument)) {
            return [];
        }

        $sourceId = $request->attributes->get('sourceId');
        if (!is_string($sourceId)) {
            return [];
        }

        $source = $this->repository->find($sourceId);
        if (null === $source) {
            throw new SourceNotFoundException($sourceId);
        }

        $sourceImplementedClasses = class_implements($source);
        $sourceImplementedClasses[$source::class] = $source::class;

        $expectedInstanceClassName = $this->getExpectedInstanceClassName();

        if (false === in_array($expectedInstanceClassName, $sourceImplementedClasses)) {
            throw new UnexpectedSourceTypeException($source, $expectedInstanceClassName);
        }

        return [$source];
    }

    abstract protected function supportsArgumentType(string $type): bool;

    /**
     * @return class-string
     */
    abstract protected function getExpectedInstanceClassName(): string;
}
