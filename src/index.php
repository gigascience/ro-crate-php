<?php

require __DIR__ . '/../vendor/autoload.php';

use Json\{Flattener, Unflattener, FileHandler};
use Exceptions\JsonFileException;
use ROCrate\{ROCrate, File, Person, Organization, Publication, ContextualEntity, ContactPoint, Dataset,
     ROCratePreviewGenerator};

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
        echo'' . $key . ' -> ' . $value . "\n";
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

/*
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
//$root->addPropertyPair('creator', '#alice', true)->addPropertyPair('creator', '#bob')
// ->addPropertyPair('creator', '#cathy')->removePropertyPair("creator", "#alice")
// ->addPropertyPair('creator', '#alice')->addPropertyPair('creator', '#bob');
$root->addProperty('creator', [['@id' => '#cathy'], ['@id' => '#alice']])
    ->removePropertyPair('creator', '#bob')->removePropertyPair('creator', '#cathy');

$crate->addEntity($crate->createGenericEntity('Test ID', [])->addType("TestType"));

$author->addPropertyPair("encodingFormat", "test/pdf", false)
    ->addPropertyPair("encodingFormat", "TRY", true);//->removePropertyPair("encodingFormat", "TRY");
*/

//$crate->getEntity("data.csv")->removePropertyPair("license", "https://creativecommons.org/licenses/by-nc-sa/3.0/au/");

//$crate->removeEntity($author->getId());

// GigaDB testing example using dataset of id 102736
$crate = new ROCrate(__DIR__ . '/../resources');
$crate->addProfile();
$crate->getDescriptor()->removePropertyPair("about", "./")
    ->addPropertyPair("about", "https://gigadb.org/dataset/102736", true);

$root = $crate->getRootDataset();

$name = 'Supporting data for "Using synthetic RNA to benchmark poly(A) length inference from direct RNA sequencing."';
$desc = 'Polyadenylation is a dynamic process which is important in cellular physiology. ' .
'Oxford Nanopore Technologies direct RNA-sequencing provides a strategy for sequencing the full-length RNA molecule ' .
'and analysis of the transcriptome and epi-transcriptome. There are currently several tools available for poly(A) ' .
'tail-length estimation, including well-established tools such as tailfindr and nanopolish, as well as two more ' .
'recent deep learning models: Dorado and BoostNano. However, there has been limited benchmarking of the accuracy of ' .
'these tools against gold-standard datasets. In this paper we evaluate four poly(A) estimation tools using synthetic ' .
'RNA standards (Sequins), which have known poly(A) tail-lengths and provide a valuable approach to measuring the ' .
'accuracy of poly(A) tail-length estimation. All four tools generate mean tail-length estimates which lie within 12% ' .
'of the correct value. Overall, Dorado is recommended as the preferred approach due to its relatively fast run ' .
'times, low coefficient of variation and ease of use with integration with base-calling.';
$root->setId("https://gigadb.org/dataset/102736")
    ->addPropertyPair("identifier", "https://doi.org/10.5524/102736", true)
    ->addPropertyPair("cite-as", "https://doi.org/10.5524/102736", false)
    ->addPropertyPair("name", $name, false)
    ->addPropertyPair("description", $desc, false)
    ->addPropertyPair("datePublished", "2025-07-29", false)
    ->addPropertyPair("sdDatePublished", "2025-07-29", false)
    ->addPropertyPair("publisher", "https://gigadb.org/", true)
    ->addPropertyPair("sdPublisher", "https://gigadb.org/", true)
    ->addPropertyPair("license", "https://creativecommons.org/publicdomain/zero/1.0/", true)
    ->addPropertyPair("thumbnail", "https://assets.gigadb-cdn.net/live/images" .
    "/datasets/32d9369e-500d-5347-8842-9fe46cdc3693/102736.png", true);

$crate->addEntity(new File("https://assets.gigadb-cdn.net/live/images" .
    "/datasets/32d9369e-500d-5347-8842-9fe46cdc3693/102736.png"));

