<?php

declare(strict_types=1);

namespace PhpOffice\WMF\Reader\WMF;

use Imagick as ImagickBase;
use ImagickException;
use PhpOffice\WMF\Exception\WMFException;

class Imagick extends ReaderAbstract
{
    /**
     * @var ImagickBase
     */
    protected $im;

    public function load(string $filename): bool
    {
        try {
            $this->im = new ImagickBase();

            return $this->im->readImage($filename);
        } catch (ImagickException $e) {
            $this->im->clear();

            if ($this->hasExceptionsEnabled()) {
                throw new WMFException('Cannot load WMG File from Imagick');
            } else {
                return false;
            }
        }
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
                if ($this->hasExceptionsEnabled()) {
                    throw new WMFException(sprintf('Format %s not supported', $format));
                } else {
                    return false;
                }
        }
    }
}
