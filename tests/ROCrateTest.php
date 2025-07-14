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
     * @return void
     */
    public function testCreationFromEmpty(): void
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
     * @return void
     */
    public function testCreationFromExisting(): void
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

    /**
     * Tests whether the addPropertyPair works well
     * @return void
     */
    public function testAddPropertyPair(): void
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
        $author->addPropertyPair('knows', '#alan');
        $author->addPropertyPair('knows', '#alice', false);
        $author->addPropertyPair('knows', '#alice')->addPropertyPair('knows', '#cathy');

        $crate->addEntity($author);
        
        $root->addPropertyPair('creator', '#alice', true)->addPropertyPair('creator', '#bob')->addPropertyPair('creator', '#cathy');
        
        $this->assertEquals('#alice', $author->getProperties()['knows'][0]);
        $this->assertEquals('#cathy', $author->getProperties()['knows'][1]);
        $this->assertEquals('#alice', $root->getProperties()['creator'][0]['@id']);
        $this->assertEquals('#bob', $root->getProperties()['creator'][1]['@id']);
    }

    /**
     * Tests whether the removePropertyPair works well
     * @return void
     */
    public function testRemovePropertyPair(): void
    {
        $crate = new ROCrate(__DIR__ . '/../resources', true);
        $root = $crate->getRootDataset();

        $author = new Person('#alice', 'Alice Smith');
        $author->addProperty('affiliation', 'University of Example 1');
        $crate->addEntity($author);
        $author = new Person('#cathy', 'Cathy');
        $author->addProperty('affiliation', 'University of Example 2');
        $crate->addEntity($author);
        $root->addProperty('creator', [['@id' => '#cathy'], ['@id' => '#alice']])->removePropertyPair('creator', '#bob')->removePropertyPair('creator', '#cathy');
        
        $this->assertEquals('#alice', $root->getProperties()['creator'][0]['@id']);
    }

    /**
     * Tests whether the addPropertyPair and the removePropertyPair work well together
     * @return void
     */
    public function testManipulatePropertyPair(): void
    {
        $crate = new ROCrate(__DIR__ . '/../resources', true);
        $root = $crate->getRootDataset();

        $author = new Person('#alice', 'Alice Smith');
        $author->addProperty('affiliation', 'University of Example 1');
        $crate->addEntity($author);
        $author = new Person('#cathy', 'Cathy');
        $author->addProperty('affiliation', 'University of Example 2');
        $crate->addEntity($author);
        
        $root->addPropertyPair('creator', '#alice', true)->addPropertyPair('creator', '#bob')->addPropertyPair('creator', '#cathy')->removePropertyPair('creator', '#alice')->addPropertyPair('creator', '#alice', true)->addPropertyPair('creator', '#bob');
        
        $this->assertEquals('#bob', $root->getProperties()['creator'][0]['@id']);
        $this->assertEquals('#cathy', $root->getProperties()['creator'][1]['@id']);
    }
}