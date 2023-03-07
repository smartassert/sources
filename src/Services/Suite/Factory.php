<?php

declare(strict_types=1);

namespace App\Services\Suite;

use App\Entity\Suite;
use App\Exception\EmptyEntityIdException;
use App\Exception\NonUniqueEntityLabelException;
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
     * @throws NonUniqueEntityLabelException
     */
    public function create(SuiteRequest $request): Suite
    {
        $source = $request->source;

        $suite = $this->repository->findOneBy([
            'source' => $source,
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
