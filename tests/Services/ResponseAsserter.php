<?php

declare(strict_types=1);

namespace App\Tests\Services;

//use App\Entity\User;
//use App\Security\AudienceClaimInterface;
//use App\Security\TokenInterface;
//use App\Tests\Services\Asserter\ResponseAsserter\ArrayBodyAsserter;
//use App\Tests\Services\Asserter\ResponseAsserter\JsonResponseAsserter;
//use App\Tests\Services\Asserter\ResponseAsserter\JwtTokenBodyAsserterFactory;
//use App\Tests\Services\Asserter\ResponseAsserter\TextPlainBodyAsserter;
//use App\Tests\Services\Asserter\ResponseAsserter\TextPlainResponseAsserter;
use App\Tests\Services\Asserter\Response\ArrayBodyAsserter;
use App\Tests\Services\Asserter\Response\JsonResponseAsserter;
use App\Tests\Services\Asserter\Response\ResponseAsserter as FooResponseAsserter;
use App\Tests\Services\Asserter\Response\YamlResponseAsserter;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class ResponseAsserter
{
//    public function __construct(
//        private JwtTokenBodyAsserterFactory $jwtTokenBodyAsserterFactory,
//    ) {
//    }

    public function assertUnauthorizedResponse(ResponseInterface $response): void
    {
        Assert::assertSame(401, $response->getStatusCode());
        $response->getBody()->rewind();
        Assert::assertSame('', $response->getBody()->getContents());
    }

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
    public function assertAddFileInvalidRequestResponse(ResponseInterface $response, array $expectedData): void
    {
        (new JsonResponseAsserter(Response::HTTP_BAD_REQUEST))
            ->addBodyAsserter(new ArrayBodyAsserter($expectedData))
            ->assert($response)
        ;
    }

    public function assertAddFileSuccessResponse(ResponseInterface $response): void
    {
        (new FooResponseAsserter(Response::HTTP_OK))
            ->assert($response)
        ;
    }

    /**
     * @param array<mixed> $expectedData
     */
    public function assertRemoveFileInvalidRequestResponse(ResponseInterface $response, array $expectedData): void
    {
        (new JsonResponseAsserter(Response::HTTP_BAD_REQUEST))
            ->addBodyAsserter(new ArrayBodyAsserter($expectedData))
            ->assert($response)
        ;
    }

    public function assertRemoveFileSuccessResponse(ResponseInterface $response): void
    {
        (new FooResponseAsserter(Response::HTTP_OK))
            ->assert($response)
        ;
    }

    /**
     * @param array<mixed> $expectedData
     */
    public function assertCreateSourceInvalidRequestResponse(ResponseInterface $response, array $expectedData): void
    {
        (new JsonResponseAsserter(Response::HTTP_BAD_REQUEST))
            ->addBodyAsserter(new ArrayBodyAsserter($expectedData))
            ->assert($response)
        ;
    }

    /**
     * @param array<mixed> $expectedData
     */
    public function assertCreateSourceSuccessResponse(ResponseInterface $response, array $expectedData): void
    {
        (new JsonResponseAsserter(Response::HTTP_OK))
            ->addBodyAsserter(new ArrayBodyAsserter($expectedData))
            ->assert($response)
        ;
    }

    /**
     * @param array<mixed> $expectedData
     */
    public function assertListSourcesSuccessResponse(ResponseInterface $response, array $expectedData): void
    {
        (new JsonResponseAsserter(Response::HTTP_OK))
            ->addBodyAsserter(new ArrayBodyAsserter($expectedData))
            ->assert($response)
        ;
    }

    /**
     * @param array<mixed> $expectedData
     */
    public function assertGetSourceSuccessResponse(ResponseInterface $response, array $expectedData): void
    {
        (new JsonResponseAsserter(Response::HTTP_OK))
            ->addBodyAsserter(new ArrayBodyAsserter($expectedData))
            ->assert($response)
        ;
    }

    /**
     * @param array<mixed> $expectedData
     */
    public function assertUpdateSourceSuccessResponse(ResponseInterface $response, array $expectedData): void
    {
        (new JsonResponseAsserter(Response::HTTP_OK))
            ->addBodyAsserter(new ArrayBodyAsserter($expectedData))
            ->assert($response)
        ;
    }

    /**
     * @param array<mixed> $expectedData
     */
    public function assertUpdateSourceInvalidRequestResponse(ResponseInterface $response, array $expectedData): void
    {
        (new JsonResponseAsserter(Response::HTTP_BAD_REQUEST))
            ->addBodyAsserter(new ArrayBodyAsserter($expectedData))
            ->assert($response)
        ;
    }

    public function assertDeleteSourceSuccessResponse(ResponseInterface $response): void
    {
        (new FooResponseAsserter(Response::HTTP_OK))
            ->assert($response)
        ;
    }

    /**
     * @param array<mixed> $expectedData
     */
    public function assertPrepareSourceSuccessResponse(ResponseInterface $response, array $expectedData): void
    {
        (new JsonResponseAsserter(Response::HTTP_ACCEPTED))
            ->addBodyAsserter(new ArrayBodyAsserter($expectedData))
            ->assert($response)
        ;
    }

    public function assertReadSourceSuccessResponse(ResponseInterface $response, string $expectedBody): void
    {
        (new YamlResponseAsserter(Response::HTTP_OK, $expectedBody))
            ->assert($response)
        ;
    }
}
