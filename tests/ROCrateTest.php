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
        $author = new Person('#alice');
        $author->addProperty('name', 'Alice Smith');
        $author->addProperty('affiliation', 'University of Example 1');
        $crate->addEntity($author);
        $author = new Person('#bob');
        $author->addProperty('name', 'Bob');
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

        $author = new Person('#alice');
        $author->addProperty('name', 'Alice Smith');
        $author->addProperty('affiliation', 'University of Example 1');
        $crate->addEntity($author);
        $author = new Person('#cathy');
        $author->addProperty('name', 'Cathy');
        $author->addProperty('affiliation', 'University of Example 2');
        $crate->addEntity($author);
        $root->addProperty('creator', [['@id' => '#cathy'], ['@id' => '#alice']]);
        
        $this->assertEquals('./', $root->getId());
        $this->assertEquals('./', $crate->getDescriptor()->getProperties()['about'][0]['@id']);
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
        $author = new Person('#alice');
        $author->addProperty('name', 'Alice Smith');
        $author->addProperty('affiliation', 'University of Example 1');
        $crate->addEntity($author);
        $author = new Person('#bob');
        $author->addProperty('name', 'Bob');
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

        $author = new Person('#alice');
        $author->addProperty('name', 'Alice Smith');
        $author->addProperty('affiliation', 'University of Example 1');
        $crate->addEntity($author);
        $author = new Person('#cathy');
        $author->addProperty('name', 'Cathy');
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

        $author = new Person('#alice');
        $author->addProperty('name', 'Alice Smith');
        $author->addProperty('affiliation', 'University of Example 1');
        $crate->addEntity($author);
        $author = new Person('#cathy');
        $author->addProperty('name', 'Cathy');
        $author->addProperty('affiliation', 'University of Example 2');
        $crate->addEntity($author);
        
        $root->addPropertyPair('creator', '#alice', true)->addPropertyPair('creator', '#bob')->addPropertyPair('creator', '#cathy')->removePropertyPair('creator', '#alice')->addPropertyPair('creator', '#alice', true)->addPropertyPair('creator', '#bob');
        
        $this->assertEquals('#bob', $root->getProperties()['creator'][0]['@id']);
        $this->assertEquals('#cathy', $root->getProperties()['creator'][1]['@id']);
    }

    /**
     * Tests the ISO date string validator
     * @return void
     */
    public function testISOValidator(): void {
        $strings = ["", "23", "asdada", "-2015-04", "0862", "0000-03-02", "0000-53-02", "2346-06,19", "2025-08-04T10:30:45Z", "2030-12-25T08:00:00+02:00", "1999-07-14T23:59:59-05:00", "-2024-02-29T00:00:00Z", "-0044-03-15T09:30:00+03:00", "2023-12-31T23:59:59-11:30"];
        
        $this->assertEquals(false, ROCrate::isValidISO8601Date($strings[0]));
        $this->assertEquals(false, ROCrate::isValidISO8601Date($strings[1]));
        $this->assertEquals(false, ROCrate::isValidISO8601Date($strings[2]));

        $this->assertEquals(true, ROCrate::isValidISO8601Date($strings[3]));
        $this->assertEquals(true, ROCrate::isValidISO8601Date($strings[4]));
        $this->assertEquals(true, ROCrate::isValidISO8601Date($strings[5]));

        $this->assertEquals(false, ROCrate::isValidISO8601Date($strings[6]));
        $this->assertEquals(false, ROCrate::isValidISO8601Date($strings[7]));

        $this->assertEquals(true, ROCrate::isValidISO8601Date($strings[8]));
        $this->assertEquals(true, ROCrate::isValidISO8601Date($strings[9]));
        $this->assertEquals(true, ROCrate::isValidISO8601Date($strings[10]));
        $this->assertEquals(true, ROCrate::isValidISO8601Date($strings[11]));
        $this->assertEquals(true, ROCrate::isValidISO8601Date($strings[12]));
        $this->assertEquals(true, ROCrate::isValidISO8601Date($strings[13]));
    }

    /**
     * Tests the uri validator
     * @return void
     */
    public function testUriValidator(): void {
        $strings = ["http://example.com", "https://sub.example.com/path?q=val", "ftp://files.example.com", "path/to/file", "path/to/file", "//example.com", "file.txt?query=abc#section", "invalid|path", "space in url", "percent%2G"];

        $this->assertEquals(true, ROCrate::isValidUri($strings[0]));
        $this->assertEquals(true, ROCrate::isValidUri($strings[1]));
        $this->assertEquals(true, ROCrate::isValidUri($strings[2]));
        $this->assertEquals(false, ROCrate::isValidUri($strings[3]));
        $this->assertEquals(true, ROCrate::isValidUri($strings[4], false));
        $this->assertEquals(true, ROCrate::isValidUri($strings[5], false));
        $this->assertEquals(true, ROCrate::isValidUri($strings[6], false));
        $this->assertEquals(false, ROCrate::isValidUri($strings[7], false));
        $this->assertEquals(false, ROCrate::isValidUri($strings[8], false));
        $this->assertEquals(false, ROCrate::isValidUri($strings[9], false));
        $this->assertEquals(true, ROCrate::isValidUri("https://orcid.org/0000-0002-1825-0097"));
        $this->assertEquals(true, ROCrate::isValidUri("https://en.wikipedia.org/wiki/Josiah_S._Carberry"));
        $this->assertEquals(true, ROCrate::isValidUri("#josiah", false));
        $this->assertEquals(true, ROCrate::isValidUri("#0fa587c6-4580-4ece-a5df-69af3c5590e3", false));
        $this->assertEquals(false, ROCrate::isValidUri("#0fa587c6-4580-4ece-a5df-69af3c5590e3"));
    }

    /**
     * Tests the url validator
     * @return void
     */
    public function testUrlValidator(): void {
        $strings = ["http://example.com", "https://sub.example.com/path", "ftp://files.example.com", "mailto:contact@example.com", "//example.com", "example.com", "/path/to/file"];

         $this->assertEquals(true, ROCrate::isValidUrl($strings[0]));
        $this->assertEquals(true, ROCrate::isValidUrl($strings[1]));
        $this->assertEquals(true, ROCrate::isValidUrl($strings[2]));
        $this->assertEquals(true, ROCrate::isValidUrl($strings[3]));
        $this->assertEquals(false, ROCrate::isValidUrl($strings[4]));
        $this->assertEquals(false, ROCrate::isValidUrl($strings[5]));
        $this->assertEquals(false, ROCrate::isValidUrl($strings[6]));
    }
}