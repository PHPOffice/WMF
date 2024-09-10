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
    public function testLoadFromString(string $file): void
    {
        $reader = new ImagickReader();
        $this->assertTrue($reader->loadFromString(file_get_contents($this->getResourceDir() . $file)));
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
        $this->assertTrue($reader->save($outputFile, 'png'));

        $this->assertImageCompare($outputFile, $similarFile);

        @unlink($outputFile);
    }

    public function testSaveWithException(): void
    {
        $file = 'vegetable.wmf';
        $outputFile = $this->getResourceDir() . 'output_' . pathinfo($file, PATHINFO_FILENAME) . '.png';

        $this->expectException(WMFException::class);

        $reader = new ImagickReader();
        $reader->load($this->getResourceDir() . $file);
        $reader->save($outputFile, 'notanextension');
    }

    public function testSaveWithoutException(): void
    {
        $file = 'vegetable.wmf';
        $outputFile = $this->getResourceDir() . 'output_' . pathinfo($file, PATHINFO_FILENAME) . '.png';

        $reader = new ImagickReader();
        $reader->enableExceptions(false);
        $reader->load($this->getResourceDir() . $file);
        $this->assertFalse($reader->save($outputFile, 'notanextension'));
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
    public function testNotImplementedWithExceptions(string $file): void
    {
        $this->expectException(WMFException::class);

        $reader = new ImagickReader();
        $reader->load($this->getResourceDir() . $file);
    }

    /**
     * @dataProvider dataProviderFilesWMFNotImplemented
     */
    public function testNotImplementedWithoutExceptions(string $file): void
    {
        $reader = new ImagickReader();
        $reader->enableExceptions(false);
        $this->assertFalse($reader->load($this->getResourceDir() . $file));
    }
}
