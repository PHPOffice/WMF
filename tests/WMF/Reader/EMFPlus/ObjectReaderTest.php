<?php

declare(strict_types=1);

namespace Tests\PhpOffice\WMF\Reader\EMFPlus;

use PhpOffice\WMF\Exception\WMFException;
use PhpOffice\WMF\Reader\EMFPlus\ObjectReader;
use PHPUnit\Framework\TestCase;

class ObjectReaderTest extends TestCase
{
    /**
     * Graphics version of EMF+ objects
     */
    private const VERSION = 0xDBC01002;

    /**
     * @return array<string, mixed>
     */
    private function read(int $type, string $data): array
    {
        return (new ObjectReader())->read($type, $data);
    }

    private function getSolidBrush(int $argb): string
    {
        return pack('V3', self::VERSION, ObjectReader::BRUSH_SOLID, $argb);
    }

    private function getPath(): string
    {
        // A closed square : start, line, line, line & close
        return pack('V2v2', self::VERSION, 4, 0, 0)
            . pack('g8', 0, 0, 10, 0, 10, 10, 0, 10)
            . pack('C4', 0, 1, 1, 0x81);
    }

    public function testSolidBrush(): void
    {
        $this->assertEquals(
            ['type' => 'brush', 'style' => 0, 'color' => [0x11, 0x22, 0x33, 0x80]],
            $this->read(ObjectReader::OBJECT_BRUSH, $this->getSolidBrush(0x80112233))
        );
    }

    public function testLinearGradientBrush(): void
    {
        // From black (x = 0) to white (x = 10), tiled
        $data = pack('V4', self::VERSION, ObjectReader::BRUSH_LINEAR_GRADIENT, 0, 0)
            . pack('g4', 0, 0, 10, 10)
            . pack('V4', 0xFF000000, 0xFFFFFFFF, 0, 0);
        $brush = $this->read(ObjectReader::OBJECT_BRUSH, $data);

        $this->assertEquals([0, 0, 0, 255], $brush['shader'](0, 5));
        $this->assertEquals([128, 128, 128, 255], $brush['shader'](5, 5));
        // Tiled
        $this->assertEquals([128, 128, 128, 255], $brush['shader'](15, 5));
    }

    public function testHatchBrush(): void
    {
        // HatchStyleHorizontal : red lines on white
        $data = pack('V5', self::VERSION, ObjectReader::BRUSH_HATCH, 0, 0xFFFF0000, 0xFFFFFFFF);
        $brush = $this->read(ObjectReader::OBJECT_BRUSH, $data);

        $this->assertEquals('device', $brush['space']);
        $this->assertEquals([255, 0, 0, 255], $brush['shader'](3, 8));
        $this->assertEquals([255, 255, 255, 255], $brush['shader'](3, 9));
    }

    public function testPen(): void
    {
        // Start cap round, end cap arrow anchor, join bevel, dashes
        $data = pack('V4', self::VERSION, 0, 0x0002 | 0x0004 | 0x0008 | 0x0020, 0) . pack('g', 2.0)
            . pack('l4', 2, 0x14, 1, 1)
            . $this->getSolidBrush(0xFF0000FF);
        $pen = $this->read(ObjectReader::OBJECT_PEN, $data);

        $this->assertEquals('pen', $pen['type']);
        $this->assertEquals(2.0, $pen['width']);
        $this->assertEquals(0x0000, $pen['startCap']);
        $this->assertCount(3, $pen['endCapShape']);
        $this->assertEquals(0x00011000, $pen['style']);
        $this->assertEquals([3, 1], $pen['dashes']);
        $this->assertEquals([0, 0, 255, 255], $pen['color']);
    }

    public function testPenWithArrowCap(): void
    {
        // Custom end cap : adjustable arrow (width 3, height 4, middle inset 1)
        $cap = pack('V2', self::VERSION, 1) . pack('g3', 3, 4, 1) . str_repeat("\0", 40);
        $data = pack('V3', self::VERSION, 0, 0x1000) . pack('V', 0) . pack('g', 1.0)
            . pack('V', strlen($cap)) . $cap
            . $this->getSolidBrush(0xFF000000);
        $pen = $this->read(ObjectReader::OBJECT_PEN, $data);

        $this->assertEquals([[0, 0], [1.5, -4], [0, -3], [-1.5, -4]], $pen['endCapShape']);
    }

