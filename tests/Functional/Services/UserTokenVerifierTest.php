<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Services\UserTokenVerifier;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class UserTokenVerifierTest extends WebTestCase
{
    private const USER_TOKEN =
        'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.' .
        'eyJlbWFpbCI6InVzZXJAZXhhbXBsZS5jb20iLCJzdWIiOiIwMUZQWkdIQUc2NUUwTjlBUldHNlkxUkgzNCIsImF1ZCI6WyJhcGkiXX0.' .
        'hMGV5MJexFIDIuh5gsqkhJ7C3SDQGnOW7sZVS5b6X08';

    private const USER_ID = '01FPZGHAG65E0N9ARWG6Y1RH34';

    private UserTokenVerifier $verifier;
    private MockHandler $mockHandler;
    private HttpHistoryContainer $httpHistoryContainer;

    protected function setUp(): void
    {
        parent::setUp();

        $verifier = self::getContainer()->get(UserTokenVerifier::class);
        \assert($verifier instanceof UserTokenVerifier);
        $this->verifier = $verifier;

        $mockHandler = self::getContainer()->get(MockHandler::class);
        \assert($mockHandler instanceof MockHandler);
        $this->mockHandler = $mockHandler;

        $httpHistoryContainer = self::getContainer()->get(HttpHistoryContainer::class);
        \assert($httpHistoryContainer instanceof HttpHistoryContainer);
        $this->httpHistoryContainer = $httpHistoryContainer;

        $handlerStack = self::getContainer()->get(HandlerStack::class);
        if ($handlerStack instanceof HandlerStack) {
            $handlerStack->push(Middleware::history($this->httpHistoryContainer));
        }
    }

    /**
     * @dataProvider verifyDataProvider
     */
    public function testVerify(
        string $userToken,
        ResponseInterface|\Throwable $userServiceResponse,
        string $expectedAuthorizationHeader,
        ?string $expectedReturnValue
    ): void {
        $this->mockHandler->append($userServiceResponse);

        $verifyReturnValue = $this->verifier->verify($userToken);
        self::assertSame($expectedReturnValue, $verifyReturnValue);

        $sentRequest = $this->httpHistoryContainer->getTransactions()->getRequests()->getLast();
        self::assertInstanceOf(RequestInterface::class, $sentRequest);
        \assert($sentRequest instanceof RequestInterface);

        self::assertSame('GET', $sentRequest->getMethod());

        $authorizationHeader = $sentRequest->getHeaderLine('authorization');
        self::assertSame($expectedAuthorizationHeader, $authorizationHeader);
    }

    /**
     * @return array<mixed>
     */
    public function verifyDataProvider(): array
    {
        $userToken = self::USER_TOKEN;
        $expectedAuthorizationHeader = 'Bearer ' . self::USER_TOKEN;

        return [
            'unverified, HTTP 401' => [
                'userToken' => $userToken,
                'userServiceResponse' => new Response(401),
                'expectedAuthorizationHeader' => $expectedAuthorizationHeader,
                'expectedReturnValue' => null,
            ],
            'unverified, HTTP 500' => [
                'userToken' => $userToken,
                'userServiceResponse' => new Response(500),
                'expectedAuthorizationHeader' => $expectedAuthorizationHeader,
                'expectedReturnValue' => null,
            ],
            'unverified, curl 28 (connection timeout)' => [
                'userToken' => $userToken,
                'userServiceResponse' => \Mockery::mock(ConnectException::class),
                'expectedAuthorizationHeader' => $expectedAuthorizationHeader,
                'expectedReturnValue' => null,
            ],
            'verified' => [
                'userToken' => self::USER_TOKEN,
                'userServiceResponse' => new Response(200, [], self::USER_ID),
                'expectedAuthorizationHeader' => $expectedAuthorizationHeader,
                'expectedReturnValue' => self::USER_ID,
            ],
        ];
    }
}