$parts = ["https://s3.ap-northeast-1.wasabisys.com/gigadb-datasets/live/pub/10.5524/" .
"102001_103000/102736/readme_102736.txt", "https://s3.ap-northeast-1.wasabisys.com/gigadb-datasets/live/pub/" .
"10.5524/102001_103000/102736/boostnano_no_dorado_R1_tails.csv",
"https://gigadb.org/dataset/view/id/102736/Files_page/4", "#other-files"];
foreach ($parts as $part) {
    $root->addPropertyPair("hasPart", $part, true);
}

$fileOne = new File("https://s3.ap-northeast-1.wasabisys.com/gigadb-datasets/live/pub/10.5524/" .
"102001_103000/102736/readme_102736.txt");
$fileOne->addPropertyPair("name", "readme_102736.txt", false)
    ->addPropertyPair("contentSize", "9.30 kB", false)
    ->addPropertyPair("encodingFormat", "text/txt", false);
$crate->addEntity($fileOne);
$fileOne->addPropertyPair("exifData", "#oneExtra", true);
$oneExtra = new ContextualEntity("#oneExtra", ["PropertyValue"]);
$oneExtra->addPropertyPair("name", "Extra Information", false)
    ->addPropertyPair("value", "Data Type: Readme, File Attributes: MD5 checksum: " .
    "450ef019cf8ba58beb644ef18d1411d0", false);
$crate->addEntity($oneExtra);

$fileTwo = new File("https://s3.ap-northeast-1.wasabisys.com/gigadb-datasets/live/pub/" .
"10.5524/102001_103000/102736/boostnano_no_dorado_R1_tails.csv");
$fileTwo->addPropertyPair("name", "boostnano_no_dorado_R1_tails.csv", false)
    ->addPropertyPair("contentSize", "317.24 kB", false)
    ->addPropertyPair("encodingFormat", "text/csv", false)
    ->addPropertyPair("description", "PolyA tail lengths as found by Boostnano " .
    "for R1 sequins which were filtered out by Dorado but kept by Boostnano; underlying data for figure 3", false);
$crate->addEntity($fileTwo);
$fileTwo->addPropertyPair("exifData", "#twoExtra", true);
$twoExtra = new ContextualEntity("#twoExtra", ["PropertyValue"]);
$twoExtra->addPropertyPair("name", "Extra Information", false)
    ->addPropertyPair("value", "Data Type: Tabular data, File Attributes: MD5 checksum: " .
    "97ee210d263c783e4ddfe20352831d60 Figure in MS: 3", false);
$crate->addEntity($twoExtra);

$zip = new Dataset("https://gigadb.org/dataset/view/id/102736/Files_page/4");
$zip->addPropertyPair("name", "BoostNano-master", false)
    ->addPropertyPair("description", "Archival copy of the GitHub repository " .
    "https://github.com/haotianteng/BoostNano downloaded 18-July-2025. BoostNano, a tool for " .
    "preprocessing ONT-Nanopore RNA sequencing reads.This project is licensed under the MPL 2.0 " .
    "license. Please refer to the GitHub repo for most recent updates.", false)
    ->addPropertyPair("distribution", "https://s3.ap-northeast-1.wasabisys.com/" .
    "gigadb-datasets/live/pub/10.5524/102001_103000/102736/BoostNano-master.zip", true)
    ->addPropertyPair("releaseDate", "2025-07-23", false);
$crate->addEntity($zip);
$zipDist = new ContextualEntity("https://s3.ap-northeast-1.wasabisys.com/gigadb-datasets/live/" .
"pub/10.5524/102001_103000/102736/BoostNano-master.zip", ["DataDownload"]);
$zipDist->addPropertyPair("encodingFormat", "application/zip", false)
    ->addPropertyPair("encodingFormat", "https://www.nationalarchives.gov.uk/PRONOM/x-fmt/263", true)
    ->addPropertypair("contentSize", "2.44 MB", false);
