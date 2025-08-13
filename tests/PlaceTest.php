<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ROCrate\ROCrate;
use ROCrate\Place;

/**
 * Summary of PlaceTest
 */
class PlaceTest extends TestCase
{
    /**
     * Tests the constructor
     * @return void
     */
    public function testPlace(): void
    {
        $place = new Place("http://sws.geonames.org/8152662/");

        $this->assertEquals("http://sws.geonames.org/8152662/", $place->getId());
        $this->assertEquals(true, in_array("Place", $place->getTypes()));
    }

    /**
     * Tests the toArray method
     * @return void
     */
    public function testToArray(): void
    {
        $place = new Place("http://sws.geonames.org/8152662/");
        $place->addProperty("description", "Catalina Park is a disused motor racing venue, located at Katoomba ...")
            ->addPropertyPair("geo", "_:Geometry-1", true)
            ->addProperty("identifier", "http://sws.geonames.org/8152662/")
            ->addProperty("uri", "https://www.geonames.org/8152662/catalina-park.html")
            ->addProperty("name", "Catalina Park");
        $array = $place->toArray();

        $this->assertEquals("http://sws.geonames.org/8152662/", $array["@id"]);
        $this->assertEquals(true, in_array("Place", $array["@type"]));
        $this->assertEquals("Catalina Park is a disused motor racing venue, located at Katoomba ...", $array["description"]);
        $this->assertEquals("_:Geometry-1", $array["geo"][0]["@id"]);
        $this->assertEquals("http://sws.geonames.org/8152662/", $array["identifier"]);
        $this->assertEquals("https://www.geonames.org/8152662/catalina-park.html", $array["uri"]);
        $this->assertEquals("Catalina Park", $array["name"]);
    }
}
