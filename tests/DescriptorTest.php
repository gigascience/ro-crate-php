<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ROCrate\ROCrate;
use ROCrate\Descriptor;

/**
 * Summary of DescriptorTest
 */
class DescriptorTest extends TestCase {
    /**
     * Tests the constructor
     * @return void
     */
    public function testDescriptor(): void {
        $descriptor = new Descriptor();

        $this->assertEquals("ro-crate-metadata.json", $descriptor->getId());
        $this->assertEquals(true, in_array("CreativeWork", $descriptor->getTypes()));
    }

    /**
     * Tests the toArray method
     * @return void
     */
    public function testToArray(): void {
        $descriptor = new Descriptor();
        $descriptor->addPropertyPair("about", "./", true)
            ->addPropertyPair("conformsTo", "https://w3id.org/ro/crate/1.2", true);
        $array = $descriptor->toArray();

        $this->assertEquals("ro-crate-metadata.json", $array["@id"]);
        $this->assertEquals(true, in_array("CreativeWork", $array["@type"]));
        $this->assertEquals("./", $array["about"][0]["@id"]);
        $this->assertEquals("https://w3id.org/ro/crate/1.2", $array["conformsTo"][0]["@id"]);
    }
}