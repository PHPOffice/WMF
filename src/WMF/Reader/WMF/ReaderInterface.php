<?php

namespace PhpOffice\WMF\Reader\WMF;

use PhpOffice\WMF\Reader\ReaderInterface as ReaderInterfaceBase;

interface ReaderInterface extends ReaderInterfaceBase
{
    public function isWMF(string $filename): bool;
}
