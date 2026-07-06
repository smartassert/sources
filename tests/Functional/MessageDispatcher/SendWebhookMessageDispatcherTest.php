<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageDispatcher;

use App\Entity\SerializedSuite;
use App\Entity\Suite;
use App\Enum\SerializedSuite\State;
use App\Event\SerializedSuiteStateChangedEvent;
use App\MessageDispatcher\SendWebhookMessageDispatcher;
use App\Services\EntityIdFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Webhook\Messenger\SendWebhookMessage;

class SendWebhookMessageDispatcherTest extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    private InMemoryTransport $messengerTransport;
    private SendWebhookMessageDispatcher $dispatcher;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $messengerTransport = self::getContainer()->get('messenger.transport.async');
        \assert($messengerTransport instanceof InMemoryTransport);
        $this->messengerTransport = $messengerTransport;

        $dispatcher = self::getContainer()->get(SendWebhookMessageDispatcher::class);
        \assert($dispatcher instanceof SendWebhookMessageDispatcher);
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param array<mixed> $expectedRemoteEventPayload
     */
    #[DataProvider('dispatchDataProvider')]
    public function testDispatch(
        SerializedSuite $serializedSuite,
        string $expectedRemoteEventName,
        array $expectedRemoteEventPayload,
    ): void {
        $event = new SerializedSuiteStateChangedEvent($serializedSuite, State::REQUESTED);

        $this->dispatcher->dispatch($event);

        $envelopes = $this->messengerTransport->getSent();

        $message = $envelopes[0]->getMessage();
        self::assertInstanceOf(SendWebhookMessage::class, $message);

        $subscriber = $message->getSubscriber();
        self::assertSame($serializedSuite->getNotifyUrl(), $subscriber->getUrl());
        self::assertSame(
            $this->getContainer()->getParameter('notify_secret'),
            $subscriber->getSecret()
        );

        $remoteEvent = $message->getEvent();
        self::assertSame($expectedRemoteEventName, $remoteEvent->getName());
        self::assertEquals($expectedRemoteEventPayload, $remoteEvent->getPayload());
    }

    /**
     * @return array<mixed>
     */
    public static function dispatchDataProvider(): array
    {
        $serializedSuite = new SerializedSuite(
            new EntityIdFactory()->create(),
            new Suite(new EntityIdFactory()->create()),
            'https://example.com/nofity',
            []
        );

        return [
            'default' => [
                'serializedSuite' => $serializedSuite,
                'expectedRemoteEventName' => 'serialized_suite.state_changed',
                'expectedRemoteEventPayload' => $serializedSuite->toArray(),
            ],
        ];
    }
}
