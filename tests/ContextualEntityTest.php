<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ROCrate\ROCrate;
use ROCrate\ContextualEntity;

/**
 * Summary of ContextualEntityTest
 */
class ContextualEntityTest extends TestCase
{
    /**
     * Tests the constructor
     * @return void
     */
    public function testContactPoint(): void
    {
        $contactPoint = new ContextualEntity("mailto:example@abc.def.gh", ["Person"]);

        $this->assertEquals("mailto:example@abc.def.gh", $contactPoint->getId());
        $this->assertEquals(true, in_array("Person", $contactPoint->getTypes()));
    }

    /**
     * Tests the toArray method
     * @return void
     */
    public function testToArray(): void
    {
        $contactPoint = new ContextualEntity("mailto:example@abc.def.gh", ["Person"]);
        $contactPoint->addProperty("description", "This is a contextual entity.");
        $array = $contactPoint->toArray();

        $this->assertEquals("mailto:example@abc.def.gh", $array["@id"]);
        $this->assertEquals(true, in_array("Person", $array["@type"]));
        $this->assertEquals("This is a contextual entity.", $array["description"]);
    }
}
