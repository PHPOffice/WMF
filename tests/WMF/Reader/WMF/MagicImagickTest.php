<?php

declare(strict_types=1);

namespace Tests\PhpOffice\WMF\Reader\WMF;

use PhpOffice\WMF\Reader\WMF\GD;
use PhpOffice\WMF\Reader\WMF\Imagick;
use PhpOffice\WMF\Reader\WMF\Magic;
use PhpOffice\WMF\Reader\WMF\ReaderInterface;
use Tests\PhpOffice\WMF\Reader\AbstractTestReader;

class MagicImagickTest extends AbstractTestReader
{
    private function getReader(): ReaderInterface
    {
        $reader = new Magic();
        $reader->setBackends([
            Imagick::class,
            GD::class,
        ]);

        return $reader;
    }

    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testLoad(string $file): void
    {
        $reader = $this->getReader();
        $this->assertTrue($reader->load($this->getResourceDir() . $file));
    }

    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testLoadFromString(string $file): void
    {
        $reader = $this->getReader();
        $this->assertTrue($reader->loadFromString(file_get_contents($this->getResourceDir() . $file)));
    }

    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testGetResource(string $file): void
    {
        $reader = $this->getReader();
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

        $reader = $this->getReader();
        $reader->load($this->getResourceDir() . $file);
        $reader->save($outputFile, 'png');

        $this->assertImageCompare($outputFile, $similarFile, 0.02);

        @unlink($outputFile);
    }

    /**
     * @dataProvider dataProviderMediaType
     */
    public function testSave(string $extension, string $mediatype): void
    {
        $outputFile = $this->getResourceDir() . 'output_save.' . $extension;

        $reader = $this->getReader();
        $reader->load($this->getResourceDir() . 'burger.wmf');
        $reader->save($outputFile, $extension);
        $this->assertMimeType($outputFile, $mediatype);

        @unlink($outputFile);
    }

    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testIsWMF(string $file): void
    {
        $reader = $this->getReader();
        $reader->load($this->getResourceDir() . $file);
        $this->assertTrue($reader->isWMF());
    }

    public function testMediaType(): void
    {
        $reader = $this->getReader();
        $this->assertEquals('image/wmf', $reader->getMediaType());
    }
}
