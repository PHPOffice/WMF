<?php

declare(strict_types=1);

namespace Tests\PhpOffice\WMF\Reader\WMF;

use PhpOffice\WMF\Reader\WMF\GD;
use PhpOffice\WMF\Reader\WMF\Imagick;
use PhpOffice\WMF\Reader\WMF\Magic;
use Tests\PhpOffice\WMF\Reader\AbstractTestReader;

class MagicTest extends AbstractTestReader
{
    public function testGetBackends(): void
    {
        $reader = new Magic();
        $this->assertEquals([
            Imagick::class,
            GD::class,
        ], $reader->getBackends());
    }

    public function testSetBackends(): void
    {
        $reader = new Magic();
        $this->assertInstanceOf(Magic::class, $reader->setBackends([
            GD::class,
            Imagick::class,
        ]));
        $this->assertEquals([
            GD::class,
            Imagick::class,
        ], $reader->getBackends());
    }
}
