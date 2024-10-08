<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Request\FileSourceRequest;
use App\Tests\Services\StringFactory;

abstract class AbstractSuiteTest extends AbstractApplicationTest
{
    protected string $sourceId;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceId = $this->createSource(self::USER_1_EMAIL);
    }

    /**
     * @param non-empty-string $userEmail
     */
    protected function createSource(string $userEmail, ?string $label = null): string
    {
        $label = is_string($label) ? $label : StringFactory::createRandom();

        $createSourceResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$apiTokens->get($userEmail),
            [
                FileSourceRequest::PARAMETER_LABEL => $label,
            ]
        );

        $createSourceResponseData = json_decode($createSourceResponse->getBody()->getContents(), true);
        \assert(is_array($createSourceResponseData));
        $sourceId = $createSourceResponseData['id'] ?? null;
        \assert(is_string($sourceId));

        return $sourceId;
    }
}
