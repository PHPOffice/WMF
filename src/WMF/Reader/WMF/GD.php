<?php

declare(strict_types=1);

namespace PhpOffice\WMF\Reader\WMF;

use GdImage;
use PhpOffice\WMF\Exception\WMFException;

class GD extends ReaderAbstract
{
    /**
     * @phpstan-ignore-next-line
     *
     * @var GdImage|resource|false
     */
    protected $gd;
    /**
     * @var string
     */
    protected $content;
    /**
     * @var int
     */
    protected $pos;
    /**
     * @var int
     */
    protected $windowOriginX;
    /**
     * @var int
     */
    protected $windowOriginY;
    /**
     * @var int
     */
    protected $windowWidth;
    /**
     * @var int
     */
    protected $windowHeight;
    /**
     * @var int
     */
    protected $unitPerInch;
    /**
     * @var array<array<string, string>>
     */
    protected $gdiObjects = [];

    public const META_EOF = 0x0000;
    public const META_SETPOLYFILLMODE = 0x0106;
    public const META_SELECTOBJECT = 0x012D;
    public const META_DELETEOBJECT = 0x01F0;
    public const META_SETWINDOWORG = 0x020B;
    public const META_SETWINDOWEXT = 0x020C;
    public const META_CREATEPENINDIRECT = 0x02FA;
    public const META_CREATEBRUSHINDIRECT = 0x02FC;
    public const META_POLYGON = 0x0324;

    public function __destruct()
    {
        if ($this->gd) {
            imagedestroy($this->gd);
        }
    }

    public function isWMF(string $filename): bool
    {
        list(, $key) = unpack('L', substr(file_get_contents($filename), 0, 4));

        return $key == (int) 0x9AC6CDD7;
    }

