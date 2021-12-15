<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Security\Authenticator;
use App\Services\UserTokenVerifier;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use webignition\ObjectReflector\ObjectReflector;

class AuthenticatorTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    private const USER_TOKEN =
        'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.' .
        'eyJlbWFpbCI6InVzZXJAZXhhbXBsZS5jb20iLCJzdWIiOiIwMUZQWkdIQUc2NUUwTjlBUldHNlkxUkgzNCIsImF1ZCI6WyJhcGkiXX0.' .
        'hMGV5MJexFIDIuh5gsqkhJ7C3SDQGnOW7sZVS5b6X08';

    private const USER_ID = '01FPZGHAG65E0N9ARWG6Y1RH34';

    private Authenticator $authenticator;

    protected function setUp(): void
    {
        parent::setUp();

        $authenticator = self::getContainer()->get(Authenticator::class);
        \assert($authenticator instanceof Authenticator);
        $this->authenticator = $authenticator;
    }

    /**
     * @dataProvider authenticateFailureDataProvider
     */
    public function testAuthenticateFailure(?string $userToken): void
    {
        $requestHeaders = [];
        if (is_string($userToken)) {
            $requestHeaders['HTTP_AUTHORIZATION'] = 'Bearer ' . $userToken;
        }

        $userTokenVerifier = $this->createUserTokenVerifier((string) $userToken, null);
        ObjectReflector::setProperty(
            $this->authenticator,
            Authenticator::class,
            'userTokenVerifier',
            $userTokenVerifier
        );

        self::expectExceptionObject(
            new CustomUserMessageAuthenticationException('Invalid user token')
        );

        $this->authenticator->authenticate(new Request(server: $requestHeaders));
    }

    /**
     * @return array<mixed>
     */
    public function authenticateFailureDataProvider(): array
    {
        return [
            'no user token' => [
                'userToken' => null,
                'request' => new Request(),
            ],
            'invalid user token' => [
                'userToken' => 'invalid-token',
            ],
        ];
    }

    public function testAuthenticateSuccess(): void
    {
        $userTokenVerifier = $this->createUserTokenVerifier(self::USER_TOKEN, self::USER_ID);
        ObjectReflector::setProperty(
            $this->authenticator,
            Authenticator::class,
            'userTokenVerifier',
            $userTokenVerifier
        );

        $passport = $this->authenticator->authenticate(new Request(server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . self::USER_TOKEN
        ]));
        $expectedPassport = new SelfValidatingPassport(new UserBadge(self::USER_ID));

        self::assertEquals($expectedPassport, $passport);
    }

    private function createUserTokenVerifier(string $token, ?string $returnValue): UserTokenVerifier
    {
        $verifier = \Mockery::mock(UserTokenVerifier::class);
        $verifier
            ->shouldReceive('verify')
            ->with($token)
            ->andReturn($returnValue)
        ;

        return $verifier;
    }
}
