<?php

declare(strict_types=1);

namespace PhpOffice\WMF\Reader;

use Imagick as ImagickBase;
use PhpOffice\WMF\Reader\GD;
use PhpOffice\WMF\Reader\Imagick as ImagickReader;

class Magic implements ReaderInterface
{
    /**
     * @var ReaderInterface
     */
    protected $reader;

    public function __construct()
    {
        if (extension_loaded('imagick') && in_array('WMF', ImagickBase::queryformats())) {
            $this->reader = new ImagickReader();
        }
        if (!$this->reader && extension_loaded('gd')) {
            $this->reader = new GD();
        }
    }

    public function load(string $filename): bool
    {
        return $this->reader->load($filename); 
    }

    public function save(string $filename, string $format): bool
    {
        return $this->reader->save($filename, $format); 
    }

    public function isWMF(string $filename): bool
    {
        return $this->reader->isWMF($filename);
    }

    public function getResource()
    {
        return $this->reader->getResource();
    }
}