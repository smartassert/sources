<?php

declare(strict_types=1);

namespace App\Services\Suite;

use App\Entity\SourceOriginInterface;
use App\Entity\Suite;
use App\Exception\EmptyEntityIdException;
use App\Repository\SuiteRepository;
use App\Request\CreateSuiteRequest;
use App\Services\EntityIdFactory;

class Factory
{
    public function __construct(
        private readonly SuiteRepository $repository,
        private readonly EntityIdFactory $entityIdFactory,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     */
    public function create(SourceOriginInterface $source, CreateSuiteRequest $request): Suite
    {
        $suite = $this->repository->findOneBy([
            'userId' => $source->getUserId(),
            'label' => $request->label,
            'deletedAt' => null,
        ]);

        if (null === $suite) {
            $suite = new Suite($this->entityIdFactory->create(), $source);
            $suite->setLabel($request->label);
            $suite->setTests($request->tests);

            $this->repository->save($suite);
        }

        return $suite;
    }
}
