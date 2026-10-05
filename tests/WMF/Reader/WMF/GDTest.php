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
    public function testLoadFromString(string $file): void
    {
        $reader = new GD();
        $this->assertTrue($reader->loadFromString(file_get_contents($this->getResourceDir() . $file)));
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
        $this->assertTrue($reader->save($outputFile, 'png'));

        $this->assertImageCompare($outputFile, $similarFile, 0.02);

        @unlink($outputFile);
    }

    /**
     * @dataProvider dataProviderMediaTypeWMF
     */
    public function testSave(string $extension, string $mediatype): void
    {
        $outputFile = $this->getResourceDir() . 'output_save.' . $extension;

        $reader = new GD();
        $reader->load($this->getResourceDir() . 'burger.wmf');
        $reader->save($outputFile, $extension);
        $this->assertMimeType($outputFile, $mediatype);

        @unlink($outputFile);
    }

    public function testSaveWithException(): void
    {
        $file = 'vegetable.wmf';
        $outputFile = $this->getResourceDir() . 'output_' . pathinfo($file, PATHINFO_FILENAME) . '.png';

        $this->expectException(WMFException::class);

        $reader = new GD();
        $reader->load($this->getResourceDir() . $file);
        $reader->save($outputFile, 'notanextension');
    }

    public function testSaveWithoutException(): void
    {
        $file = 'vegetable.wmf';
        $outputFile = $this->getResourceDir() . 'output_' . pathinfo($file, PATHINFO_FILENAME) . '.png';

        $reader = new GD();
        $reader->enableExceptions(false);
        $reader->load($this->getResourceDir() . $file);
        $this->assertFalse($reader->save($outputFile, 'notanextension'));
    }

    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testIsWMF(string $file): void
    {
        $reader = new GD();
        $reader->load($this->getResourceDir() . $file);
        $this->assertTrue($reader->isWMF());
    }

    public function testMediaType(): void
    {
        $reader = new GD();
        $this->assertEquals('image/wmf', $reader->getMediaType());
    }

    public function testNotImplementedWithExceptions(): void
    {
        $this->expectException(WMFException::class);
        $this->expectExceptionMessage('Reader : Function not implemented : 0x062f');

        $reader = new GD();
        $reader->loadFromString($this->getContentWMFNotImplemented());
    }

    public function testNotImplementedWithoutExceptions(): void
    {
        $reader = new GD();
        $reader->enableExceptions(false);
        $this->assertFalse($reader->loadFromString($this->getContentWMFNotImplemented()));
    }

    /**
     * The test file of libUEMF uses most of the WMF records
     */
    public function testLoadLibUEMF(): void
    {
        $outputFile = $this->getResourceDir() . 'output_test_libuemf.png';

        $reader = new GD();
        $this->assertTrue($reader->load($this->getResourceDir() . 'test_libuemf.wmf'));
        $this->assertTrue($reader->save($outputFile, 'png'));
        $this->assertMimeType($outputFile, 'image/png');

        @unlink($outputFile);
    }

    /**
     * @return array<array<int>>
     */
    public static function dataProviderBitmapColors(): array
    {
        return [
            // DIB_RGB_COLORS : the colors of the color table (red & blue)
            [0, 0xFF0000, 0x0000FF],
            // DIB_PAL_COLORS : the palette of the file is not known, a gray scale is used
            [1, 0x000000, 0xFFFFFF],
        ];
    }

    /**
     * @dataProvider dataProviderBitmapColors
     */
    public function testBitmapColors(int $usage, int $leftColor, int $rightColor): void
    {
        $reader = new GD();
        $this->assertTrue($reader->loadFromString($this->getContentWMFWithBitmap(0, $usage)));
        $image = $reader->getResource();

        $this->assertEquals($leftColor, imagecolorat($image, (int) (imagesx($image) / 4), (int) (imagesy($image) / 2)) & 0xFFFFFF);
        $this->assertEquals($rightColor, imagecolorat($image, (int) (imagesx($image) * 3 / 4), (int) (imagesy($image) / 2)) & 0xFFFFFF);
    }

    public function testBitmapNotSupportedWithExceptions(): void
    {
        $this->expectException(WMFException::class);
        $this->expectExceptionMessage('Reader : Bitmap not supported : 0x0f43');

        $reader = new GD();
        $reader->loadFromString($this->getContentWMFWithBitmap(1, 0));
    }

    public function testBitmapNotSupportedWithoutExceptions(): void
    {
        $reader = new GD();
        $reader->enableExceptions(false);
        $this->assertFalse($reader->loadFromString($this->getContentWMFWithBitmap(1, 0)));
    }
}
