<?php

namespace ROCrate;

use Exceptions\ROCrateException;

/**
 * Helps generate the ro-crate website for human-readability
 */
class ROCratePreviewGenerator
{
    public static $rootId = './';

    /**
     * Generates the ro-crate website file
     * @param string $directory The base directory
     * @return void
     */
    public static function generatePreview(string $directory): void
    {
        $basePath = realpath($directory) ?: $directory;

        // Configuration
        define('INPUT_JSON', $basePath . '/ro-crate-metadata.json');
        // !!!
        define('OUTPUT_HTML', $basePath . '/ro-crate-preview-out.html');
        define('CSS_PATH', '/ro-crate-preview_files/style.css');

        // Load and validate JSON
        if (!file_exists(INPUT_JSON)) {
            die("Error: " . INPUT_JSON . " not found");
        }

        $json = file_get_contents(INPUT_JSON);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            die("Error: Invalid JSON - " . json_last_error_msg());
        }

        // a dummy instance to call the non-static methods
        $generator = new ROCratePreviewGenerator();

        // Build context term URIs
        $termUris = $generator->buildTermUris($data['@context'] ?? []);
        $entities = $generator->indexEntities($data['@graph'] ?? []);
        $rootEntity = $generator->findRootEntity($entities);

        // Generate HTML
        $html = $generator->generateHTML($rootEntity, $entities, $termUris, $basePath);
        file_put_contents(OUTPUT_HTML, $html);

