<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\SourceController;
use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Message\Prepare;
use App\Services\InvalidRequestResponseFactory;
use App\Services\ResponseFactory;
use App\Services\RunSourceFactory;
use App\Tests\Model\UserId;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SourceControllerTest extends WebTestCase
{
    public function testPrepareSuccessDispatchesPrepareMessage(): void
    {
        $userId = UserId::create();
        $user = new User($userId);

        $fileSource = new FileSource($userId, 'file source label');
        $runSource = new RunSource($fileSource);

        $createdMessage = null;
        $messageBus = \Mockery::mock(MessageBusInterface::class);
        $messageBus
            ->shouldReceive('dispatch')
            ->withArgs(function (Prepare $message) use ($runSource, &$createdMessage) {
                self::assertSame($runSource->getId(), $message->getSourceId());
                $createdMessage = $message;

                return true;
            })
            ->andReturnUsing(function () use (&$createdMessage) {
                self::assertInstanceOf(Prepare::class, $createdMessage);

                return new Envelope($createdMessage);
            })
        ;

        $request = new Request();

        $runSourceFactory = \Mockery::mock(RunSourceFactory::class);
        $runSourceFactory
            ->shouldReceive('createFromRequest')
            ->with($fileSource, $request)
            ->andReturn($runSource)
        ;

        $userToken = \Mockery::mock(TokenInterface::class);
        $userToken
            ->shouldReceive('getUser')
            ->andReturn($user)
        ;

        $tokenStorage = \Mockery::mock(TokenStorageInterface::class);
        $tokenStorage
            ->shouldReceive('getToken')
            ->andReturn($userToken)
        ;

        (new SourceController(
            \Mockery::mock(ResponseFactory::class),
            \Mockery::mock(ValidatorInterface::class),
            \Mockery::mock(InvalidRequestResponseFactory::class),
            $tokenStorage,
        ))
            ->prepare($request, $fileSource, $messageBus, $runSourceFactory)
        ;
    }
}
