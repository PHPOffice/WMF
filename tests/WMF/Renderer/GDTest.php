<?php

declare(strict_types=1);

namespace Tests\PhpOffice\WMF\Renderer;

use GdImage;
use PhpOffice\WMF\Renderer\GD;
use PhpOffice\WMF\Renderer\Rasterizer;
use PHPUnit\Framework\TestCase;

class GDTest extends TestCase
{
    /**
     * @phpstan-ignore-next-line
     *
     * @param GdImage|resource $image
     */
    private function assertColorAt($image, int $x, int $y, int $expected): void
    {
        $this->assertEquals(sprintf('%06X', $expected), sprintf('%06X', imagecolorat($image, $x, $y) & 0xFFFFFF), sprintf('Color at (%d, %d)', $x, $y));
    }

    /**
     * @param array<int> $color
     *
     * @return array<string, mixed>
     */
    private function getBrush(array $color): array
    {
        return ['type' => 'brush', 'style' => 0, 'color' => $color];
    }

    public function testRender(): void
    {
        $image = (new GD(20, 10))->render();

        $this->assertEquals(20, imagesx($image));
        $this->assertEquals(10, imagesy($image));
        $this->assertColorAt($image, 5, 5, 0xFFFFFF);
    }

    public function testBigImageIsScaledDown(): void
    {
        $image = (new GD(8000, 4000))->render();

        $this->assertLessThanOrEqual(16000000, imagesx($image) * imagesy($image));
        $this->assertEqualsWithDelta(2, imagesx($image) / imagesy($image), 0.01);
    }

    public function testRectangle(): void
    {
        $renderer = new GD(20, 20);
        $renderer->selectObject(GD::getStockObject(8))
            ->selectObject($this->getBrush([255, 0, 0]))
            ->rectangle(5, 5, 15, 15);
        $image = $renderer->render();

        $this->assertColorAt($image, 10, 10, 0xFF0000);
        $this->assertColorAt($image, 2, 2, 0xFFFFFF);
        $this->assertColorAt($image, 17, 17, 0xFFFFFF);
    }

    public function testMapping(): void
    {
        // The window (0, 0) - (200, 100) is mapped to the image, with the y axis going up
        $renderer = new GD(20, 10);
        $renderer->setMapMode(GD::MM_ANISOTROPIC)
            ->setWindowOrg(0, 100)
            ->setWindowExt(200, -100)
            ->setViewportExt(20, 10)
            ->selectObject(GD::getStockObject(8))
            ->selectObject($this->getBrush([0, 0, 255]))
            // Bottom left quarter
            ->rectangle(0, 0, 100, 50);
        $image = $renderer->render();

        $this->assertColorAt($image, 5, 7, 0x0000FF);
        $this->assertColorAt($image, 5, 2, 0xFFFFFF);
        $this->assertColorAt($image, 15, 7, 0xFFFFFF);
    }

    public function testSaveRestoreDC(): void
    {
        $renderer = new GD(20, 20);
        $renderer->selectObject(GD::getStockObject(8))
            ->selectObject($this->getBrush([255, 0, 0]))
            ->saveDC()
            ->selectObject($this->getBrush([0, 255, 0]))
            ->restoreDC(-1)
            ->rectangle(0, 0, 20, 20);

        $this->assertColorAt($renderer->render(), 10, 10, 0xFF0000);
    }

    public function testClipping(): void
    {
        $renderer = new GD(20, 20);
        $renderer->selectObject(GD::getStockObject(8))
            ->selectObject($this->getBrush([255, 0, 0]))
            ->intersectClipRect(0, 0, 10, 20)
            ->excludeClipRect(0, 0, 10, 5)
            ->rectangle(0, 0, 20, 20);
        $image = $renderer->render();

        $this->assertColorAt($image, 5, 10, 0xFF0000);
        $this->assertColorAt($image, 5, 2, 0xFFFFFF);
        $this->assertColorAt($image, 15, 10, 0xFFFFFF);
    }

