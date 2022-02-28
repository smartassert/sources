<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\AbstractSource;
use App\Entity\SourceInterface;
use webignition\ObjectReflector\ObjectReflector;

class SourceUserIdMutator
{
    public const AUTHENTICATED_USER_ID_PLACEHOLDER = '{{ authenticated_user_id }}';

    public function __construct(
        private readonly AuthenticationConfiguration $authenticationConfiguration,
    ) {
    }

    public function setSourceUserId(SourceInterface $source): SourceInterface
    {
        if ($source instanceof AbstractSource && self::AUTHENTICATED_USER_ID_PLACEHOLDER == $source->getUserId()) {
            ObjectReflector::setProperty(
                $source,
                AbstractSource::class,
                'userId',
                $this->authenticationConfiguration->authenticatedUserId
            );
        }

        return $source;
    }

    /**
     * @param array<int, array<mixed>> $sourceDataCollection
     *
     * @return array<int, array<mixed>>
     */
    public function setSourceDataCollectionUserId(array $sourceDataCollection): array
    {
        foreach ($sourceDataCollection as $sourceIndex => $sourceData) {
            $sourceDataCollection[$sourceIndex] = $this->setSourceDataUserId($sourceData);
        }

        return $sourceDataCollection;
    }

    /**
     * @param array<mixed> $sourceData
     *
     * @return array<mixed>
     */
    public function setSourceDataUserId(array $sourceData): array
    {
        if (
            array_key_exists('user_id', $sourceData)
            && self::AUTHENTICATED_USER_ID_PLACEHOLDER == $sourceData['user_id']
        ) {
            $sourceData['user_id'] = $this->authenticationConfiguration->authenticatedUserId;
        }

        return $sourceData;
    }
}
