<?php

declare(strict_types=1);

namespace App\Services;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

class UserTokenVerifier
{
    public function __construct(
        private RequestFactoryInterface $requestFactory,
        private HttpClientInterface $httpClient,
        private string $verificationUrl,
    ) {
    }

    public function verify(string $userToken): ?string
    {
        $request = $this->requestFactory->createRequest('GET', $this->verificationUrl);
        $request = $request->withHeader('Authorization', 'Bearer ' . $userToken);

        try {
            $response = $this->httpClient->sendRequest($request);

            if (200 !== $response->getStatusCode()) {
                return null;
            }

            return $response->getBody()->getContents();
        } catch (ClientExceptionInterface) {
            return null;
        }
    }
}
