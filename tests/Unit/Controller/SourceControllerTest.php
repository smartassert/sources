<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\SourceController;
use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Message\Prepare;
use App\Security\UserSourceAccessChecker;
use App\Services\RequestValidator;
use App\Services\ResponseFactory;
use App\Services\RunSourceFactory;
use App\Tests\Model\UserId;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class SourceControllerTest extends WebTestCase
{
    public function testPrepareSuccessDispatchesPrepareMessage(): void
    {
        $userId = UserId::create();

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

        $userSourceAccessChecker = \Mockery::mock(UserSourceAccessChecker::class);
        $userSourceAccessChecker
            ->shouldReceive('denyAccessUnlessGranted')
            ->with($fileSource)
        ;

        (new SourceController(
            \Mockery::mock(ResponseFactory::class),
            $userSourceAccessChecker,
            \Mockery::mock(RequestValidator::class),
        ))
            ->prepare($request, $fileSource, $messageBus, $runSourceFactory)
        ;
    }
}
