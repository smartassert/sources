<?php

declare(strict_types=1);

namespace App\Services\Suite;

use App\Entity\Suite;
use App\Exception\EmptyEntityIdException;
use App\Exception\ErrorResponseException;
use App\Repository\SuiteRepository;
use App\Request\SuiteRequest;
use App\Services\EntityIdFactory;

readonly class Factory
{
    public function __construct(
        private SuiteRepository $repository,
        private EntityIdFactory $entityIdFactory,
        private Mutator $mutator,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     * @throws ErrorResponseException
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
