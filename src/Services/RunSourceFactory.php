<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\RunSource;
use App\Entity\SourceOriginInterface;
use App\Services\Source\Store;
use Symfony\Component\HttpFoundation\Request;

class RunSourceFactory
{
    public function __construct(
        private Store $store
    ) {
    }

    public function createFromRequest(SourceOriginInterface $source, Request $request): RunSource
    {
        $parameters = [];
        foreach ($source->getRunParameterNames() as $runParameterName) {
            if ($request->request->has($runParameterName)) {
                $parameters[$runParameterName] = (string) $request->request->get($runParameterName);
            }
        }

        $source = new RunSource($source, $parameters);
        $this->store->add($source);

        return $source;
    }
}
