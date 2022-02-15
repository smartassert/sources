<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\AbstractSource;
use App\Entity\FileSource;
use App\Repository\SourceRepository;
use App\Services\Source\Store;
use App\Tests\Services\AuthorizationRequestAsserter;
use App\Tests\Services\EntityRemover;
use League\Flysystem\FilesystemOperator;
use webignition\ObjectReflector\ObjectReflector;

abstract class AbstractFileSourceFilesTest extends AbstractSourceControllerTest
{
    protected const USER_ID = '01FVHKTM3V53JVCW1HPN1125NF';
    protected const SOURCE_ID = '01FVHM0XGXGAD463JTW05CN2TF';
    protected const SOURCE_RELATIVE_PATH = self::USER_ID . '/' . self::SOURCE_ID;
    protected const EXPECTED_FILE_RELATIVE_PATH = self::SOURCE_RELATIVE_PATH . '/' . self::FILENAME;

    protected const FILENAME = 'filename.yaml';
    protected const CONTENT = '- list item';

    protected const CREATE_DATA = [
        'content' => self::CONTENT,
    ];

    protected const UPDATE_DATA = [
        'content' => self::CONTENT . ' updated',
    ];

    protected SourceRepository $sourceRepository;
    protected AuthorizationRequestAsserter $authorizationRequestAsserter;
    protected FilesystemOperator $filesystemOperator;

    protected function setUp(): void
    {
        parent::setUp();

        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        \assert($sourceRepository instanceof SourceRepository);
        $this->sourceRepository = $sourceRepository;

        $authorizationRequestAsserter = self::getContainer()->get(AuthorizationRequestAsserter::class);
        \assert($authorizationRequestAsserter instanceof AuthorizationRequestAsserter);
        $this->authorizationRequestAsserter = $authorizationRequestAsserter;

        $filesystemOperator = self::getContainer()->get('default.storage');
        \assert($filesystemOperator instanceof FilesystemOperator);
        $this->filesystemOperator = $filesystemOperator;

        $entityRemover = self::getContainer()->get(EntityRemover::class);
        if ($entityRemover instanceof EntityRemover) {
            $entityRemover->removeAll();
        }

        $source = new FileSource(self::USER_ID, 'file source label');
        ObjectReflector::setProperty(
            $source,
            AbstractSource::class,
            'id',
            self::SOURCE_ID
        );

        $store = self::getContainer()->get(Store::class);
        \assert($store instanceof Store);
        $store->add($source);
    }
}
