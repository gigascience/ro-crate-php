<?php

namespace ROCrate;

use Json\{Unflattener, FileHandler};
use Exceptions\JsonFileException;

/**
 * Handles the writing of a ro-crate-metadata file
 */
class ROCrateWriter
{
    /**
     * Encode the ROCrate object, and write to JSON file
     * 
     * @param string $filePath Output file path
     * @param ROCrate $object The ROCrate object to encode
     * @throws JsonFileException
     */
    public static function writeFile(string $filePath, ROCrate $object): void 
    {

    }
}