<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ROCrate\ROCrate;
use ROCrate\DataEntity;

/**
 * Summary of DataEntityTest
 */
class DataEntityTest extends TestCase {
    /**
     * Tests the constructor
     * @return void
     */
    public function testDataEntity(): void {
        $dataEntity = new DataEntity("survey-responses-2019.csv", ["File"]);

        $this->assertEquals("survey-responses-2019.csv", $dataEntity->getId());
        $this->assertEquals(true, in_array("File", $dataEntity->getTypes()));
    }

    /**
     * Tests the toArray method
     * @return void
     */
    public function testToArray(): void {
        $dataEntity = new DataEntity("survey-responses-2019.csv", ["File"]);
        $dataEntity->addProperty("name", "Survey responses")
            ->addProperty("encodingFormat", "text/csv")
            ->addProperty("contentUrl", "http://example.com/downloads/2019/survey-responses-2019.csv")
            ->addPropertyPair("subjectOf", "http://example.com/reports/2019/annual-survey.html", true);
        $array = $dataEntity->toArray();

        $this->assertEquals("survey-responses-2019.csv", $array["@id"]);
        $this->assertEquals(true, in_array("File", $array["@type"]));
        $this->assertEquals( "Survey responses", $array["name"]);
        $this->assertEquals("text/csv", $array["encodingFormat"]);
        $this->assertEquals("http://example.com/downloads/2019/survey-responses-2019.csv", $array["contentUrl"]);
        $this->assertEquals("http://example.com/reports/2019/annual-survey.html", $array["subjectOf"][0]["@id"]);
    }
}