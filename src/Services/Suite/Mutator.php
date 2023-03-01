<?php

declare(strict_types=1);

namespace App\Services\Suite;

use App\Entity\Suite;
use App\Exception\NonUniqueSuiteLabelException;
use App\Repository\SuiteRepository;
use App\Request\SuiteRequest;

class Mutator
{
    public function __construct(
        private readonly SuiteRepository $repository,
    ) {
    }

    /**
     * @throws NonUniqueSuiteLabelException
     */
    public function update(Suite $suite, SuiteRequest $request): Suite
    {
        $foundSuite = $this->repository->findOneBy([
            'userId' => $suite->getUserId(),
            'label' => $request->label,
            'deletedAt' => null,
        ]);

        if ($foundSuite instanceof Suite && $foundSuite->id !== $suite->id) {
            throw new NonUniqueSuiteLabelException();
        }

        $suite->setLabel($request->label);
        $suite->setTests($request->tests);

        $this->repository->save($suite);

        return $suite;
    }
}
