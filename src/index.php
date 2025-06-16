<?php

#echo '/../vendor/autoload.php';

#require '/../vendor/autoload.php';
require __DIR__ . '/../vendor/autoload.php';
#require '/top/vendor/autoload.php';
#require($_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php');

use Json\{Flattener, Unflattener, FileHandler};
use Exceptions\JsonFileException;

/*
$example = new Example();
echo $example->test(); // Should output message

// Test Monolog
$log = new Monolog\Logger('name');
$log->pushHandler(new Monolog\Handler\StreamHandler('app.log', Monolog\Logger::WARNING));
$log->warning('Test log!');
*/

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