        echo "Successful Creation of Preview file";
    }

    // Helper functions !!!
    /**
     * Builds context term URIs
     * @param mixed $context The context extracted from the metadata file
     * @return array The term URIs
     */
    public function buildTermUris($context): array
    {
        $termUris = [];
        if (is_array($context)) {
            foreach ($context as $key => $value) {
                if (is_string($value)) {
                    $termUris[$key] = $value;
                } elseif (is_array($value) && isset($value['@id'])) {
                    $termUris[$key] = $value['@id'];
                }
            }
        }
        return $termUris;
    }

    /**
     * Indices the entities using their Ids
     * @param array $graph The entities extracted from the metadata file
     * @return array The array of indiced entities
     */
    public function indexEntities(array $graph): array
    {
        $index = [];
        foreach ($graph as $entity) {
            $index[$entity['@id']] = $entity;
        }
        return $index;
    }

    /**
     * Finds the root data entity
     * @param array $entities The indiced entities
     */
    public function findRootEntity(array $entities)
    {
        foreach ($entities as $entityData) {
            $conditionOne = str_contains($entityData['@id'], "ro-crate-metadata.json");
            $conditionTwo = array_key_exists("conformsTo", $entityData);
            if ($conditionOne && $conditionTwo) {
                global $rootId;
                $rootId = $entityData['about']['@id'];
                break;
            }
        }

        foreach ($entities as $entity) {
            if (($entity['@id'] ?? '') === $rootId) {
                return $entity;
            }
        }
        return reset($entities) ?: [];
    }

    /**
     * Generates the HTML file for the preview
     * @param mixed $rootEntity The root data entity
     * @param mixed $entities The indiced entities array
     * @param mixed $termUris The term URIs
     * @param mixed $basePath The base path
     * @return bool|string The HTML file as a string
     */
    public function generateHTML($rootEntity, $entities, $termUris, $basePath): string
    {
        ob_start(); ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>RO-Crate Preview: <?= htmlspecialchars($rootEntity['name'] ?? 'Untitled') ?></title>
        <link rel="stylesheet" href="<?= file_exists(CSS_PATH) ? CSS_PATH : '' ?>">
        <style>
            :root {
                --primary: #0366d6;
                --primary-dark: #0356b6;
                --secondary: #6c757d;
                --light: #f8f9fa;
                --border: #dee2e6;
                --success: #28a745;
            }
            * { box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                line-height: 1.6;
                color: #212529;
                background-color: #f5f7fa;
                margin: 0;
                padding: 0;
            }
            header {
                background: linear-gradient(135deg, var(--primary), var(--primary-dark));
                color: white;
                padding: 2rem 0;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                margin-bottom: 2rem;
                padding-left: 50px;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 0 1.5rem;
            }
            .page-title {
                display: flex;
                align-items: center;
                gap: 1rem;
            }
            .logo {
                font-size: 2.5rem;
                filter: drop-shadow(0 2px 3px rgba(0,0,0,0.2));
            }
            h1, h2, h3, h4 {
                margin-top: 0;
                font-weight: 600;
            }
            h1 { font-size: 2.2rem; }
            h2 {
                font-size: 1.8rem;
                color: var(--primary);
                padding-bottom: 0.5rem;
                border-bottom: 2px solid var(--border);
                margin-bottom: 1.5rem;
            }
            h3 { font-size: 1.5rem; margin-bottom: 1rem; }
            section {
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 15px rgba(0,0,0,0.05);
                padding: 2rem;
                margin-bottom: 2.5rem;
            }
            .entity-card {
                background: var(--light);
                padding: 1.5rem;
                border-radius: 6px;
                margin-bottom: 1.5rem;
                border-left: 4px solid var(--primary);
                transition: transform 0.2s;
            }
            .entity-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            }
            .property-list dt {
                font-weight: 600;
                margin-top: 0.8rem;
                color: var(--primary-dark);
            }
            .property-list dd {
                margin-left: 0;
                margin-bottom: 0.5rem;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            }
            th, td {
                padding: 1rem;
                text-align: left;
                border-bottom: 1px solid var(--border);
            }
            th {
                background-color: var(--primary);
                color: white;
                font-weight: 600;
            }
            tr:nth-child(even) {
                background-color: rgba(248, 249, 250, 0.5);
            }
            tr:hover {
                background-color: rgba(3, 102, 214, 0.05);
            }
            .badge {
                display: inline-block;
                padding: 0.3em 0.6em;
                border-radius: 4px;
                background: var(--primary);
                color: white;
                font-size: 0.85em;
                font-weight: 500;
                margin-right: 0.5em;
            }
            .badge-dataset { background: #6610f2; }
            .badge-file { background: var(--success); }
            .badge-person { background: #e83e8c; }
            .badge-org { background: #fd7e14; }
            .badge-other { background: var(--secondary); }
            .entity-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 1.5rem;
            }
            a {
                color: var(--primary);
                text-decoration: none;
            }
            a:hover {
                text-decoration: underline;
            }
            .text-muted {
                color: var(--secondary);
            }
            footer {
                text-align: center;
                padding: 2.5rem 0;
                color: var(--secondary);
                font-size: 0.9rem;
                border-top: 1px solid var(--border);
                margin-top: 2rem;
            }
            .back-to-top {
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: var(--primary);
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                cursor: pointer;
                opacity: 0.8;
                transition: opacity 0.3s;
            }
            .back-to-top:hover {
                opacity: 1;
            }
            @media (max-width: 768px) {
                .entity-grid {
                    grid-template-columns: 1fr;
                }
                section {
                    padding: 1.5rem;
                }
            }
        </style>
    </head>
    <body>
        <header>
            <h1><?= htmlspecialchars($rootEntity['name'] ?? 'RO-Crate Preview') ?></h1>
            <?php if (isset($rootEntity['description'])) : ?>
                <p><?= htmlspecialchars($rootEntity['description']) ?></p>
            <?php endif; ?>
        </header>

        <main>
            <section id="<?= htmlspecialchars($rootEntity['@id']) ?>">
                <h2>Root Data Entity</h2>
                <?= ROCratePreviewGenerator::renderEntity($rootEntity, $entities, $termUris, $basePath) ?>
            </section>

            <?php foreach ($entities as $id => $entity) :
                global $rootId;
                if ($id === $rootId) {
                    continue;
                } ?>
                <section id="<?= htmlspecialchars($id) ?>">
                    <h2><?= htmlspecialchars($entity['name'] ?? $entity['@id']) ?></h2>
                    <?= ROCratePreviewGenerator::renderEntity($entity, $entities, $termUris, $basePath) ?>
                </section>
            <?php endforeach; ?>
        </main>

        <footer>
            <p>Generated from RO-Crate metadata on <?= date('Y-m-d') ?></p>
        </footer>
    </body>
    </html>
        <?php
        return ob_get_clean();
    }

    public static function renderEntity($entity, $entities, $termUris, $basePath, $depth = 0)
    {
        if ($depth > 3) {
            return '<div class="error">Embedding depth exceeded</div>';
        }

        $html = '<ul>';
        foreach ($entity as $key => $value) {
            $keyHtml = ROCratePreviewGenerator::renderKey($key, $termUris);
            $valStr = ROCratePreviewGenerator::renderValue($value, $entities, $termUris, $depth);

            $values = explode(' %%$$%%$$** ', $valStr);

            // if we can resolve the key from the default context, we attach [?] hyperlink
            $contextData = json_decode(file_get_contents($basePath . "/context.jsonld"), true)['@context'];
            if (array_key_exists($key, $contextData)) {
                $resolvedKey = $contextData[$key];

                if (is_array($values)) {
                    $keyFirst = "<li><span class=\"property\">$keyHtml <a href=$resolvedKey> [?] </a> </span>:";
                    foreach ($values as $valueHtml) {
                        // if value is id, we make it hyperlink and show name if name exists in the entity
                        $conditionOne = (!is_array($valueHtml)) && (strcmp($key, '@id') !== 0);
                        $conditionTwo = (array_key_exists($valueHtml, $entities));
                        if ($conditionOne && $conditionTwo) {
                            $temp = htmlspecialchars($entities[$valueHtml]['name'] ?? $valueHtml);
                            if (strcmp($temp, "") == 0) {
                                $temp = $valueHtml;
                            }
                            $html .= $keyFirst . " <a href=#$valueHtml> $temp </a></li>";
                        } elseif (ROCrate::isValidUri($valueHtml)) {
                            $html .= $keyFirst . " <a href=$valueHtml> $valueHtml </a></li>";
                        } else {
                            $html .= $keyFirst . " $valueHtml</li>";
                        }

                        $keyFirst = ", ";
                    }
                }
                //else {
                //    $valueHtml = $values;
                //    // if value is id, we make it hyperlink and show name if name exists in the entity
                //    if ((!is_array($valueHtml)) && (strcmp($key, '@id') !== 0)
                // && (array_key_exists($valueHtml, $entities))) {
                //        $temp = htmlspecialchars($entities[$valueHtml]['name'] ?? $valueHtml);
                //        $html .= "<li><span class=\"property\"> $keyHtml <a href=$resolvedKey>
                //  [?] </a> </span>: <a href=#$valueHtml> $temp </a></li>";
                //    }
                //    else $html .= "<li><span class=\"property\">$keyHtml <a href=$resolvedKey>
                //  [?] </a> </span>: $valueHtml</li>";
                //}
            } else {
                if (is_array($values)) {
                    $keyFirst = "<li><span class=\"property\"> $keyHtml </span>:";
                    foreach ($values as $valueHtml) {
                        // if value is id, we make it hyperlink and show name if name exists in the entity
                        $conditionOne = (!is_array($valueHtml));
                        $conditionTwo = (strcmp($key, '@id') !== 0);
                        $conditionThree = (array_key_exists($valueHtml, $entities));
                        if ($conditionOne && $conditionTwo && $conditionThree) {
                            $temp = htmlspecialchars($entities[$valueHtml]['name'] ?? $valueHtml);
                            if (strcmp($temp, "") == 0) {
                                $temp = $valueHtml;
                            }
                            $html .= $keyFirst . " <a href=#$valueHtml> $temp </a></li>";
                        } elseif (ROCrate::isValidUri($valueHtml)) {
                            $html .= $keyFirst . " <a href=$valueHtml> $valueHtml </a></li>";
                        } else {
                            $html .= $keyFirst . " $valueHtml</li>";
                        }

                        $keyFirst = ", ";
                    }
                }
                //else {
                //    $valueHtml = $values;
                //    // if value is id, we make it hyperlink and show name if name exists in the entity
                //    if ((!is_array($valueHtml)) && (strcmp($key, '@id') !== 0)
                // && (array_key_exists($valueHtml, $entities))) {
                //        $temp = htmlspecialchars($entities[$valueHtml]['name'] ?? $valueHtml);
                //        $html .= "<li><span class=\"property\"> $keyHtml </span>: <a href=#$valueHtml>
                //  $temp </a></li>";
                //   }
                //    else $html .= "<li><span class=\"property\">$keyHtml</span>: $valueHtml</li>";
                //}
            }
        }
        $html .= '</ul>';
        return $html;
    }

    //!!!
    /**
     * Renders the key
     * @param mixed $key The key to render
     * @param mixed $termUris The term URIs
     * @return string The rendered key
     */
    public static function renderKey($key, $termUris): string
    {
        if (isset($termUris[$key])) {
            return sprintf(
                '<a href="%s" title="Term definition" class="external-link">%s</a>',
                htmlspecialchars($termUris[$key]),
                htmlspecialchars($key)
            );
        }
        return htmlspecialchars($key);
    }

    //!!!
    /**
     * Renders the value
     * @param mixed $value The value to render
     * @param mixed $entities The indiced entities array
     * @param mixed $termUris The term URIs
     * @param mixed $depth The depth
     * @return string The rendered value as string
     */
    public static function renderValue($value, $entities, $termUris, $depth): string
    {
        if (is_array($value)) {
            $values = [];
            foreach ($value as $item) {
                $values[] = ROCratePreviewGenerator::renderValue($item, $entities, $termUris, $depth);
            }
            return implode(' %%$$%%$$** ', $values);
        }

        if (is_object($value)) {
            $value = (array)$value;
        }

        if (is_array($value) && isset($value['@id'])) {
            $id = $value['@id'];

            // Handle external URIs
            if (filter_var($id, FILTER_VALIDATE_URL)) {
                return sprintf(
                    '<a href="%s" class="external-link">%s</a>',
                    htmlspecialchars($id),
                    htmlspecialchars($id)
                );
            }

            // Handle local entities
            if (isset($entities[$id])) {
                $target = $entities[$id];
                if (isset($target['name'])) {
                    return sprintf(
                        '<a href="#%s">%s</a>',
                        htmlspecialchars($id),
                        htmlspecialchars($target['name'])
                    );
                } else {
                    return sprintf(
                        '<div class="embedded">%s</div>',
                        ROCratePreviewGenerator::renderEntity($target, $entities, $termUris, $depth + 1)
                    );
                }
            }
        }

        // Default handling
        return htmlspecialchars(is_array($value) ? json_encode($value) : $value);
    }
}