    /**
     * @see https://github.com/affinitybridge/mpdf/blob/master/src/Image/Wmf.php
     */
    public function load(string $filename): bool
    {
        $this->content = file_get_contents($filename);

        $this->pos = 0;
        $this->gdiObjects = [];
        $k = 72 / 25.4;

        $this->readHeader();

        $contentLen = strlen($this->content);
        $recordEnd = false;

        $dataFillColor = $dataDrawColor = null;
        $nullPen = $nullBrush = false;
        $dashArray = [];
        $modePolyFill = 0;

        while ($this->pos < $contentLen && !$recordEnd) {
            list(, $size) = unpack('L', substr($this->content, $this->pos, 4));
            $this->pos += 4;

            list(, $recordType) = unpack('S', substr($this->content, $this->pos, 2));
            $this->pos += 2;

            $params = [];
            if ($size > 3) {
                $params = substr($this->content, $this->pos, 2 * ($size - 3));
                $this->pos += 2 * ($size - 3);
            }

            switch ($recordType) {
                case self::META_EOF:
                    $recordEnd = true;
                    break;
                case self::META_SETPOLYFILLMODE:
                    list(, $modePolyFill) = unpack('s', $params);
                    break;
                case self::META_SELECTOBJECT:
                    list(, $idx) = unpack('S', $params);
                    $object = $this->gdiObjects[$idx];
                    switch ($object['type']) {
                        case 'B':
                            $nullBrush = false;
                            if ($object['style'] == 1) {
                                $nullBrush = true;
                            } else {
                                $dataFillColor = imagecolorallocate($this->gd, (int) $object['r'], (int) $object['g'], (int) $object['b']);
                            }
                            break;
                        case 'P':
                            $nullPen = false;
                            $dashArray = [];
                            // dash parameters are custom
                            switch ($object['style']) {
                                case 0: // PS_SOLID
                                    break;
                                case 1: // PS_DASH
                                    $dashArray = [3, 1];
                                    break;
                                case 2: // PS_DOT
                                    $dashArray = [0.5, 0.5];
                                    break;
                                case 3: // PS_DASHDOT
                                    $dashArray = [2, 1, 0.5, 1];
                                    break;
                                case 4: // PS_DASHDOTDOT
                                    $dashArray = [2, 1, 0.5, 1, 0.5, 1];
                                    break;
                                case 5: // PS_NULL
                                    $nullPen = true;
                                    break;
                            }
                            if (!$nullPen) {
                                $dataDrawColor = imagecolorallocate($this->gd, (int) $object['r'], (int) $object['g'], (int) $object['b']);
                                // @todo
                                // $wmfdata .= sprintf("%.3F w\n", $object['width'] * $k);
                            }
                            if (!empty($dashArray)) {
                                $s = '[';
                                for ($i = 0; $i < count($dashArray); ++$i) {
                                    $s .= $dashArray[$i] * $k;
                                    if ($i != count($dashArray) - 1) {
                                        $s .= ' ';
                                    }
                                }
                                $s .= '] 0 d';
                            }
                            break;
                    }
                    break;
                case self::META_DELETEOBJECT:
                    list(, $idx) = unpack('S', $params);
                    unset($this->gdiObjects[$idx]);
                    break;
                case self::META_SETWINDOWORG:
                    // Do not allow window origin to be changed after drawing has begun
                    if (!$this->windowOriginX) {
                        $windowOrigin = array_reverse(unpack('s2', $params));
                        $this->windowOriginX = (int) $windowOrigin[0];
                        $this->windowOriginY = (int) $windowOrigin[1];
                    }
                    break;
                case self::META_SETWINDOWEXT:
                    // Do not allow window extent to be changed after drawing has begun
                    if (!$this->windowWidth) {
                        $windowExtent = array_reverse(unpack('s2', $params));
                        $this->windowWidth = (int) $windowExtent[0];
                        $this->windowHeight = (int) ($windowExtent[1] > 0 ? $windowExtent[1] : $windowExtent[1] * -1);

                        $this->gd = imagecreatetruecolor($this->windowWidth, $this->windowHeight);
                        imagefilledrectangle($this->gd, 0, 0, $this->windowWidth, $this->windowHeight, imagecolorallocate($this->gd, 255, 255, 255));
                    }
                    break;
                case self::META_CREATEPENINDIRECT:
                    $pen = unpack('Sstyle/swidth/sdummy/Cr/Cg/Cb/Ca', $params);
                    // convert width from twips to user unit
                    $pen['width'] /= (20 * $k);
                    $pen['type'] = 'P';
                    $this->addGDIObject($pen);
                    break;
                case self::META_CREATEBRUSHINDIRECT:
                    $brush = unpack('sstyle/Cr/Cg/Cb/Ca/Shatch', $params);
                    $brush['type'] = 'B';
                    $this->addGDIObject($brush);
                    break;
                case self::META_POLYGON:
                    $coordinates = unpack('s' . ($size - 3), $params);
                    $numpoints = $coordinates[1];

                    $points = [];
                    for ($i = $numpoints; $i > 0; --$i) {
                        list($px, $py) = $this->resetCoordinates((int) $coordinates[2 * $i], (int) $coordinates[2 * $i + 1]);

                        if ($i < $numpoints) {
                            $points[] = $px;
                            $points[] = $py;
                        } else {
                            $points[] = $px;
                            $points[] = $py;
                        }
                    }
                    if ($recordType == 0x0325) {
                        \imagepolygon($this->gd, $points, $numpoints, $dataDrawColor);
                    }
                    if ($recordType == self::META_POLYGON) {
                        if ($nullPen) {
                            if ($nullBrush) {
                                // No op
                                $op = 'n';
                            } else {
                                // Fill
                                if (\PHP_VERSION_ID < 80000) {
                                    imagefilledpolygon($this->gd, $points, $numpoints, $dataFillColor);
                                } else {
                                    /* @phpstan-ignore-next-line */
                                    imagefilledpolygon($this->gd, $points, $dataFillColor);
                                }
                            }
                        } else {
                            if ($nullBrush) {
                                // Stroke
                                \imagepolygon($this->gd, $points, $numpoints, $dataDrawColor);
                            } else {
                                // Stroke and Fill
                                \imagepolygon($this->gd, $points, $numpoints, $dataDrawColor);
                                if (\PHP_VERSION_ID < 80000) {
                                    imagefilledpolygon($this->gd, $points, $numpoints, $dataFillColor);
                                } else {
                                    /* @phpstan-ignore-next-line */
                                    imagefilledpolygon($this->gd, $points, $dataFillColor);
                                }
                            }
                        }
                        if ($modePolyFill == 1 && (($nullPen && !$nullBrush) || (!$nullPen && $nullBrush))) {
                            // Even-odd fill
                        }
                    }
                    break;
                default:
                    if ($this->hasExceptionsEnabled()) {
                        throw new WMFException('Reader : Function not implemented : 0x' . str_pad(dechex($recordType), 4, '0', STR_PAD_LEFT));
                    } else {
                        return false;
                    }
            }
        }

        return true;
    }

