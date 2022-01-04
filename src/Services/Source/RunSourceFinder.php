<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Repository\RunSourceRepository;

class RunSourceFinder implements TypeFinderInterface
{
    public function __construct(
        private RunSourceRepository $repository
    ) {
    }

    public function supports(string $type): bool
    {
        return SourceInterface::TYPE_RUN === $type;
    }

    public function find(SourceInterface $source): ?RunSource
    {
        if (!$source instanceof RunSource) {
            return null;
        }

        $parameters = $source->getParameters();
        ksort($parameters);

        return $this->repository->findOneBy([
            'parent' => $source->getParent(),
            'parameters' => [] === $parameters ? null : $parameters,
        ]);
    }
}
