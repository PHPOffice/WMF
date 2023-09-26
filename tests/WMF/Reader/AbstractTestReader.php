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

    public static function dataProviderFiles(): array
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
}