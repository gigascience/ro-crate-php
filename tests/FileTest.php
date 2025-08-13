<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ROCrate\ROCrate;
use ROCrate\File;

/**
 * Summary of FileTest
 */
class FileTest extends TestCase
{
    /**
     * Tests the constructor
     * @return void
     */
    public function testFile(): void
    {
        $file = new File("https://zenodo.org/record/3541888/files/ro-crate-1.0.0.pdf");

        $this->assertEquals("https://zenodo.org/record/3541888/files/ro-crate-1.0.0.pdf", $file->getId());
        $this->assertEquals(true, in_array("File", $file->getTypes()));
    }

    /**
     * Tests the toArray method
     * @return void
     */
    public function testToArray(): void
    {
        $file = new File("https://zenodo.org/record/3541888/files/ro-crate-1.0.0.pdf");
        $file->addProperty("name", "RO-Crate specification")
            ->addProperty("contentSize", "310691")
            ->addProperty("description", "RO-Crate specification")
            ->addProperty("encodingFormat", "application/pdf");
        $array = $file->toArray();

        $this->assertEquals("https://zenodo.org/record/3541888/files/ro-crate-1.0.0.pdf", $array["@id"]);
        $this->assertEquals(true, in_array("File", $array["@type"]));
        $this->assertEquals("RO-Crate specification", $array["name"]);
        $this->assertEquals("310691", $array["contentSize"]);
        $this->assertEquals("RO-Crate specification", $array["description"]);
        $this->assertEquals("application/pdf", $array["encodingFormat"]);
    }
}
