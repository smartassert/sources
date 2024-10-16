<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Tests\Services\Asserter\Response\JsonResponseAsserter;
use App\Tests\Services\Asserter\Response\YamlResponseAsserter;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class ResponseAsserter
{
    /**
     * @param array<mixed> $expectedData
     */
    public function assertSuccessfulJsonResponse(ResponseInterface $response, array $expectedData): void
    {
        (new JsonResponseAsserter(Response::HTTP_OK, $expectedData))
            ->assert($response)
        ;
    }

    public function assertReadSourceSuccessResponse(ResponseInterface $response, string $expectedBody): void
    {
        (new YamlResponseAsserter(Response::HTTP_OK, $expectedBody))
            ->assert($response)
        ;
    }

    /**
     * @param array<mixed> $expectedData
     */
    public function assertSerializeSuiteSuccessResponse(ResponseInterface $response, array $expectedData): void
    {
        (new JsonResponseAsserter(Response::HTTP_ACCEPTED, $expectedData))
            ->assert($response)
        ;
    }

    public function assertReadSerializedSuiteSuccessResponse(ResponseInterface $response, string $expectedBody): void
    {
        (new YamlResponseAsserter(Response::HTTP_OK, $expectedBody))
            ->assert($response)
        ;
    }
}
