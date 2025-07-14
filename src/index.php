<?php

#echo '/../vendor/autoload.php';

#require '/../vendor/autoload.php';
require __DIR__ . '/../vendor/autoload.php';
#require '/top/vendor/autoload.php';
#require($_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php');

use Json\{Flattener, Unflattener, FileHandler};
use Exceptions\JsonFileException;
use ROCrate\{ROCrate, File, Person};

/*
$example = new Example();
echo $example->test(); // Should output message

// Test Monolog
$log = new Monolog\Logger('name');
$log->pushHandler(new Monolog\Handler\StreamHandler('app.log', Monolog\Logger::WARNING));
$log->warning('Test log!');
*/

//phpinfo();

$flattener = new Flattener();
$unflattener = new Unflattener();

try {
    // Read and flatten JSON
    $data = FileHandler::readJsonFile(__DIR__ . '/../resources/ro-crate-metadata.json');
    $flattened = $flattener->flatten($data);
    
    // Process flattened data
    foreach ($flattened as $key => $value) {
        // Do something with key-value pairs
        echo''. $key .' -> '. $value ." ";
    }
    
    // Unflatten and write
    $nested = $unflattener->unflatten($flattened);
    FileHandler::writeJsonFile(__DIR__ . '/../resources/output.json', $nested);
    
} catch (JsonFileException $e) {
    die("JSON Error: " . $e->getMessage());
}


// Create new crate
$crate = new ROCrate(__DIR__ . '/../resources', false);

// Add Metadata Descriptor
$crate->addProfile();

// Add Root Data Entity
$root = $crate->getRootDataset();
$root->addProperty('name', 'My Research Project');
$root->addProperty('description', 'Example RO-Crate');


//$crate = new ROCrate(__DIR__ . '/../resources', true);
//$root = $crate->getRootDataset();

// Add Data Entity (creator)
// Similar for Contextual Entity
$author = new Person('#alice', 'Alice Smith');
$author->addProperty('affiliation', 'University of Example 1');
$crate->addEntity($author);
$author = new Person('#bob', 'Bob');
$author->addProperty('affiliation', 'University of Example 2');
$author->addPropertyPair('knows', '#alice');
$author->addPropertyPair('knows', '#alice')->addPropertyPair('knows', '#cathy');
$crate->addEntity($author);
///$root->addProperty('creator', [['@id' => '#alice'], ['@id' => '#bob']]);
$root->addPropertyPair('creator', '#alice', true)->addPropertyPair('creator', '#bob')->addPropertyPair('creator', '#cathy')->removePropertyPair('creator', '#alice')->addPropertyPair('creator', '#alice', true)->addPropertyPair('creator', '#bob');

$crate->addEntity($crate->createGenericEntity('Test ID', []));

// Validate and save
$errors = $crate->validate();
if (!empty($errors)) {
    echo "Validation errors:\n" . implode("\n", $errors);
} else {
    $crate->save();
}

/*
foreach ($root->toArray() as $key => $value) {
    print("". $key ."=>". $value ."");
}*/