    public function testClipRegionXOR(): void
    {
        $renderer = new GD(20, 20);
        $renderer->selectObject(GD::getStockObject(8))
            ->selectObject($this->getBrush([255, 0, 0]))
            ->selectClipRegion([[0, 0, 10, 20]], Rasterizer::RGN_COPY, false)
            ->selectClipRegion([[5, 0, 15, 20]], Rasterizer::RGN_XOR, false)
            ->rectangle(0, 0, 20, 20);
        $image = $renderer->render();

        $this->assertColorAt($image, 2, 10, 0xFF0000);
        $this->assertColorAt($image, 7, 10, 0xFFFFFF);
        $this->assertColorAt($image, 12, 10, 0xFF0000);
        $this->assertColorAt($image, 17, 10, 0xFFFFFF);
    }

    public function testPath(): void
    {
        $renderer = new GD(20, 20);
        $renderer->selectObject(GD::getStockObject(8))
            ->selectObject($this->getBrush([0, 255, 0]))
            ->beginPath()
            ->moveTo(0, 0)
            ->polylineTo([[20, 0], [20, 10]])
            ->closeFigure()
            ->endPath()
            ->fillPath();
        $image = $renderer->render();

        // Triangle above the diagonal
        $this->assertColorAt($image, 18, 2, 0x00FF00);
        $this->assertColorAt($image, 2, 15, 0xFFFFFF);
    }

    public function testStroke(): void
    {
        $renderer = new GD(20, 20);
        $renderer->selectObject(['type' => 'pen', 'style' => 0, 'width' => 4, 'geometric' => true, 'color' => [0, 0, 0]])
            ->moveTo(0, 10)
            ->lineTo(20, 10);
        $image = $renderer->render();

        $this->assertColorAt($image, 10, 9, 0x000000);
        $this->assertColorAt($image, 10, 3, 0xFFFFFF);
    }

    public function testBitmap(): void
    {
        $renderer = new GD(20, 20);
        $renderer->drawBitmap(['width' => 2, 'height' => 1, 'pixels' => [[0xFF0000, 0x0000FF]]], 0, 0, 20, 20, 0, 0, 2, 1);
        $image = $renderer->render();

        $this->assertColorAt($image, 5, 10, 0xFF0000);
        $this->assertColorAt($image, 15, 10, 0x0000FF);
    }

    public function testGradientFill(): void
    {
        $renderer = new GD(20, 10);
        $renderer->gradientFill([[0, 0, [0, 0, 0]], [20, 10, [255, 255, 255]]], [[0, 1]], 0);
        $image = $renderer->render();

        $this->assertLessThan(0x20, imagecolorat($image, 0, 5) & 0xFF);
        $this->assertGreaterThan(0xE0, imagecolorat($image, 19, 5) & 0xFF);
    }

    public function testPatBlt(): void
    {
        $renderer = new GD(20, 20);
        $renderer->patBlt(0, 0, 10, 20, GD::ROP_BLACKNESS)
            ->patBlt(10, 0, 20, 20, GD::ROP_NOP);
        $image = $renderer->render();

        $this->assertColorAt($image, 5, 10, 0x000000);
        $this->assertColorAt($image, 15, 10, 0xFFFFFF);
    }

    public function testFloodFill(): void
    {
        $renderer = new GD(20, 20);
        $renderer->selectObject(['type' => 'pen', 'style' => 0, 'width' => 2, 'geometric' => true, 'color' => [0, 0, 0]])
            ->selectObject(GD::getStockObject(5))
            ->rectangle(2, 2, 18, 18)
            ->selectObject($this->getBrush([255, 0, 0]))
            ->floodFill(10, 10, [0, 0, 0], false);
        $image = $renderer->render();

        $this->assertColorAt($image, 10, 10, 0xFF0000);
        $this->assertColorAt($image, 0, 0, 0xFFFFFF);
    }

    public function testStockObjects(): void
    {
        $this->assertEquals('brush', GD::getStockObject(0)['type']);
        $this->assertEquals('pen', GD::getStockObject(7)['type']);
        $this->assertEquals('font', GD::getStockObject(13)['type']);
        $this->assertNull(GD::getStockObject(9));
    }
}
