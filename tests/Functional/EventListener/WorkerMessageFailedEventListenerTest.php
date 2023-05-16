<?php

declare(strict_types=1);

namespace App\Tests\Functional\EventListener;

use App\MessageFailureHandler\WorkerMessageFailedEventHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Process\Process;

class WorkerMessageFailedEventListenerTest extends TestCase
{
    public function testFoo(): void
    {
        $eventClass = WorkerMessageFailedEvent::class;
        $command = sprintf('php bin/console debug:event-dispatcher "%s" --format=json', $eventClass);

        $process = Process::fromShellCommandline($command);
        $exitCode = $process->run();

        self::assertSame(0, $exitCode, sprintf('Command "%s" failed with exit code "%d"', $command, $exitCode));

        $output = $process->getOutput();
        $data = json_decode($output, true);
        self::assertIsArray($data, sprintf('Command output "%s" does not json_decode to an array', $output));

        $listenerFound = false;

        $expectedListenerConfiguration = [
            'type' => 'function',
            'name' => '__invoke',
            'class' => WorkerMessageFailedEventHandler::class,
        ];

        foreach ($data as $eventListenerConfiguration) {
            if (is_array($eventListenerConfiguration)) {
                $comparator = $eventListenerConfiguration;
                unset($comparator['priority']);

                if ($comparator === $expectedListenerConfiguration) {
                    $listenerFound = true;
                }
            }
        }

        self::assertTrue(
            $listenerFound,
            sprintf(
                '"%s::%s" not found as listener for "%s"',
                $expectedListenerConfiguration['class'],
                $expectedListenerConfiguration['name'],
                $eventClass
            )
        );
    }
}
