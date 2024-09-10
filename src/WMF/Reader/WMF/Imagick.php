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
        return $this->loadContent($filename, false);
    }

    public function loadFromString(string $content): bool
    {
        return $this->loadContent($content, true);
    }

    private function loadContent(string $content, bool $isBlob): bool
    {
        try {
            $this->im = new ImagickBase();

            return $isBlob ? $this->im->readImageBlob($content) : $this->im->readImage($content);
        } catch (ImagickException $e) {
            $this->im->clear();

            if ($this->hasExceptionsEnabled()) {
                throw new WMFException('Cannot load WMG File from Imagick');
            } else {
                return false;
            }
        }
    }

    public function isWMF(): bool
    {
        return $this->im->getImageFormat() === 'WMF';
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
