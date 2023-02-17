<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\RunSource;
use App\Entity\SourceOriginInterface;
use App\Exception\EmptyEntityIdException;
use App\Repository\SourceRepository;
use App\Services\EntityIdFactory;
use Symfony\Component\HttpFoundation\Request;

class RunSourceFactory
{
    public function __construct(
        private readonly EntityIdFactory $entityIdFactory,
        private readonly SourceRepository $sourceRepository,
    ) {
    }

    /**
     * @throws EmptyEntityIdException
     */
    public function create(SourceOriginInterface $source, Request $request): RunSource
    {
        $parameters = [];
        foreach ($source->getRunParameterNames() as $runParameterName) {
            if ($request->request->has($runParameterName)) {
                $parameters[$runParameterName] = (string) $request->request->get($runParameterName);
            }
        }

        $source = new RunSource($this->entityIdFactory->create(), $source, $parameters);
        $this->sourceRepository->save($source);

        return $source;
    }
}
