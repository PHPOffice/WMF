<?php

declare(strict_types=1);

namespace Tests\PhpOffice\WMF\Reader;

use PhpOffice\WMF\Reader\Magic;

class MagicTest extends AbstractTestReader 
{
    /**
     * @dataProvider dataProviderFiles
     */
    public function testLoad(string $file): void
    {
        $reader = new Magic();
        $this->assertTrue($reader->load($this->getResourceDir() . $file));
    }

    /**
     * @dataProvider dataProviderFiles
     */
    public function testGetResource(string $file): void
    {
        $reader = new Magic();
        $reader->load($this->getResourceDir() . $file);
        $this->assertIsObject($reader->getResource());
    }
    /**
     * @dataProvider dataProviderFiles
     */
    public function testOutput(string $file): void
    {
        $outputFile = $this->getResourceDir() . 'output_'.pathinfo($file, PATHINFO_FILENAME).'.png';
        $similarFile = $this->getResourceDir() . pathinfo($file, PATHINFO_FILENAME).'.png';

        $reader = new Magic();
        $reader->load($this->getResourceDir() . $file);
        $reader->save($outputFile, 'png');

        $this->assertImageCompare($outputFile, $similarFile, 0.02);

        @unlink($outputFile);
    }

    /**
     * @dataProvider dataProviderFiles
     */
    public function testIsWMF(string $file): void
    {
        $reader = new Magic();
        $this->assertTrue($reader->isWMF($this->getResourceDir() .$file));
    }
}