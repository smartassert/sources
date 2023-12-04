<?php

declare(strict_types=1);

namespace App\Services\Suite;

use App\Entity\Suite;
use App\Exception\DuplicateObjectException;
use App\Repository\SuiteRepository;
use App\Request\SuiteRequest;
use App\RequestField\Field\Field;

class Mutator
{
    public function __construct(
        private readonly SuiteRepository $repository,
    ) {
    }

    /**
     * @throws DuplicateObjectException
     */
    public function update(Suite $suite, SuiteRequest $request): Suite
    {
        $foundSuite = $this->repository->findOneBySourceOwnerAndLabel($request->source, $request->label);
        if ($foundSuite instanceof Suite && $foundSuite->id !== $suite->id) {
            throw new DuplicateObjectException(new Field('label', $request->getLabel()));
        }

        $suite->setSource($request->source);
        $suite->setLabel($request->label);
        $suite->setTests($request->tests);

        $this->repository->save($suite);

        return $suite;
    }
}
