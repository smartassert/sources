<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\RunSource;
use App\Enum\RunSource\State;
use App\Exception\File\WriteException;
use App\Exception\GitRepositoryException;
use App\Exception\MessageHandler\PrepareException;
use App\Exception\SourceRead\SourceReadExceptionInterface;
use App\Message\Prepare;
use App\Repository\SourceRepository;
use App\Services\RunSourceSerializer;
use App\Services\Source\Store;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PrepareHandler
{
    public function __construct(
        private SourceRepository $sourceRepository,
        private Store $sourceStore,
        private RunSourceSerializer $runSourceSerializer,
    ) {
    }

    /**
     * @throws PrepareException
     */
    public function __invoke(Prepare $message): void
    {
        $runSource = $this->sourceRepository->find($message->getSourceId());
        if (
            !$runSource instanceof RunSource
            || null === $runSource->getParent()
            || !in_array($runSource->getState(), [State::REQUESTED, State::PREPARING_HALTED])
        ) {
            return;
        }

        $runSource->setState(State::PREPARING_RUNNING);
        $this->sourceStore->add($runSource);

        try {
            $this->runSourceSerializer->write($runSource);
        } catch (WriteException | SourceReadExceptionInterface | GitRepositoryException $e) {
            $runSource->setState(State::PREPARING_HALTED);
            $this->sourceStore->add($runSource);

            throw new PrepareException($e);
        }

        $runSource->setState(State::PREPARED);
        $this->sourceStore->add($runSource);
    }
}
