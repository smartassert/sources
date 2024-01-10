<?php

declare(strict_types=1);

namespace App\Services\Suite;

use App\Entity\Suite;
use App\Exception\ErrorResponseException;
use App\Exception\ErrorResponseExceptionFactory;
use App\Repository\SuiteRepository;
use App\Request\SuiteRequest;
use SmartAssert\ServiceRequest\Field\Field;

readonly class Mutator
{
    public function __construct(
        private SuiteRepository $repository,
        private ErrorResponseExceptionFactory $exceptionFactory,
    ) {
    }

    /**
     * @throws ErrorResponseException
     */
    public function update(Suite $suite, SuiteRequest $request): Suite
    {
        $foundSuite = $this->repository->findOneBySourceOwnerAndLabel($request->source, $request->label);
        if ($foundSuite instanceof Suite && $foundSuite->id !== $suite->id) {
            throw $this->exceptionFactory->createForDuplicateObject(new Field('label', $request->getLabel()));
        }

        $suite->setSource($request->source);
        $suite->setLabel($request->label);
        $suite->setTests($request->tests);

        $this->repository->save($suite);

        return $suite;
    }
}
