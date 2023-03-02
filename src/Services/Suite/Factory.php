<?php

declare(strict_types=1);

namespace App\Services\Suite;

use App\Entity\SourceOriginInterface;
use App\Entity\Suite;
use App\Exception\EmptyEntityIdException;
use App\Exception\NonUniqueSuiteLabelException;
use App\Repository\SuiteRepository;
use App\Request\CreateSuiteRequest;
use App\Services\EntityIdFactory;

class Factory
{
    public function __construct(
        private readonly SuiteRepository $repository,
        private readonly EntityIdFactory $entityIdFactory,
        private readonly Mutator $mutator,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     * @throws NonUniqueSuiteLabelException
     */
    public function create(SourceOriginInterface $source, CreateSuiteRequest $request): Suite
    {
        $suite = $this->repository->findOneBy([
            'source' => $source,
            'userId' => $source->getUserId(),
            'label' => $request->label,
            'tests' => $request->tests,
            'deletedAt' => null,
        ]);

        if (null === $suite) {
            $suite = $this->mutator->update(
                new Suite($this->entityIdFactory->create(), $source),
                $request
            );
        }

        return $suite;
    }
}
