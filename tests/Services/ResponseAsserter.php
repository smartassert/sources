<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Tests\Services\Asserter\Response\YamlResponseAsserter;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class ResponseAsserter
{
    public function assertReadSerializedSuiteSuccessResponse(ResponseInterface $response, string $expectedBody): void
    {
        (new YamlResponseAsserter(Response::HTTP_OK, $expectedBody))
            ->assert($response)
        ;
    }
}
