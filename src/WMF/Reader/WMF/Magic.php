<?php

declare(strict_types=1);

namespace PhpOffice\WMF\Reader\WMF;

use GDImage;
use Imagick as ImagickBase;
use PhpOffice\WMF\Reader\WMF\Imagick as ImagickReader;

class Magic extends ReaderAbstract
{
    /**
     * @var array<string>
     */
    protected $backends = [
        ImagickReader::class,
        GD::class,
    ];

    /**
     * @var ?ReaderInterface
     */
    protected $reader;

    protected function getBackend(): ?ReaderInterface
    {
        if ($this->reader) {
            return $this->reader;
        }

        $reader = null;
        foreach ($this->backends as $backend) {
            if ($backend === GD::class) {
                if (extension_loaded('gd')) {
                    $reader = new GD();

                    break;
                }
            }
            if ($backend === ImagickReader::class) {
                if (extension_loaded('imagick') && in_array('WMF', ImagickBase::queryformats())) {
                    $reader = new ImagickReader();
                }

                break;
            }
        }

        $this->reader = $reader;

        return $this->reader;
    }

    public function load(string $filename): bool
    {
        return $this->getBackend()->load($filename);
    }

    public function loadFromString(string $content): bool
    {
        return $this->getBackend()->loadFromString($content);
    }

    public function save(string $filename, string $format): bool
    {
        return $this->getBackend()->save($filename, $format);
    }

    public function getMediaType(): string
    {
        return $this->getBackend()->getMediaType();
    }

    public function isWMF(): bool
    {
        return $this->getBackend()->isWMF();
    }

    /**
     * @phpstan-ignore-next-line
     *
     * @return GDImage|ImagickBase
     */
    public function getResource()
    {
        return $this->getBackend()->getResource();
    }

    /**
     * @return array<string>
     */
    public function getBackends(): array
    {
        return $this->backends;
    }

    /**
     * @param array<string> $backends
     */
    public function setBackends(array $backends): self
    {
        $this->backends = [];
        foreach ($backends as $backend) {
            if (is_a($backend, ReaderInterface::class, true)) {
                $this->backends[] = $backend;
            }
        }

        return $this;
    }
}
