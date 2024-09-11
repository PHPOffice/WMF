<?php

declare(strict_types=1);

namespace PhpOffice\WMF\Reader\WMF;

abstract class ReaderAbstract implements ReaderInterface
{
    /**
     * @var bool
     */
    protected $hasExceptionsEnabled = true;
    /**
     * @var string
     */
    protected $content;

    /**
     * Enable/Disable throwing exceptions
     *
     * By default, it's enabled
     */
    public function enableExceptions(bool $enable): self
    {
        $this->hasExceptionsEnabled = $enable;

        return $this;
    }

    /**
     * Returns if exceptions are thrown
     */
    public function hasExceptionsEnabled(): bool
    {
        return $this->hasExceptionsEnabled;
    }

    public function getMediaType(): string
    {
        return 'image/wmf';
    }
}