$crate->addEntity($zipDist);
$zip->addPropertyPair("exifData", "#zipExtra", true);
$zipExtra = new ContextualEntity("#zipExtra", ["PropertyValue"]);
$zipExtra->addPropertyPair("name", "Extra Information", false)
    ->addPropertyPair("value", "Data Type: GitHub archive, File Attributes: MD5 checksum: " .
    "4b4d2ce7259e5045d89b731b7bfcf730 SWH: swh:1:snp:ee789638699e0e33ca3b1d09da5bb1f485ea7c70 license: MPL 2.0", false);
$crate->addEntity($zipExtra);

$otherFiles = new Dataset("#other-files");
$otherFiles->addPropertyPair("name", "other files", false)
    ->addPropertyPair("description", "This dataset contains too many files that are not individually described", false);
$crate->addEntity($otherFiles);

$authors = ["https://orcid.org/0000-0001-9083-6757", "#Xuan_Yang",
"https://orcid.org/0000-0003-0337-8722", "#Benjamin_Reames",
"https://orcid.org/0000-0003-1155-0959", "https://orcid.org/0000-0002-4300-455X"];
$affiliations = ["https://ror.org/01ej9dk98", "https://ror.org/01ej9dk98",
"https://ror.org/05x2bcf33", "https://ror.org/01ej9dk98",
"https://ror.org/01ej9dk98", "https://ror.org/01ej9dk98"];
$names = ["Chang JJ", "Yang X", "Teng H", "Reames B", "Corbin V", "Coin LJM"];
$affiliationNames = ["The University of Melbourne", "The University of Melbourne",
"Carnegie Mellon University", "The University of Melbourne",
"The University of Melbourne", "The University of Melbourne"];
$idx = 0;
$usedAffiliations = [];
foreach ($authors as $author) {
    $root->addPropertyPair("author", $author, true);

    $person = new Person($author);
    $person->addPropertyPair("affiliation", $affiliations[$idx], true)
        ->addPropertyPair("name", $names[$idx], false);
    $crate->addEntity($person);

    if (in_array($affiliations[$idx], $usedAffiliations)) {
        $idx++;
        continue;
    }
    $org = new Organization($affiliations[$idx]);
    $org->addPropertyPair("name", $affiliationNames[$idx], false);
    $crate->addEntity($org);
    $usedAffiliations[] = $affiliations[$idx];

    $idx++;
}

$root->addPropertyPair("citation", "https://doi.org/10.5524/100425", true);
$otherCrate = new Publication("https://doi.org/10.5524/100425", "CreativeWork");
$otherCrate->addType("Dataset")
    ->addPropertyPair("conformsTo", "https://w3id.org/ro/crate", true);
$crate->addEntity($otherCrate);


$root->addPropertyPair("funder", "https://ror.org/011kf5r70", true);
$funder = new Organization("https://ror.org/011kf5r70");
$funder->addPropertyPair("identifier", "https://ror.org/011kf5r70", false)
    ->addPropertyPair("name", "National Health and Medical Research Council", false)
    ->addPropertyPair("description", "Funding Body", false);
$crate->addEntity($funder);
$funder->addPropertyPair("exifData", "#awardee", true);
$awardee = new ContextualEntity("#awardee", ["PropertyValue"]);
$awardee->addPropertyPair("name", "Awardee", false)
    ->addPropertyPair("value", "L Coin", false);
$crate->addEntity($awardee);
$funder->addPropertyPair("exifData", "#awardId", true);
$awardId = new ContextualEntity("#awardId", ["PropertyValue"]);
$awardId->addPropertyPair("name", "Award ID", false)
    ->addPropertyPair("value", "GNT1195743", false);
$crate->addEntity($awardId);

$root->addPropertyPair("exifData", "#datasetTypes", true);
$datasetTypes = new ContextualEntity("#datasetTypes", ["PropertyValue"]);
$datasetTypes->addPropertyPair("name", "Dataset type", false)
    ->addPropertyPair("value", "Epigenomic, Bioinformatics, Software, Transcriptomic", false);
