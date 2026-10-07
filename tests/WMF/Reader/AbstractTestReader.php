<?php

declare(strict_types=1);

namespace Tests\PhpOffice\WMF\Reader;

use Imagick;
use PHPUnit\Framework\TestCase;

class AbstractTestReader extends TestCase
{
    public function getResourceDir(): string
    {
        return dirname(__DIR__, 2) . '/resources/';
    }

    public function assertImageCompare(string $expectedFile, string $outputFile, float $threshold = 0): void
    {
        $imExpected = new Imagick($expectedFile);
        $imOutput = new Imagick($outputFile);

        $result = $imExpected->compareImages($imOutput, Imagick::METRIC_MEANSQUAREERROR);
        $this->assertLessThanOrEqual($threshold, $result[1]);
    }

    /**
     * @return array<array<string>>
     */
    public static function dataProviderFilesWMF(): array
    {
        return [
            [
                'burger.wmf',
            ],
            [
                'chicken.wmf',
            ],
            [
                'fish.wmf',
            ],
            [
                'vegetable.wmf',
            ],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public static function dataProviderFilesWMFNotImplemented(): array
    {
        return [
            [
                'test_libuemf.wmf',
            ],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public static function dataProviderMediaTypeWMF(): array
    {
        return [
            [
                'gif',
                'image/gif',
            ],
            [
                'jpg',
                'image/jpeg',
            ],
            [
                'jpeg',
                'image/jpeg',
            ],
            [
                'png',
                'image/png',
            ],
            [
                'webp',
                'image/webp',
            ],
            [
                'wbmp',
                'image/vnd.wap.wbmp',
            ],
            [
                'wmf',
                'image/x-wmf',
            ],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public static function dataProviderFilesEMF(): array
    {
        return [
            [
                'computer_mail.emf',
            ],
            [
                'inkscape_shapes.emf',
            ],
        ];
    }

    /**
     * @return array<array<string>>
     */
    public static function dataProviderMediaTypeEMF(): array
    {
        return [
            [
                'gif',
                'image/gif',
            ],
            [
                'jpg',
                'image/jpeg',
            ],
            [
                'jpeg',
                'image/jpeg',
            ],
            [
                'png',
                'image/png',
            ],
            [
                'webp',
                'image/webp',
            ],
            [
                'wbmp',
                'image/vnd.wap.wbmp',
            ],
        ];
    }

    /**
     * Returns an EMF file (header of computer_mail.emf) with a record not implemented (EMR_ALPHABLEND)
     */
    public function getContentEMFNotImplemented(): string
    {
        $content = file_get_contents($this->getResourceDir() . 'computer_mail.emf');
        list(, $headerSize) = unpack('V', substr($content, 4, 4));

        return substr($content, 0, $headerSize)
            . pack('V2', 0x72, 8)
            . pack('V5', 0x0E, 20, 0, 16, 20);
    }

    /**
     * Returns a WMF file (headers of burger.wmf) with a record not implemented (META_DRAWTEXT)
     */
    public function getContentWMFNotImplemented(): string
    {
        $content = file_get_contents($this->getResourceDir() . 'burger.wmf');

        return substr($content, 0, 22 + 18)
            . pack('Vv', 3, 0x062F)
            . pack('Vv', 3, 0x0000);
    }

    /**
     * Returns a 2x1 DIB (bitmap info & bits) : the first pixel uses the color 0, the second one the color 1
     *
     * The color table contains red & blue, or indexes in the palette of the file (DIB_PAL_COLORS)
     */
    private function getDIB(int $compression, bool $hasPaletteIndexes): string
    {
        $colorTable = $hasPaletteIndexes ? "\x00\x00\x01\x00" : "\x00\x00\xFF\x00\xFF\x00\x00\x00";

        return pack('VllvvVVllVV', 40, 2, 1, 1, 1, $compression, 4, 0, 0, 2, 0) . $colorTable . "\x40\0\0\0";
    }

    /**
     * Returns an EMF file (header of inkscape_shapes.emf) with a EMR_STRETCHDIBITS record covering the image
     *
     * @param int $compression BI_RGB (0) or a compression not supported by monochrome bitmaps (BI_RLE8 : 1)
     * @param int $usage DIB_RGB_COLORS (0) or DIB_PAL_COLORS (1)
     */
    public function getContentEMFWithBitmap(int $compression, int $usage): string
    {
        $content = file_get_contents($this->getResourceDir() . 'inkscape_shapes.emf');
        list(, $headerSize) = unpack('V', substr($content, 4, 4));

        $dib = $this->getDIB($compression, $usage == 1);
        $bmiSize = strlen($dib) - 4;
        $record = pack('l4', 0, 0, 2503, 1889)
            // xDest, yDest, xSrc, ySrc, cxSrc, cySrc
            . pack('l6', 0, 0, 0, 0, 2, 1)
            // offBmi, cbBmi, offBits, cbBits, usage, SRCCOPY
            . pack('V6', 80, $bmiSize, 80 + $bmiSize, 4, $usage, 0x00CC0020)
            // cxDest, cyDest
            . pack('l2', 2504, 1890)
            . $dib;

        return substr($content, 0, $headerSize)
            . pack('V2', 0x51, 8 + strlen($record)) . $record
            . pack('V5', 0x0E, 20, 0, 16, 20);
    }

    /**
     * Returns a WMF file (headers of burger.wmf) with a META_STRETCHDIB record covering the image
     *
     * @param int $compression BI_RGB (0) or a compression not supported by monochrome bitmaps (BI_RLE8 : 1)
     * @param int $usage DIB_RGB_COLORS (0) or DIB_PAL_COLORS (1)
     */
    public function getContentWMFWithBitmap(int $compression, int $usage): string
    {
        $content = file_get_contents($this->getResourceDir() . 'burger.wmf');

        // The window of burger.wmf : origin (-1225, 1137), extent (2290, -2035)
        $params = pack('Vv', 0x00CC0020, $usage)
            // srcHeight, srcWidth, ySrc, xSrc, destHeight, destWidth, yDest, xDest
            . pack('v8', 1, 2, 0, 0, -2035 & 0xFFFF, 2290, 1137, -1225 & 0xFFFF)
            . $this->getDIB($compression, $usage == 1);

        return substr($content, 0, 22 + 18)
            . pack('Vv', 3 + strlen($params) / 2, 0x0F43) . $params
            . pack('Vv', 3, 0x0000);
    }

    public function assertMimeType(string $filename, string $expectedMimeType): void
    {
        // Use GD
        $gdInfo = getimagesize($filename);
        if (is_array($gdInfo)) {
            $this->assertEquals($expectedMimeType, $gdInfo['mime']);

            return;
        }

        // Use Imagick
        $this->assertEquals($expectedMimeType, (new Imagick($filename))->getImageMimeType());
    }
}
