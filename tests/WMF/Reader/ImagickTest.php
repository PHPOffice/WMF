<?php

declare(strict_types=1);

namespace Tests\PhpOffice\WMF\Reader;

use Imagick as ImagickBase;
use PhpOffice\WMF\Reader\Imagick as ImagickReader;

class ImagickTest extends AbstractTestReader
{
    /**
     * @dataProvider dataProviderFiles
     */
    public function testLoad(string $file): void
    {
        $reader = new ImagickReader();
        $this->assertTrue($reader->load($this->getResourceDir() . $file));
    }

    /**
     * @dataProvider dataProviderFiles
     */
    public function testGetResource(string $file): void
    {
        $reader = new ImagickReader();
        $reader->load($this->getResourceDir() . $file);
        $this->assertInstanceOf(ImagickBase::class, $reader->getResource());
    }

    /**
     * @dataProvider dataProviderFiles
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
     * @dataProvider dataProviderFiles
     */
    public function testIsWMF(string $file): void
    {
        $reader = new ImagickReader();
        $this->assertTrue($reader->isWMF($this->getResourceDir() . $file));
    }
}
