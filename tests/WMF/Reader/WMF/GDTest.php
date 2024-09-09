<?php

declare(strict_types=1);

namespace Tests\PhpOffice\WMF\Reader\WMF;

use GdImage;
use PhpOffice\WMF\Exception\WMFException;
use PhpOffice\WMF\Reader\WMF\GD;
use Tests\PhpOffice\WMF\Reader\AbstractTestReader;

class GDTest extends AbstractTestReader
{
    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testLoad(string $file): void
    {
        $reader = new GD();
        $this->assertTrue($reader->load($this->getResourceDir() . $file));
    }

    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testGetResource(string $file): void
    {
        $reader = new GD();
        $reader->load($this->getResourceDir() . $file);
        if (\PHP_VERSION_ID < 80000) {
            $this->assertIsResource($reader->getResource());
        } else {
            /* @phpstan-ignore-next-line */
            $this->assertInstanceOf(GdImage::class, $reader->getResource());
        }
    }

    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testOutput(string $file): void
    {
        $outputFile = $this->getResourceDir() . 'output_' . pathinfo($file, PATHINFO_FILENAME) . '.png';
        $similarFile = $this->getResourceDir() . pathinfo($file, PATHINFO_FILENAME) . '.png';

        $reader = new GD();
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
        $reader = new GD();
        $this->assertTrue($reader->isWMF($this->getResourceDir() . $file));
    }

    /**
     * @dataProvider dataProviderFilesWMFNotImplemented
     */
    public function testNotImplemented(string $file): void
    {
        $this->expectException(WMFException::class);

        $reader = new GD();
        $reader->load($this->getResourceDir() . $file);
    }
}
