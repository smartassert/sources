<?php

declare(strict_types=1);

namespace App\Services\SourceRepository;

use App\Exception\SourceRepositoryReaderNotFoundException;
use App\Model\SourceRepositoryInterface;
use App\Services\SourceRepository\Reader\Provider;
use App\Services\YamlFileCollection\Factory as YamlFileProviderFactory;
use SmartAssert\YamlFile\Collection\Serializer as YamlFileCollectionSerializer;
use SmartAssert\YamlFile\Exception\Collection\SerializeException;

class Serializer
{
    public function __construct(
        private Provider $readerProvider,
        private YamlFileProviderFactory $yamlFileProviderFactory,
        private YamlFileCollectionSerializer $yamlFileCollectionSerializer,
    ) {
    }

    /**
     * @param array<int, string> $manifestPaths
     *
     * @throws SourceRepositoryReaderNotFoundException
     * @throws SerializeException
     */
    public function serialize(
        SourceRepositoryInterface $sourceRepository,
        array $manifestPaths = [],
    ): string {
        return $this->yamlFileCollectionSerializer->serializeUnreliableProvider(
            $this->yamlFileProviderFactory->create(
                $this->readerProvider->find($sourceRepository),
                $sourceRepository->getRepositoryPath(),
                $manifestPaths
            )
        );
    }
}
