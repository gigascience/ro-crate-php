<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ROCrate\ROCrate;
use ROCrate\Dataset;

/**
 * Summary of DatasetTest
 */
class DatasetTest extends TestCase
{
    /**
     * Tests the constructor
     * @return void
     */
    public function testDataset(): void
    {
        $dataset = new Dataset("./");

        $this->assertEquals("./", $dataset->getId());
        $this->assertEquals(true, in_array("Dataset", $dataset->getTypes()));
    }

    /**
     * Tests the toArray method
     * @return void
     */
    public function testToArray(): void
    {
        $dataset = new Dataset("./");
        $dataset->addPropertyPair("hasPart", "survey-responses-2019.csv", true)
            ->addPropertyPair("hasPart", "https://zenodo.org/record/3541888/files/ro-crate-1.0.0.pdf");
        $array = $dataset->toArray();

        $this->assertEquals("./", $array["@id"]);
        $this->assertEquals(true, in_array("Dataset", $array["@type"]));
        $this->assertEquals("survey-responses-2019.csv", $array["hasPart"][0]["@id"]);
        $this->assertEquals("https://zenodo.org/record/3541888/files/ro-crate-1.0.0.pdf", $array["hasPart"][1]["@id"]);
    }
}