$crate->addEntity($datasetTypes);

$root->addPropertyPair("keywords", 'oxford nanopore technologies, poly(a) tail, ' .
'estimation, segmentation, direct rna sequencing', false)
    ->addPropertyPair("about", "https://nanoporetech.com/", true);

$keyword = new ContextualEntity("https://nanoporetech.com/", ["URL"]);
$keyword->addPropertyPair("name", "oxford nanopore technologies", false);
$crate->addEntity($keyword);

$rootDoi = new ContextualEntity("https://doi.org/10.5524/102736", ["PropertyValue"]);
$rootDoi->addPropertyPair("propertyID", "https://registry.identifiers.org/registry/doi", false)
    ->addPropertyPair("value", "doi:10.5524/102736", false)
    ->addPropertyPair("url", "https://doi.org/10.5524/102736", false);
$crate->addEntity($rootDoi);

$cc0 = new ContextualEntity("https://creativecommons.org/publicdomain/zero/1.0/", ["CreativeWork"]);
$cc0->addPropertyPair("name", "Creative Commons Zero v1.0 Universal", false)
    ->addPropertyPair("description", 'The person who associated a work with this deed has' .
    ' dedicated the work to the public domain by waiving all of his or her rights to the work worldwide under ' .
    'copyright law, including all related and neighboring rights, to the extent allowed by law. You can copy, ' .
    'modify, distribute and perform the work, even for commercial purposes, all without asking permission. ' .
    'See Other Information below.', false);
$crate->addEntity($cc0);

$gigaDB = new Organization("https://gigadb.org/");
$gigaDB->addPropertyPair("name", "GigaScience DataBase", false)
    ->addPropertyPair("description", "GigaDB is a data repository supporting scientific " .
    "publications in the Life/Biomedical Sciences domain. GigaDB organises and curates data from individually " .
    "publishable units into datasets, which are provided openly and in as FAIR manner as possible for the global " .
    "research community.", false)
    ->addPropertyPair("contactPoint", "mailto:database@gigasciencejournal.com", true);
$crate->addEntity($gigaDB);

$contact = new ContactPoint("mailto:database@gigasciencejournal.com");
$contact->addPropertyPair("contactType", "contact of the publisher", false)
    ->addPropertyPair("email", "database@gigasciencejournal.com", false)
    ->addPropertyPair("identifier", "database@gigasciencejournal.com", false);
$crate->addEntity($contact);

$root->addPropertyPair("exifData", "#additionalInfo1", true);
$addInfo = new ContextualEntity("#additionalInfo1", ["PropertyValue"]);
$addInfo->addPropertyPair("name", "Additional information", false)
    ->addPropertyPair("value", "https://doi.org/10.26188/c.7767503.v1", false);
$crate->addEntity($addInfo);

$root->addPropertyPair("exifData", "#additionalInfo2", true);
$addInfo = new ContextualEntity("#additionalInfo2", ["PropertyValue"]);
$addInfo->addPropertyPair("name", "Additional information", false)
    ->addPropertyPair("value", "https://registry.dome-ml.org/review/4ctuzhv3y5", false);
$crate->addEntity($addInfo);

$root->addPropertyPair("exifData", "#additionalInfo3", true);
$addInfo = new ContextualEntity("#additionalInfo3", ["PropertyValue"]);
$addInfo->addPropertyPair("name", "Additional information", false)
    ->addPropertyPair("value", "https://archive.softwareheritage.org/swh:1:snp:" .
    "1d8fdaa469108a2834a854d8249913d267fb9cfc", false);
$crate->addEntity($addInfo);

$root->addPropertyPair("exifData", "#additionalInfo4", true);
$addInfo = new ContextualEntity("#additionalInfo4", ["PropertyValue"]);
$addInfo->addPropertyPair("name", "Additional information", false)
    ->addPropertyPair("value", "https://archive.softwareheritage.org/swh:1:snp:" .
    "95b1531358ec75027da00fc8b539bce14188d30d", false);
