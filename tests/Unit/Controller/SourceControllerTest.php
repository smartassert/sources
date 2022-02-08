<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\SourceController;
use App\Entity\FileSource;
use App\Entity\RunSource;
use App\Message\Prepare;
use App\Repository\SourceRepository;
use App\Services\RunSourceFactory;
use App\Services\Source\Mutator;
use App\Services\Source\Store;
use App\Tests\Model\UserId;
use SmartAssert\UsersSecurityBundle\Security\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class SourceControllerTest extends WebTestCase
{
    public function testPrepareSuccessDispatchesPrepareMessage(): void
    {
        $userId = UserId::create();
        $user = new User($userId);

        $fileSource = new FileSource($userId, 'file source label');
        $runSource = new RunSource($fileSource);

        $sourceStore = \Mockery::mock(Store::class);
        $sourceStore
            ->shouldReceive('add')
            ->withArgs(function (RunSource $passedRunSource) use ($runSource) {
                self::assertSame($runSource, $passedRunSource);

                return true;
            })
        ;

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

        $controller = new SourceController(
            $sourceStore,
            \Mockery::mock(Mutator::class),
            \Mockery::mock(SourceRepository::class),
            $messageBus,
            $runSourceFactory,
        );

        $controller->prepare($request, $fileSource, $user);
    }
}
