<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Enum\SerializedSuite\State;
use App\Request\GitSourceRequest;
use App\Request\SuiteRequest;
use App\Tests\Application\AbstractApplicationTest;
use Symfony\Component\Uid\Ulid;

class SerializeSuiteFailureTest extends AbstractApplicationTest
{
    use GetClientAdapterTrait;
    use WaitUntilSerializedSuiteStateTrait;

    public function testSerializeSuiteFailure(): void
    {
        $label = md5((string) rand());
        $hostUrlPort = rand(9000, 9999);
        $hostUrl = 'https://app:' . $hostUrlPort . '/repository.git';
        $path = '/';

        $createSourceResponse = $this->applicationClient->makeCreateGitSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                GitSourceRequest::PARAMETER_LABEL => $label,
                GitSourceRequest::PARAMETER_HOST_URL => $hostUrl,
                GitSourceRequest::PARAMETER_PATH => $path
            ]
        );

        $createSourceResponseData = json_decode($createSourceResponse->getBody()->getContents(), true);
        \assert(is_array($createSourceResponseData));
        $sourceId = $createSourceResponseData['id'] ?? null;
        $createSuiteResponse = $this->applicationClient->makeCreateSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            [
                SuiteRequest::PARAMETER_SOURCE_ID => $sourceId,
                SuiteRequest::PARAMETER_LABEL => md5((string) rand()),
                SuiteRequest::PARAMETER_TESTS => [
                    'test1.yaml',
                    'test2.yaml',
                ],
            ]
        );

        $createSuiteResponseData = json_decode($createSuiteResponse->getBody()->getContents(), true);
        \assert(is_array($createSuiteResponseData));
        $suiteId = $createSuiteResponseData['id'] ?? null;

        $serializedSuiteId = (string) (new Ulid());

        $createSerializedSuiteResponse = $this->applicationClient->makeCreateSerializedSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $serializedSuiteId,
            $suiteId,
            []
        );

        $this->waitUntilSuiteStateIs($serializedSuiteId, State::FAILED);

        $getSerializedSuiteResponse = $this->applicationClient->makeGetSerializedSuiteRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $serializedSuiteId
        );

        $getSerializedSuiteResponseData = json_decode(
            $getSerializedSuiteResponse->getBody()->getContents(),
            true
        );
        \assert(is_array($getSerializedSuiteResponseData));
        self::assertSame('failed', $getSerializedSuiteResponseData['state']);
        self::assertSame('git/clone', $getSerializedSuiteResponseData['failure_reason']);
        self::assertSame(
            sprintf(
                'fatal: unable to access \'%s/\': Failed to connect to app port %d: Connection refused',
                $hostUrl,
                $hostUrlPort
            ),
            $getSerializedSuiteResponseData['failure_message']
        );
    }
}
