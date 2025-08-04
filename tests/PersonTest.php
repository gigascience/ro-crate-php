<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ROCrate\ROCrate;
use ROCrate\Person;

/**
 * Summary of PersonTest
 */
class PersonTest extends TestCase {
    /**
     * Tests the constructor
     * @return void
     */
    public function testPerson(): void {
        $person = new Person("https://orcid.org/0000-0002-8367-6908");

        $this->assertEquals("https://orcid.org/0000-0002-8367-6908", $person->getId());
        $this->assertEquals(true, in_array("Person", $person->getTypes()));
    }

    /**
     * Tests the toArray method
     * @return void
     */
    public function testToArray(): void {
        $person = new Person("https://orcid.org/0000-0002-8367-6908");
        $person->addProperty("affiliation", "University of Technology Sydney")
            ->addProperty("name", "J. Xuan");
        $array = $person->toArray();

        $this->assertEquals("https://orcid.org/0000-0002-8367-6908", $array["@id"]);
        $this->assertEquals(true, in_array("Person", $array["@type"]));
        $this->assertEquals("University of Technology Sydney", $array["affiliation"]);
        $this->assertEquals("J. Xuan", $array["name"]);
    }
}