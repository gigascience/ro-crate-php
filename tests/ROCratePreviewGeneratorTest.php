<?php

declare(strict_types=1);

namespace Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use ROCrate\ROCrate;
use ROCrate\ROCratePreviewGenerator;

/**
 * Summary of ROCratePreviewGeneratorTest
 */
class ROCratePreviewGeneratorTest extends TestCase
{
    /**
     * Tests whether the preview HTML file is successfully generated
     * @return void
     */
    public function testGeneration(): void
    {
        $flag = true;

        try {
            ROCratePreviewGenerator::generatePreview(__DIR__ . '/../resources');
        } catch (Exception $e) {
            $flag = false;
        }

        $this->assertEquals(true, $flag);
    }
}
