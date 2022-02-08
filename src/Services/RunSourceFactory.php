<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\OriginSourceInterface;
use App\Entity\RunSource;
use Symfony\Component\HttpFoundation\Request;

class RunSourceFactory
{
    public function createFromRequest(OriginSourceInterface $source, Request $request): RunSource
    {
        $parameters = [];
        foreach ($source->getRunParameterNames() as $runParameterName) {
            if ($request->request->has($runParameterName)) {
                $parameters[$runParameterName] = (string) $request->request->get($runParameterName);
            }
        }

        return new RunSource($source, $parameters);
    }
}
