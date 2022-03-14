<?php

declare(strict_types=1);

namespace App\Services\SourceRepository;

use App\Exception\SourceRepositoryReaderNotFoundException;
use App\Model\SourceRepositoryInterface;
use App\Services\SourceRepository\Reader\Provider;
use App\Services\YamlFileProvider\Factory as YamlFileProviderFactory;
use SmartAssert\YamlFile\Collection\Serializer as YamlFileCollectionSerializer;
use SmartAssert\YamlFile\Exception\ProvisionException;

class Serializer
{
    public function __construct(
        private Provider $readerProvider,
        private YamlFileProviderFactory $yamlFileProviderFactory,
        private YamlFileCollectionSerializer $yamlFileCollectionSerializer,
    ) {
    }

    /**
     * @throws SourceRepositoryReaderNotFoundException
     * @throws ProvisionException
     */
    public function serialize(SourceRepositoryInterface $sourceRepository): string
    {
        return $this->yamlFileCollectionSerializer->serialize(
            $this->yamlFileProviderFactory->create(
                $this->readerProvider->find($sourceRepository),
                $sourceRepository->getRepositoryPath()
            )
        );
    }
}
