<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Entity\RunSource;
use App\Enum\RunSource\State;
use App\Exception\DirectoryDuplicationException;
use App\Exception\UserGitRepositoryException;
use App\Message\Prepare;
use App\Repository\RunSourceRepository;
use App\Repository\SourceRepository;
use App\Services\RunSourcePreparer;
use App\Services\Source\Store;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PrepareHandler
{
    public function __construct(
        private SourceRepository $sourceRepository,
        private RunSourceRepository $runSourceRepository,
        private Store $sourceStore,
        private RunSourcePreparer $runSourcePreparer,
    ) {
    }

    /**
     * @throws DirectoryDuplicationException
     * @throws UserGitRepositoryException
     */
    public function __invoke(Prepare $message): void
    {
        $source = $this->sourceRepository->find($message->getSourceId());
        if (!($source instanceof FileSource || $source instanceof GitSource)) {
            return;
        }

        $runSource = $this->runSourceRepository->findByParent($source);
        if (!$runSource instanceof RunSource) {
            $runSource = new RunSource($source, $message->getParameters());
            $this->sourceStore->add($runSource);
        }

        if (!in_array($runSource->getState(), [State::REQUESTED, State::PREPARING_HALTED])) {
            return;
        }

        $runSource->setState(State::PREPARING_RUNNING);
        $this->sourceStore->add($runSource);

        $this->runSourcePreparer->prepare($runSource);

        $runSource->setState(State::PREPARED);
        $this->sourceStore->add($runSource);
    }
}
