<?php

declare(strict_types=1);

namespace PhpOffice\WMF\Reader;

use Imagick as ImagickBase;
use PhpOffice\WMF\Exception\WMFException;

class Imagick implements ReaderInterface
{
    /**
     * @var ImagickBase
     */
    protected $im;

    public function load(string $filename): bool
    {
        $this->im = new ImagickBase();

        return $this->im->readImage($filename);
    }

    public function isWMF(string $filename): bool
    {
        $im = new ImagickBase();
        $im->readImage($filename);

        return $im->getImageFormat() === 'WMF';
    }

    public function getResource(): ImagickBase
    {
        return $this->im;
    }

    public function save(string $filename, string $format): bool
    {
        switch (strtolower($format)) {
            case 'gif':
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'webp':
            case 'wbmp':
                $this->getResource()->setImageFormat(strtolower($format));

                return $this->getResource()->writeImage($filename);
            default:
                throw new WMFException(sprintf('Format %s not supported', $format));
        }
    }
}
