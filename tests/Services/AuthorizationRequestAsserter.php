<?php

declare(strict_types=1);

namespace App\Tests\Services;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use SmartAssert\UsersClient\Routes;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class AuthorizationRequestAsserter
{
    public function __construct(
        HandlerStack $handlerStack,
        private HttpHistoryContainer $httpHistoryContainer,
        private string $usersSecurityBundleBaseUrl,
    ) {
        $handlerStack->push(Middleware::history($this->httpHistoryContainer), 'history');
    }

    public function assertAuthorizationRequestIsMade(): void
    {
        $request = $this->httpHistoryContainer->getTransactions()->getRequests()->getLast();
        TestCase::assertInstanceOf(RequestInterface::class, $request);

        $expectedUrl = $this->usersSecurityBundleBaseUrl . Routes::DEFAULT_VERIFY_API_TOKEN_PATH;

        TestCase::assertSame($expectedUrl, (string) $request->getUri());
        TestCase::assertSame(
            ApplicationClient::AUTH_HEADER_VALUE,
            $request->getHeaderLine(ApplicationClient::AUTH_HEADER_KEY)
        );
    }
}