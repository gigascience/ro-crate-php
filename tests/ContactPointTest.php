<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ROCrate\ROCrate;
use ROCrate\ContactPoint;

/**
 * Summary of ContactPointTest
 */
class ContactPointTest extends TestCase {
    /**
     * Tests the constructor
     * @return void
     */
    public function testContactPoint(): void {
        $contactPoint = new ContactPoint("mailto:example@abc.def.gh");

        $this->assertEquals("mailto:example@abc.def.gh", $contactPoint->getId());
        $this->assertEquals(true, in_array("ContactPoint", $contactPoint->getTypes()));
    }

    /**
     * Tests the toArray method
     * @return void
     */
    public function testToArray(): void {
        $contactPoint = new ContactPoint("mailto:example@abc.def.gh");
        $contactPoint->addProperty("contactType", "customer service")
            ->addProperty("email", "example@abc.def.gh")
            ->addProperty("identifier", "example@abc.def.gh")
            ->addProperty("url", "https://orcid.org/xxxx-yyyy-zzzz-mmmm");
        $array = $contactPoint->toArray();

        $this->assertEquals("mailto:example@abc.def.gh", $array["@id"]);
        $this->assertEquals(true, in_array("ContactPoint", $array["@type"]));
        $this->assertEquals("customer service", $array["contactType"]);
        $this->assertEquals("example@abc.def.gh", $array["email"]);
        $this->assertEquals("example@abc.def.gh", $array["identifier"]);
        $this->assertEquals("https://orcid.org/xxxx-yyyy-zzzz-mmmm", $array["url"]);
    }
}