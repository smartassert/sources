<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\OriginSourceInterface;
use App\Entity\SourceInterface;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\SourceRequestInterface;

class Mutator
{
    public function __construct(
        private Store $store,
    ) {
    }

    public function update(OriginSourceInterface $source, SourceRequestInterface $request): SourceInterface
    {
        $isMutated = false;
        if ($source instanceof FileSource && $request instanceof FileSourceRequest) {
            $source->setLabel($request->getLabel());
            $isMutated = true;
        }

        if ($source instanceof GitSource && $request instanceof GitSourceRequest) {
            $source->setHostUrl($request->getHostUrl());
            $source->setPath($request->getPath());
            $source->setCredentials($request->getCredentials());
            $isMutated = true;
        }

        if (true === $isMutated) {
            $this->store->add($source);
        }

        return $source;
    }
}
