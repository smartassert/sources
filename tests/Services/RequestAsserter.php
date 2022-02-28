<?php

declare(strict_types=1);

namespace App\Tests\Services;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use SmartAssert\UsersClient\Routes;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class RequestAsserter
{
    public function __construct(
        HandlerStack $handlerStack,
        private HttpHistoryContainer $httpHistoryContainer,
        private string $usersSecurityBundleBaseUrl,
        private readonly AuthenticationConfiguration $authenticationConfiguration,
    ) {
        $handlerStack->push(Middleware::history($this->httpHistoryContainer), 'history');
    }

    public function assertAuthorizationRequestIsMade(
        ?string $expectedToken = null,
    ): void {
        $expectedToken = is_string($expectedToken)
            ? $expectedToken
            : $this->authenticationConfiguration->validToken;

        $request = $this->httpHistoryContainer->getTransactions()->getRequests()->getLast();
        TestCase::assertInstanceOf(RequestInterface::class, $request);

        $expectedUrl = $this->usersSecurityBundleBaseUrl . Routes::DEFAULT_VERIFY_API_TOKEN_PATH;
        TestCase::assertSame($expectedUrl, (string) $request->getUri());

        $expectedAuthorizationHeader = $this->authenticationConfiguration->headerValuePrefix . $expectedToken;
        $authorizationHeader = $request->getHeaderLine($this->authenticationConfiguration->headerName);

        TestCase::assertSame($expectedAuthorizationHeader, $authorizationHeader);
    }
}
