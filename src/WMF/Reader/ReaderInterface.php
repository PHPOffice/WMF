<?php

namespace PhpOffice\WMF\Reader;

use GDImage;
use Imagick;

interface ReaderInterface
{
    public function isWMF(string $filename): bool;

    public function load(string $filename): bool;

    public function save(string $filename, string $format): bool;

    /**
     * @phpstan-ignore-next-line
     *
     * @return GDImage|Imagick
     */
    public function getResource();
}
