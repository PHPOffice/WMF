<?php

declare(strict_types=1);

namespace PhpOffice\WMF\Reader\WMF;

use GDImage;
use Imagick as ImagickBase;
use PhpOffice\WMF\Reader\WMF\Imagick as ImagickReader;

class Magic implements ReaderInterface
{
    /**
     * @var ReaderInterface
     */
    protected $reader;

    public function __construct()
    {
        $reader = null;
        if (extension_loaded('imagick') && in_array('WMF', ImagickBase::queryformats())) {
            $reader = new ImagickReader();
        }
        if (!$reader && extension_loaded('gd')) {
            $reader = new GD();
        }
        $this->reader = $reader;
    }

    public function load(string $filename): bool
    {
        return $this->reader->load($filename);
    }

    public function save(string $filename, string $format): bool
    {
        return $this->reader->save($filename, $format);
    }

    public function getMediaType(): string
    {
        return $this->reader->getMediaType();
    }

    public function isWMF(string $filename): bool
    {
        return $this->reader->isWMF($filename);
    }

    /**
     * @phpstan-ignore-next-line
     *
     * @return GDImage|ImagickBase
     */
    public function getResource()
    {
        return $this->reader->getResource();
    }
}
