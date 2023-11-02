<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Enum\Source\Type;
use App\Request\FileSourceRequest;
use App\Request\OriginSourceRequest;

abstract class AbstractSuiteTest extends AbstractApplicationTest
{
    protected string $sourceId;

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
        $label = is_string($label) ? $label : md5((string) rand());

        $createSourceResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$apiTokens->get($userEmail),
            [
                OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
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
