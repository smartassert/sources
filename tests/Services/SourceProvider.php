<?php

declare(strict_types=1);

namespace App\Tests\Services;

use App\Entity\RunSource;
use App\Entity\SourceInterface;
use App\Enum\RunSource\FailureReason;
use App\Repository\SourceRepository;
use App\Services\EntityIdFactory;
use App\Tests\Model\UserId;

class SourceProvider
{
    public const FILE_WITHOUT_RUN_SOURCE = 'file_without_run_source';
    public const FILE_WITH_RUN_SOURCE = 'file_with_run_source';
    public const GIT_WITH_CREDENTIALS_WITH_RUN_SOURCE = 'git_with_credentials_with_run_source';
    public const GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE = 'git_without_credentials_without_run_source';
    public const RUN_WITH_FILE_PARENT = 'run_with_file_parent';
    public const RUN_WITH_DIFFERENT_FILE_PARENT = 'run_with_different_file_parent';
    public const RUN_WITH_GIT_PARENT = 'run_with_git_parent';
    public const RUN_WITH_DIFFERENT_GIT_PARENT = 'run_with_different_git_parent';
    public const RUN_FAILED = 'run_failed';
    public const FILE_DIFFERENT_USER = 'file_different_user';
    public const GIT_DIFFERENT_USER = 'git_different_user';
    public const RUN_DIFFERENT_USER = 'run_different_user';

    public const ALL = [
        self::FILE_WITHOUT_RUN_SOURCE,
        self::FILE_WITH_RUN_SOURCE,
        self::GIT_WITH_CREDENTIALS_WITH_RUN_SOURCE,
        self::GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE,
        self::RUN_WITH_FILE_PARENT,
        self::RUN_WITH_DIFFERENT_FILE_PARENT,
        self::RUN_WITH_GIT_PARENT,
        self::RUN_WITH_DIFFERENT_GIT_PARENT,
        self::RUN_FAILED,
        self::FILE_DIFFERENT_USER,
        self::GIT_DIFFERENT_USER,
        self::RUN_DIFFERENT_USER,
    ];

    /**
     * @var array<string, SourceInterface>
     */
    private array $sources = [];

    /**
     * @var non-empty-string
     */
    private string $userId;

    public function __construct(
        private readonly SourceRepository $sourceRepository,
    ) {
    }

    /**
     * @param string[] $sourcesToInitialize
     */
    public function initialize(array $sourcesToInitialize = self::ALL): void
    {
        $idFactory = new EntityIdFactory();

        $fileSourceWithoutRunSource = FileSourceFactory::create($this->userId, 'without run source');
        $fileSourceWithRunSource = FileSourceFactory::create($this->userId, 'with run source');
        $gitSourceWithCredentialsWithRunSource = GitSourceFactory::create(
            userId: $this->userId,
            label: 'git source with credentials with run source',
            hostUrl: 'http://example.com/with-credentials.git',
            credentials: md5((string) rand()),
        );

        $this->sources[self::FILE_WITHOUT_RUN_SOURCE] = $fileSourceWithoutRunSource;
        $this->sources[self::FILE_WITH_RUN_SOURCE] = $fileSourceWithRunSource;
        $this->sources[self::GIT_WITH_CREDENTIALS_WITH_RUN_SOURCE] = $gitSourceWithCredentialsWithRunSource;
        $this->sources[self::GIT_WITHOUT_CREDENTIALS_WITHOUT_RUN_SOURCE] = GitSourceFactory::create(
            userId: $this->userId,
            label: 'git source without credentials without run source',
            hostUrl: 'http://example.com/without-credentials.git'
        );
        $this->sources[self::RUN_WITH_FILE_PARENT] = new RunSource($idFactory->create(), $fileSourceWithRunSource);
        $this->sources[self::RUN_WITH_DIFFERENT_FILE_PARENT] = new RunSource(
            $idFactory->create(),
            FileSourceFactory::create($this->userId, 'file source label two')
        );
        $this->sources[self::RUN_WITH_GIT_PARENT] = new RunSource(
            $idFactory->create(),
            $gitSourceWithCredentialsWithRunSource
        );
        $this->sources[self::RUN_WITH_DIFFERENT_GIT_PARENT] = new RunSource(
            $idFactory->create(),
            GitSourceFactory::create(
                userId: $this->userId,
                label: 'git source as different parent',
                hostUrl: 'http://example.com/as-different-parent.git'
            )
        );

        $this->sources[self::RUN_FAILED] = (new RunSource(
            $idFactory->create(),
            $gitSourceWithCredentialsWithRunSource
        ))
            ->setPreparationFailed(
                FailureReason::GIT_CLONE,
                'fatal: repository \'http://example.com/with-credentials.git\' not found'
            )
        ;

        $this->sources[self::FILE_DIFFERENT_USER] = FileSourceFactory::create($idFactory->create());
        $this->sources[self::GIT_DIFFERENT_USER] = GitSourceFactory::create(label: 'git source different user');
        $this->sources[self::RUN_DIFFERENT_USER] = new RunSource(
            $idFactory->create(),
            FileSourceFactory::create(UserId::create(), 'label')
        );

        foreach ($sourcesToInitialize as $sourceIdentifier) {
            $source = $this->sources[$sourceIdentifier] ?? null;
            if ($source instanceof SourceInterface) {
                $this->sourceRepository->save($source);
            }
        }
    }

    public function get(string $identifier): SourceInterface
    {
        $source = $this->sources[$identifier] ?? null;
        if (!$source instanceof SourceInterface) {
            throw new \RuntimeException('Source "' . $identifier . '" not found');
        }

        return $source;
    }

    /**
     * @param non-empty-string $userId
     */
    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }
}
