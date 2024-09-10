<?php

namespace PhpOffice\WMF\Reader;

use GDImage;
use Imagick;

interface ReaderInterface
{
    public function load(string $filename): bool;

    public function loadFromString(string $content): bool;

    public function save(string $filename, string $format): bool;

    public function getMediaType(): string;

    /**
     * @phpstan-ignore-next-line
     *
     * @return GDImage|Imagick
     */
    public function getResource();
}
