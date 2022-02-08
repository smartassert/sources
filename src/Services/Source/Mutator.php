<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\SourceInterface;
use App\Request\FooFileSourceRequest;
use App\Request\FooGitSourceRequest;
use App\Request\SourceRequestInterface;

class Mutator
{
    public function __construct(
        private Store $store,
    ) {
    }

    public function update(SourceInterface $source, SourceRequestInterface $sourceRequest): SourceInterface
    {
        $isMutated = false;
        if ($source instanceof FileSource && $sourceRequest instanceof FooFileSourceRequest) {
            $source->setLabel($sourceRequest->getParameter(FooFileSourceRequest::PARAMETER_LABEL));
            $isMutated = true;
        }

        if ($source instanceof GitSource && $sourceRequest instanceof FooGitSourceRequest) {
            $source->setHostUrl($sourceRequest->getParameter(FooGitSourceRequest::PARAMETER_HOST_URL));
            $source->setPath($sourceRequest->getParameter(FooGitSourceRequest::PARAMETER_PATH));
            $source->setCredentials($sourceRequest->getParameter(FooGitSourceRequest::PARAMETER_CREDENTIALS));
            $isMutated = true;
        }

        if (true === $isMutated) {
            $this->store->add($source);
        }

        return $source;
    }
}