$crate->addEntity($addInfo);

$root->addPropertyPair("exifData", "#additionalInfo5", true);
$addInfo = new ContextualEntity("#additionalInfo5", ["PropertyValue"]);
$addInfo->addPropertyPair("name", "Additional information", false)
    ->addPropertyPair("value", "https://archive.softwareheritage.org/swh:1:snp:" .
    "98b3a8996ab44283990fe707ffc44d45b2a61695", false);
$crate->addEntity($addInfo);

$root->addPropertyPair("exifData", "#additionalInfo6", true);
$addInfo = new ContextualEntity("#additionalInfo6", ["PropertyValue"]);
$addInfo->addPropertyPair("name", "Additional information", false)
    ->addPropertyPair("value", "https://archive.softwareheritage.org/swh:1:snp:" .
    "ee789638699e0e33ca3b1d09da5bb1f485ea7c70", false);
$crate->addEntity($addInfo);

$root->addPropertyPair("exifData", "#additionalInfo7", true);
$addInfo = new ContextualEntity("#additionalInfo7", ["PropertyValue"]);
$addInfo->addPropertyPair("name", "Additional information", false)
    ->addPropertyPair("value", "https://scicrunch.org/resolver/RRID:SCR_026467", false);
$crate->addEntity($addInfo);

$root->addPropertyPair("exifData", "#additionalInfo8", true);
$addInfo = new ContextualEntity("#additionalInfo8", ["PropertyValue"]);
$addInfo->addPropertyPair("name", "Additional information", false)
    ->addPropertyPair("value", "https://bio.tools/boostnano", false);
$crate->addEntity($addInfo);

$root->addPropertyPair("exifData", "#githubLink1", true);
$gitLink = new ContextualEntity("#githubLink1", ["PropertyValue"]);
$gitLink->addPropertyPair("name", "Github links", false)
    ->addPropertyPair("value", "https://github.com/haotianteng/BoostNano", false);
$crate->addEntity($gitLink);

$root->addPropertyPair("exifData", "#githubLink2", true);
$gitLink = new ContextualEntity("#githubLink2", ["PropertyValue"]);
$gitLink->addPropertyPair("name", "Github links", false)
    ->addPropertyPair("value", "https://github.com/adnaniazi/tailfindr", false);
$crate->addEntity($gitLink);

$root->addPropertyPair("exifData", "#githubLink3", true);
$gitLink = new ContextualEntity("#githubLink3", ["PropertyValue"]);
$gitLink->addPropertyPair("name", "Github links", false)
    ->addPropertyPair("value", "https://github.com/haotianteng/chiron", false);
$crate->addEntity($gitLink);

$root->addPropertyPair("exifData", "#githubLink4", true);
$gitLink = new ContextualEntity("#githubLink4", ["PropertyValue"]);
$gitLink->addPropertyPair("name", "Github links", false)
    ->addPropertyPair("value", "https://github.com/jts/nanopolish", false);
$crate->addEntity($gitLink);

$root->addPropertyPair("exifData", "#accessions", true);
$accessions = new ContextualEntity("#accessions", ["PropertyValue"]);
$accessions->addPropertyPair("name", "Accessions (data not in GigaDB)", false)
    ->addPropertyPair("value", "BioProject: PRJNA675370", false);
$crate->addEntity($accessions);

$root->addPropertyPair("exifData", "#history", true);
$history = new ContextualEntity("#history", ["PropertyValue"]);
$history->addPropertyPair("name", "History", false)
    ->addPropertyPair("value", "Date: July 29, 2025, Action: Dataset publish", false);
$crate->addEntity($history);

$errMsg = $crate->saveWithErrorMessage();
if ($errMsg !== []) {
    foreach ($errMsg as $msg) {
        echo "\n$msg";
    }
}
