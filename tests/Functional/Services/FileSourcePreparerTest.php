<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Entity\FileSource;
use App\Services\FileSourcePreparer;
use App\Tests\Model\UserId;
use App\Tests\Services\FileStoreFixtureCreator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FileSourcePreparerTest extends WebTestCase
{
    private FileSourcePreparer $fileSourcePreparer;
    private FileStoreFixtureCreator $fixtureCreator;

    protected function setUp(): void
    {
        parent::setUp();

        $fileSourcePreparer = self::getContainer()->get(FileSourcePreparer::class);
        \assert($fileSourcePreparer instanceof FileSourcePreparer);
        $this->fileSourcePreparer = $fileSourcePreparer;

        $fixtureCreator = self::getContainer()->get(FileStoreFixtureCreator::class);
        \assert($fixtureCreator instanceof FileStoreFixtureCreator);
        $this->fixtureCreator = $fixtureCreator;
    }

    public function testPrepareSuccess(): void
    {
        $source = new FileSource(UserId::create(), 'file source label');
        $this->fixtureCreator->copyFixturesTo($source->getPath());

        $target = $this->fileSourcePreparer->prepare($source);

        $fileStoreBasePath = self::getContainer()->getParameter('file_store_base_path');
        \assert(is_string($fileStoreBasePath));

        $sourceAbsolutePath = $fileStoreBasePath . '/' . $source;
        $targetAbsolutePath = $fileStoreBasePath . '/' . $target;

        self::assertSame(scandir($sourceAbsolutePath), scandir($targetAbsolutePath));
    }
}
