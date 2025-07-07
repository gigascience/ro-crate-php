<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ROCrate\ROCrate;
use ROCrate\Person;

/**
 * Summary of ROCrateTest
 */
class ROCrateTest extends TestCase 
{
    /**
     * Tests whether the creation of a ro-crate object is successful from nothing
     */
    public function testCreationFromEmpty()
    {
        // Create new crate
        $crate = new ROCrate(__DIR__ . '/../resources', false);

        // Add Metadata Descriptor
        $crate->addProfile();

        // Add Root Data Entity
        $root = $crate->getRootDataset();
        $root->addProperty('name', 'My Research Project');
        $root->addProperty('description', 'Example RO-Crate');

        // Add Data Entity (creator)
        // Similar for Contextual Entity
        $author = new Person('#alice', 'Alice Smith');
        $author->addProperty('affiliation', 'University of Example 1');
        $crate->addEntity($author);
        $author = new Person('#bob', 'Bob');
        $author->addProperty('affiliation', 'University of Example 2');
        $crate->addEntity($author);
        $root->addProperty('creator', [['@id' => '#alice'], ['@id' => '#bob']]);
        
        $this->assertEquals('My Research Project', $root->getProperties()['name']);
        $this->assertEquals('Example RO-Crate', $root->getProperties()['description']);
        $this->assertEquals('#alice', $root->getProperties()['creator'][0]['@id']);
        $this->assertEquals('Person', $author->getTypes()[0]);
    }

    /**
     * Tests whether the creation of a ro-crate object is successful given an existing file of ro-crate
     */
    public function testCreationFromExisting()
    {
        $crate = new ROCrate(__DIR__ . '/../resources', true);
        $root = $crate->getRootDataset();

        $author = new Person('#alice', 'Alice Smith');
        $author->addProperty('affiliation', 'University of Example 1');
        $crate->addEntity($author);
        $author = new Person('#cathy', 'Cathy');
        $author->addProperty('affiliation', 'University of Example 2');
        $crate->addEntity($author);
        $root->addProperty('creator', [['@id' => '#cathy'], ['@id' => '#alice']]);
        
        $this->assertEquals('./', $root->getId());
        $this->assertEquals('./', $crate->getDescriptor()->getProperties()['about']['@id']);
        $this->assertEquals('#cathy', $root->getProperties()['creator'][0]['@id']);
        $this->assertEquals('Person', $author->getTypes()[0]);
    }
}