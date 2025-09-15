<?php

declare(strict_types=1);

namespace Tests;

// tests/JsonFileHandlerTest.php
use PHPUnit\Framework\TestCase;
use Json\FileHandler;
use Exceptions\JsonFileException;

class JsonFileHandlerTest extends TestCase
{
    private const TEST_FILE = __DIR__ . '/test.json';
    private FileHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new FileHandler();
    }

    protected function tearDown(): void
    {
        if (file_exists(self::TEST_FILE)) {
            unlink(self::TEST_FILE);
        }
    }

    public function testReadWriteCycle(): void
    {
        $data = ['test' => ['nested' => true]];

        // Write then read
        $this->handler->writeJsonFile(self::TEST_FILE, $data);
        $result = $this->handler->readJsonFile(self::TEST_FILE);

        $this->assertEquals($data, $result);
    }

    public function testReadMissingFile(): void
    {
        $this->expectException(JsonFileException::class);
        $this->expectExceptionCode(JsonFileException::FILE_NOT_FOUND);
        $this->handler->readJsonFile('/path/to/missing.json');
    }

    public function testWriteInvalidData(): void
    {
        // Create invalid data (circular reference)
        $data = [];
        $data['self'] = &$data;

        $this->expectException(JsonFileException::class);
        $this->expectExceptionCode(JsonFileException::INVALID_JSON);
        $this->handler->writeJsonFile(self::TEST_FILE, $data);
    }

    public function testInvalidJsonFile(): void
    {
        file_put_contents(self::TEST_FILE, '{invalid json}');

        $this->expectException(JsonFileException::class);
        $this->expectExceptionCode(JsonFileException::INVALID_JSON);
        $this->handler->readJsonFile(self::TEST_FILE);
    }
}
