<?php

declare(strict_types=1);

namespace App\Services\Suite;

use App\Entity\Suite;
use App\Exception\DuplicateObjectException;
use App\Exception\EmptyEntityIdException;
use App\Repository\SuiteRepository;
use App\Request\SuiteRequest;
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
     * @throws DuplicateObjectException
     */
    public function create(SuiteRequest $request): Suite
    {
        $testsComparator = $request->tests;
        if ([] === $testsComparator) {
            $testsComparator = null;
        }

        $suite = $this->repository->findOneBy([
            'source' => $request->source,
            'label' => $request->label,
            'tests' => $testsComparator,
            'deletedAt' => null,
        ]);

        if (null === $suite) {
            $suite = $this->mutator->update(
                new Suite($this->entityIdFactory->create()),
                $request
            );
        }

        return $suite;
    }
}
