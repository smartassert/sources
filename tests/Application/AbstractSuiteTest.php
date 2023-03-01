<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Request\FileSourceRequest;

abstract class AbstractSuiteTest extends AbstractApplicationTest
{
    protected string $sourceId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sourceId = $this->createSource(self::USER_1_EMAIL);
    }

    protected function createSource(string $userEmail, ?string $label = null): string
    {
        $label = is_string($label) ? $label : md5((string) rand());

        $createSourceResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken($userEmail),
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
