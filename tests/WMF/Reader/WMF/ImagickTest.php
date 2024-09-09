<?php

declare(strict_types=1);

namespace Tests\PhpOffice\WMF\Reader\WMF;

use Imagick as ImagickBase;
use PhpOffice\WMF\Exception\WMFException;
use PhpOffice\WMF\Reader\WMF\Imagick as ImagickReader;
use Tests\PhpOffice\WMF\Reader\AbstractTestReader;

class ImagickTest extends AbstractTestReader
{
    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testLoad(string $file): void
    {
        $reader = new ImagickReader();
        $this->assertTrue($reader->load($this->getResourceDir() . $file));
    }

    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testGetResource(string $file): void
    {
        $reader = new ImagickReader();
        $reader->load($this->getResourceDir() . $file);
        $this->assertInstanceOf(ImagickBase::class, $reader->getResource());
    }

    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testOutput(string $file): void
    {
        $outputFile = $this->getResourceDir() . 'output_' . pathinfo($file, PATHINFO_FILENAME) . '.png';
        $similarFile = $this->getResourceDir() . pathinfo($file, PATHINFO_FILENAME) . '.png';

        $reader = new ImagickReader();
        $reader->load($this->getResourceDir() . $file);
        $reader->save($outputFile, 'png');

        $this->assertImageCompare($outputFile, $similarFile);

        @unlink($outputFile);
    }

    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testIsWMF(string $file): void
    {
        $reader = new ImagickReader();
        $this->assertTrue($reader->isWMF($this->getResourceDir() . $file));
    }

    /**
     * @dataProvider dataProviderFilesWMFNotImplemented
     */
    public function testNotImplemented(string $file): void
    {
        $this->expectException(WMFException::class);

        $reader = new ImagickReader();
        $reader->load($this->getResourceDir() . $file);
    }
}
