<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Tests\Services\Asserter\Response\BodyContentAsserter;
use App\Tests\Services\Asserter\Response\HeaderAsserter;
use App\Tests\Services\Asserter\Response\JsonResponseAsserter;
use App\Tests\Services\Asserter\Response\YamlResponseAsserter;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class ResponseAsserter
{
    public function assertForbiddenResponse(ResponseInterface $response): void
    {
        Assert::assertSame(403, $response->getStatusCode());
    }

    public function assertNotFoundResponse(ResponseInterface $response): void
    {
        Assert::assertSame(404, $response->getStatusCode());
    }

    /**
     * @param array<mixed> $expectedData
     */
    public function assertInvalidRequestJsonResponse(ResponseInterface $response, array $expectedData): void
    {
        (new JsonResponseAsserter(Response::HTTP_BAD_REQUEST, $expectedData))
            ->assert($response)
        ;
    }

    public function assertSuccessfulResponseWithNoBody(ResponseInterface $response): void
    {
        (new Asserter\Response\ResponseAsserter(Response::HTTP_OK))
            ->addBodyAsserter(new BodyContentAsserter(''))
            ->addHeaderAsserter(new HeaderAsserter(['content-type' => '']))
            ->assert($response)
        ;
    }

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
    public function assertMethodNotAllowedResponse(ResponseInterface $response, array $expectedData): void
    {
        (new JsonResponseAsserter(Response::HTTP_METHOD_NOT_ALLOWED, $expectedData))
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
