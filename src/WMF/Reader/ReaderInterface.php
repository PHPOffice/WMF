<?php

namespace PhpOffice\WMF\Reader;

interface ReaderInterface
{
    public function isWMF(string $filename): bool;
    
    public function load(string $filename): bool;
    
    public function save(string $filename, string $format): bool;

    public function getResource();
}