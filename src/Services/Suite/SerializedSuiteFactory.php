<?php

declare(strict_types=1);

namespace App\Services\Suite;

use App\Entity\SerializedSuite;
use App\Entity\Suite;
use App\Exception\EmptyEntityIdException;
use App\Repository\SerializedSuiteRepository;
use App\Services\EntityIdFactory;
use Symfony\Component\HttpFoundation\Request;

class SerializedSuiteFactory
{
    public function __construct(
        private readonly EntityIdFactory $entityIdFactory,
        private readonly SerializedSuiteRepository $serializedSuiteRepository,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     */
    public function create(Suite $suite, Request $request): SerializedSuite
    {
        $source = $suite->getSource();

        $parameters = [];
        foreach ($source->getRunParameterNames() as $runParameterName) {
            if ($request->request->has($runParameterName)) {
                $parameters[$runParameterName] = (string) $request->request->get($runParameterName);
            }
        }

        $serializedSuite = new SerializedSuite($this->entityIdFactory->create(), $suite, $parameters);
        $this->serializedSuiteRepository->save($serializedSuite);

        return $serializedSuite;
    }
}