    public function testPath(): void
    {
        $path = $this->read(ObjectReader::OBJECT_PATH, $this->getPath());

        $this->assertEquals([
            ['points' => [[0, 0], [10, 0], [10, 10], [0, 10]], 'types' => [0, 1, 1, 1], 'closed' => true],
        ], $path['figures']);
    }

    public function testRegion(): void
    {
        // Union of a rectangle & a path
        $path = $this->getPath();
        $data = pack('V3', self::VERSION, 2, ObjectReader::REGION_UNION)
            . pack('V', ObjectReader::REGION_RECT) . pack('g4', 0, 0, 5, 5)
            . pack('V2', ObjectReader::REGION_PATH, strlen($path)) . $path;
        $region = $this->read(ObjectReader::OBJECT_REGION, $data);

        $this->assertEquals(ObjectReader::REGION_UNION, $region['node']['type']);
        $this->assertEquals([0, 0, 5, 5], $region['node']['left']['rect']);
        $this->assertCount(1, $region['node']['right']['path']['figures']);
    }

    public function testImageARGB(): void
    {
        // 2x1 bitmap : opaque red, half transparent blue
        $data = pack('V2l3V2', self::VERSION, 1, 2, 1, 8, 0x0026200A, 0) . "\x00\x00\xFF\xFF\xFF\x00\x00\x80";
        $image = $this->read(ObjectReader::OBJECT_IMAGE, $data);

        $this->assertFalse($image['isMetafile']);
        $this->assertEquals(0xFF0000, $image['bitmap']['pixels'][0][0]);
        $this->assertEquals(0x0000FF | (63 << 24), $image['bitmap']['pixels'][0][1]);
    }

    public function testImageIndexed(): void
    {
        // 2x1 bitmap, 8bpp with a palette (green, blue)
        $data = pack('V2l3V2', self::VERSION, 1, 2, 1, 4, 0x00030803, 0)
            . pack('V4', 0, 2, 0xFF00FF00, 0xFF0000FF)
            . "\x01\x00\x00\x00";
        $image = $this->read(ObjectReader::OBJECT_IMAGE, $data);

        $this->assertEquals([[0x0000FF, 0x00FF00]], $image['bitmap']['pixels']);
    }

    public function testImageMetafile(): void
    {
        // A placeable WMF file, rendered with the WMF reader
        $wmf = (string) file_get_contents(dirname(__DIR__, 3) . '/resources/burger.wmf');
        $data = pack('V4', self::VERSION, 2, 2, strlen($wmf) - 22) . substr($wmf, 0, 22) . "\0\0" . substr($wmf, 22);
        $image = $this->read(ObjectReader::OBJECT_IMAGE, $data);

        $this->assertTrue($image['isMetafile']);
        $this->assertEquals(165, $image['bitmap']['width']);
        $this->assertEquals(147, $image['bitmap']['height']);
    }

    public function testFont(): void
    {
        // 12 points, bold & italic
        $data = pack('V', self::VERSION) . pack('g', 12) . pack('V', 3) . pack('l', 3) . pack('V2', 0, 5) . "A\0r\0i\0a\0l\0";
        $font = $this->read(ObjectReader::OBJECT_FONT, $data);

        $this->assertEquals(12.0, $font['emSize']);
        $this->assertEquals(3, $font['unit']);
        $this->assertEquals(700, $font['weight']);
        $this->assertTrue($font['italic']);
        $this->assertEquals('Arial', $font['face']);
    }

    public function testObjectNotImplemented(): void
    {
        $this->expectException(WMFException::class);
        $this->expectExceptionMessage('Reader : EMF+ object not implemented : 10');

        $this->read(10, '');
    }
}
