<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ROCrate\ROCrate;
use ROCrate\Organization;

/**
 * Summary of OrganizationTest
 */
class OrganizationTest extends TestCase
{
    /**
     * Tests the constructor
     * @return void
     */
    public function testOrganization(): void
    {
        $organization = new Organization("https://ror.org/03f0f6041");

        $this->assertEquals("https://ror.org/03f0f6041", $organization->getId());
        $this->assertEquals(true, in_array("Organization", $organization->getTypes()));
    }

    /**
     * Tests the toArray method
     * @return void
     */
    public function testToArray(): void
    {
        $organization = new Organization("https://ror.org/03f0f6041");
        $organization->addProperty("name", "University of Technology Sydney")
            ->addProperty("url", "https://ror.org/03f0f6041");
        $array = $organization->toArray();

        $this->assertEquals("https://ror.org/03f0f6041", $array["@id"]);
        $this->assertEquals(true, in_array("Organization", $array["@type"]));
        $this->assertEquals("University of Technology Sydney", $array["name"]);
        $this->assertEquals("https://ror.org/03f0f6041", $array["url"]);
    }
}
