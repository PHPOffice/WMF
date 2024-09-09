<?php

declare(strict_types=1);

namespace Tests\PhpOffice\WMF\Reader\WMF;

use PhpOffice\WMF\Reader\WMF\Magic;
use Tests\PhpOffice\WMF\Reader\AbstractTestReader;

class MagicTest extends AbstractTestReader
{
    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testLoad(string $file): void
    {
        $reader = new Magic();
        $this->assertTrue($reader->load($this->getResourceDir() . $file));
    }

    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testGetResource(string $file): void
    {
        $reader = new Magic();
        $reader->load($this->getResourceDir() . $file);
        $this->assertIsObject($reader->getResource());
    }

    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testOutput(string $file): void
    {
        $outputFile = $this->getResourceDir() . 'output_' . pathinfo($file, PATHINFO_FILENAME) . '.png';
        $similarFile = $this->getResourceDir() . pathinfo($file, PATHINFO_FILENAME) . '.png';

        $reader = new Magic();
        $reader->load($this->getResourceDir() . $file);
        $reader->save($outputFile, 'png');

        $this->assertImageCompare($outputFile, $similarFile, 0.02);

        @unlink($outputFile);
    }

    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testIsWMF(string $file): void
    {
        $reader = new Magic();
        $this->assertTrue($reader->isWMF($this->getResourceDir() . $file));
    }
}
