<?php

namespace QUITests\Unit\Cron;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUITests\Unit\Cron\Fixtures\AccessibleQuiqqerCrons;

require_once __DIR__ . '/Fixtures/AccessibleQuiqqerCrons.php';

class QuiqqerCronsTest extends TestCase
{
    private string $uploadDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploadDirectory = sys_get_temp_dir()
            . '/quiqqer-cron-upload-cleanup-'
            . bin2hex(random_bytes(8))
            . '/';

        mkdir($this->uploadDirectory . 'fixture', 0700, true);
    }

    protected function tearDown(): void
    {
        $fixtureDirectory = $this->uploadDirectory . 'fixture/';

        if (is_dir($fixtureDirectory)) {
            $files = scandir($fixtureDirectory);

            if (is_array($files)) {
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        unlink($fixtureDirectory . $file);
                    }
                }
            }

            rmdir($fixtureDirectory);
        }

        if (is_dir($this->uploadDirectory)) {
            rmdir($this->uploadDirectory);
        }

        parent::tearDown();
    }

    #[Test]
    public function cleanupRemovesExpiredUploadAndMetadata(): void
    {
        $fixtureDirectory = $this->uploadDirectory . 'fixture/';
        $uploadFile = $fixtureDirectory . 'document.txt';
        $configFile = $uploadFile . '.json';

        file_put_contents($uploadFile, 'upload contents');
        file_put_contents($configFile, json_encode(['file' => 'document.txt']));
        touch($uploadFile, time() - 172800);
        touch($configFile, time() - 172800);

        AccessibleQuiqqerCrons::cleanupDirectory($this->uploadDirectory);

        self::assertFileDoesNotExist($uploadFile);
        self::assertFileDoesNotExist($configFile);
    }

    #[Test]
    public function cleanupRecognizesJsonUploadsWithoutTreatingThemAsMetadata(): void
    {
        $fixtureDirectory = $this->uploadDirectory . 'fixture/';
        $uploadFile = $fixtureDirectory . 'document.json';
        $configFile = $uploadFile . '.json';

        file_put_contents($uploadFile, json_encode(['contents' => 'upload']));
        file_put_contents($configFile, json_encode(['file' => 'document.json']));
        touch($uploadFile, time() - 172800);
        touch($configFile, time() - 172800);

        AccessibleQuiqqerCrons::cleanupDirectory($this->uploadDirectory);

        self::assertFileDoesNotExist($uploadFile);
        self::assertFileDoesNotExist($configFile);
    }

    #[Test]
    public function cleanupPreservesRecentAndInvalidMetadataFiles(): void
    {
        $fixtureDirectory = $this->uploadDirectory . 'fixture/';
        $recentUpload = $fixtureDirectory . 'recent.txt';
        $recentConfig = $recentUpload . '.json';
        $invalidConfig = $fixtureDirectory . 'invalid.json';

        file_put_contents($recentUpload, 'recent upload');
        file_put_contents($recentConfig, json_encode(['file' => 'recent.txt']));
        file_put_contents($invalidConfig, '{invalid');
        touch($invalidConfig, time() - 172800);

        AccessibleQuiqqerCrons::cleanupDirectory($this->uploadDirectory);

        self::assertFileExists($recentUpload);
        self::assertFileExists($recentConfig);
        self::assertFileExists($invalidConfig);
    }
}
