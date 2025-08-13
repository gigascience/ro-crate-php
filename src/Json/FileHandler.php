<?php

// src/Json/FileHandler.php
namespace Json;

use Exceptions\JsonFileException;

/**
 * Handles JSON file reading/writing operations
 */
class FileHandler
{
    /**
     * Read and decode JSON file
     *
     * @param string $filePath Absolute file path
     * @return array Decoded JSON data
     * @throws JsonFileException
     */
    public static function readJsonFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new JsonFileException("JSON file not found: $filePath", JsonFileException::FILE_NOT_FOUND);
        }

        $json = file_get_contents($filePath);
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonFileException("Invalid JSON: " . json_last_error_msg(), JsonFileException::INVALID_JSON);
        }

        return $data;
    }

    /**
     * Encode data and write to JSON file
     *
     * @param string $filePath Output file path
     * @param array $data Data to encode
     * @throws JsonFileException
     */
    public static function writeJsonFile(string $filePath, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JsonFileException("JSON encoding failed: " . json_last_error_msg(), JsonFileException::INVALID_JSON);
        }

        if (file_put_contents($filePath, $json) === false) {
            throw new JsonFileException("Failed to write JSON file", JsonFileException::WRITE_FAILURE);
        }
    }
}
