<?php

declare(strict_types=1);

namespace Tests\PhpOffice\WMF\Reader\EMF;

use GdImage;
use PhpOffice\WMF\Exception\WMFException;
use PhpOffice\WMF\Reader\EMF\GD;
use Tests\PhpOffice\WMF\Reader\AbstractTestReader;

class GDTest extends AbstractTestReader
{
    /**
     * @dataProvider dataProviderFilesEMF
     */
    public function testLoad(string $file): void
    {
        $reader = new GD();
        $this->assertTrue($reader->load($this->getResourceDir() . $file));
    }

    /**
     * @dataProvider dataProviderFilesEMF
     */
    public function testLoadFromString(string $file): void
    {
        $reader = new GD();
        $this->assertTrue($reader->loadFromString(file_get_contents($this->getResourceDir() . $file)));
    }

    /**
     * @dataProvider dataProviderFilesWMF
     */
    public function testLoadWMF(string $file): void
    {
        $reader = new GD();
        $this->assertFalse($reader->load($this->getResourceDir() . $file));
        $this->assertFalse($reader->isEMF());
    }

    /**
     * @dataProvider dataProviderFilesEMF
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
     * @dataProvider dataProviderFilesEMF
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
     * @dataProvider dataProviderMediaTypeEMF
     */
    public function testSave(string $extension, string $mediatype): void
    {
        $outputFile = $this->getResourceDir() . 'output_save.' . $extension;

        $reader = new GD();
        $reader->load($this->getResourceDir() . 'computer_mail.emf');
        $reader->save($outputFile, $extension);
        $this->assertMimeType($outputFile, $mediatype);

        @unlink($outputFile);
    }

    public function testSaveEMF(): void
    {
        $file = $this->getResourceDir() . 'computer_mail.emf';
        $outputFile = $this->getResourceDir() . 'output_save.emf';

        $reader = new GD();
        $reader->load($file);
        $this->assertTrue($reader->save($outputFile, 'emf'));
        $this->assertFileEquals($file, $outputFile);

        @unlink($outputFile);
    }

    public function testSaveWithException(): void
    {
        $file = 'computer_mail.emf';
        $outputFile = $this->getResourceDir() . 'output_' . pathinfo($file, PATHINFO_FILENAME) . '.png';

        $this->expectException(WMFException::class);

        $reader = new GD();
        $reader->load($this->getResourceDir() . $file);
        $reader->save($outputFile, 'notanextension');
    }

    public function testSaveWithoutException(): void
    {
        $file = 'computer_mail.emf';
        $outputFile = $this->getResourceDir() . 'output_' . pathinfo($file, PATHINFO_FILENAME) . '.png';

        $reader = new GD();
        $reader->enableExceptions(false);
        $reader->load($this->getResourceDir() . $file);
        $this->assertFalse($reader->save($outputFile, 'notanextension'));
    }

    /**
     * @dataProvider dataProviderFilesEMF
     */
    public function testIsEMF(string $file): void
    {
        $reader = new GD();
        $reader->load($this->getResourceDir() . $file);
        $this->assertTrue($reader->isEMF());
    }

    public function testMediaType(): void
    {
        $reader = new GD();
        $this->assertEquals('image/emf', $reader->getMediaType());
    }

    public function testNotImplementedWithExceptions(): void
    {
        $this->expectException(WMFException::class);
        $this->expectExceptionMessage('Reader : Function not implemented : 0x0072');

        $reader = new GD();
        $reader->loadFromString($this->getContentEMFNotImplemented());
    }

    public function testNotImplementedWithoutExceptions(): void
    {
        $reader = new GD();
        $reader->enableExceptions(false);
        $this->assertFalse($reader->loadFromString($this->getContentEMFNotImplemented()));
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
        $this->assertTrue($reader->loadFromString($this->getContentEMFWithBitmap(0, $usage)));
        $image = $reader->getResource();

        $this->assertEquals($leftColor, imagecolorat($image, (int) (imagesx($image) / 4), (int) (imagesy($image) / 2)) & 0xFFFFFF);
        $this->assertEquals($rightColor, imagecolorat($image, (int) (imagesx($image) * 3 / 4), (int) (imagesy($image) / 2)) & 0xFFFFFF);
    }

    public function testBitmapNotSupportedWithExceptions(): void
    {
        $this->expectException(WMFException::class);
        $this->expectExceptionMessage('Reader : Bitmap not supported : 0x0051');

        $reader = new GD();
        $reader->loadFromString($this->getContentEMFWithBitmap(1, 0));
    }

    public function testBitmapNotSupportedWithoutExceptions(): void
    {
        $reader = new GD();
        $reader->enableExceptions(false);
        $this->assertFalse($reader->loadFromString($this->getContentEMFWithBitmap(1, 0)));
    }
}
