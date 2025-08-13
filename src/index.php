<?php

#echo '/../vendor/autoload.php';

#require '/../vendor/autoload.php';
require __DIR__ . '/../vendor/autoload.php';
#require '/top/vendor/autoload.php';
#require($_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php');

use Json\{Flattener, Unflattener, FileHandler};
use Exceptions\JsonFileException;
use ROCrate\{ROCrate, File, Person, ROCratePreviewGenerator};

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
        echo'' . $key . ' -> ' . $value . " ";
    }

    // Unflatten and write
    $nested = $unflattener->unflatten($flattened);
    FileHandler::writeJsonFile(__DIR__ . '/../resources/output.json', $nested);
} catch (JsonFileException $e) {
    die("JSON Error: " . $e->getMessage());
}

ROCratePreviewGenerator::generatePreview(__DIR__ . '/../resources');

// Create new crate
//$crate = new ROCrate(__DIR__ . '/../resources', false);

// Add Metadata Descriptor
//$crate->addProfile();

// Add Root Data Entity
//$root = $crate->getRootDataset();
//$root->addProperty('name', 'My Research Project');
//$root->addProperty('description', 'Example RO-Crate');


$crate = new ROCrate(__DIR__ . '/../resources', true);
$root = $crate->getRootDataset();

$root->addPropertyPair("description", "Test Description", false);
$root->addPropertyPair("license", "Test License", false);

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
$author->addPropertyPair('knows', '#alice', true)->addPropertyPair('knows', '#cathy');

//$root->addProperty('creator', [['@id' => '#alice']]);
//$root->addProperty('creator', [['@id' => '#alice'], ['@id' => '#bob']]);
//$root->addPropertyPair('creator', '#alice', true)->addPropertyPair('creator', '#bob')->addPropertyPair('creator', '#cathy')->removePropertyPair("creator", "#alice")->addPropertyPair('creator', '#alice')->addPropertyPair('creator', '#bob');
$root->addProperty('creator', [['@id' => '#cathy'], ['@id' => '#alice']])->removePropertyPair('creator', '#bob')->removePropertyPair('creator', '#cathy');

$crate->addEntity($crate->createGenericEntity('Test ID', [])->addType("TestType"));

$author->addPropertyPair("encodingFormat", "test/pdf", false)->addPropertyPair("encodingFormat", "TRY", true);//->removePropertyPair("encodingFormat", "TRY");

//$crate->getEntity("data.csv")->removePropertyPair("license", "https://creativecommons.org/licenses/by-nc-sa/3.0/au/");

//$crate->removeEntity($author->getId());

// Validate and save
//echo $crate->validate()[0];

try {
    $crate->save();
} catch (Exception $e) {
    foreach ($crate->showErrors() as $msg) {
        echo "\n$msg";
    }
}



/*
foreach ($root->toArray() as $key => $value) {
    print("". $key ."=>". $value ."");
}*/

// Usage example:
/*
try {
    $generator = new ROCratePreviewGenerator(__DIR__ . '/../resources');
    $generator->generateHTML();
    echo "RO-Crate HTML preview generated successfully!";
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}*/