    protected function readHeader(): void
    {
        list(, $key) = unpack('L', substr($this->content, 0, 4));
        list(, $handle) = unpack('S', substr($this->content, 4, 2));
        list(, $left) = unpack('S', substr($this->content, 6, 2));
        list(, $top) = unpack('S', substr($this->content, 8, 2));
        list(, $right) = unpack('S', substr($this->content, 10, 2));
        list(, $bottom) = unpack('S', substr($this->content, 12, 2));
        list(, $this->unitPerInch) = unpack('S', substr($this->content, 14, 2));
        list(, $reserved) = unpack('L', substr($this->content, 16, 4));
        list(, $checksum) = unpack('S', substr($this->content, 18, 2));

        $this->pos = 18;
        if ($key == (int) 0x9AC6CDD7) {
            $this->pos += 22;
        }
    }

    /**
     * @return array<int, int>
     */
    protected function resetCoordinates(int $x, int $y): array
    {
        $x -= $this->windowOriginX;

        $midHeight = $this->windowHeight / 2;
        $y += ($this->windowHeight - $this->windowOriginY);
        if ($y > $midHeight) {
            $y -= ($y - $midHeight) * 2;
        } else {
            $y += ($midHeight - $y) * 2;
        }

        return [$x, $y];
    }

    /**
     * @param array<string, string> $gdiObject
     */
    protected function addGDIObject(array $gdiObject, ?int $idx = null): void
    {
        if (!$idx) {
            // Find next available slot
            $idx = 0;

            if (!empty($this->gdiObjects)) {
                $empty = false;
                $i = 0;
                while (!$empty) {
                    $empty = !isset($this->gdiObjects[$i]);
                    ++$i;
                }
                $idx = $i - 1;
            }
        }

        $this->gdiObjects[$idx] = $gdiObject;
    }

    /**
     * @phpstan-ignore-next-line
     *
     * @return GdImage|resource
     */
    public function getResource()
    {
        // INCH_TO_POINT
        $inchToPoint = 72;

        $this->gd = imagescale(
            $this->gd,
            (int) ceil(($this->windowWidth / $this->unitPerInch) * $inchToPoint),
            (int) ceil(($this->windowHeight / $this->unitPerInch) * $inchToPoint)
        );
        imagesavealpha($this->gd, true);

        return $this->gd;
    }

    public function save(string $filename, string $format): bool
    {
        switch (strtolower($format)) {
            case 'gif':
                return imagegif($this->getResource(), $filename);
            case 'jpg':
            case 'jpeg':
                return imagejpeg($this->getResource(), $filename);
            case 'png':
                return imagepng($this->getResource(), $filename);
            case 'webp':
                return imagewebp($this->getResource(), $filename);
            case 'wbmp':
                return imagewbmp($this->getResource(), $filename);
            default:
                if ($this->hasExceptionsEnabled()) {
                    throw new WMFException(sprintf('Format %s not supported', $format));
                } else {
                    return false;
                }
        }
    }
}
