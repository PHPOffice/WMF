<?php

declare(strict_types=1);

namespace PhpOffice\WMF\Renderer;

/**
 * Conversion of the strings of metafiles to UTF-8
 */
class Encoding
{
    /**
     * Characters of Windows-1252 from 0x80 to 0x9F
     */
    protected const WINDOWS_1252 = [
        0x20AC, 0x81, 0x201A, 0x0192, 0x201E, 0x2026, 0x2020, 0x2021, 0x02C6, 0x2030, 0x0160, 0x2039, 0x0152, 0x8D, 0x017D, 0x8F,
        0x90, 0x2018, 0x2019, 0x201C, 0x201D, 0x2022, 0x2013, 0x2014, 0x02DC, 0x2122, 0x0161, 0x203A, 0x0153, 0x9D, 0x017E, 0x0178,
    ];

    /**
     * Decodes a UTF-16LE string (stopping at the first NUL character) to UTF-8
     */
    public static function decodeUTF16(string $data): string
    {
        $data = (string) substr($data, 0, strlen($data) - strlen($data) % 2);
        if ($data === '') {
            return '';
        }
        $units = array_values(unpack('v*', $data));
        $count = count($units);

        $result = '';
        for ($i = 0; $i < $count; ++$i) {
            $codePoint = $units[$i];
            if ($codePoint == 0) {
                break;
            }
            if ($codePoint >= 0xD800 && $codePoint < 0xDC00 && $i + 1 < $count && $units[$i + 1] >= 0xDC00 && $units[$i + 1] < 0xE000) {
                $codePoint = 0x10000 + (($codePoint - 0xD800) << 10) + ($units[$i + 1] - 0xDC00);
                ++$i;
            }
            $result .= self::encodeUTF8($codePoint);
        }

        return $result;
    }

    /**
     * Decodes a Windows-1252 string (stopping at the first NUL character) to UTF-8
     */
    public static function decodeANSI(string $data): string
    {
        $result = '';
        $length = strlen($data);
        for ($i = 0; $i < $length; ++$i) {
            $byte = ord($data[$i]);
            if ($byte == 0) {
                break;
            }
            $result .= self::encodeUTF8($byte >= 0x80 && $byte < 0xA0 ? self::WINDOWS_1252[$byte - 0x80] : $byte);
        }

        return $result;
    }

    public static function encodeUTF8(int $codePoint): string
    {
        if ($codePoint >= 0xD800 && $codePoint < 0xE000) {
            // Lone surrogate
            $codePoint = 0xFFFD;
        }
        if ($codePoint < 0x80) {
            return chr($codePoint);
        }
        if ($codePoint < 0x800) {
            return chr(0xC0 | ($codePoint >> 6)) . chr(0x80 | ($codePoint & 0x3F));
        }
        if ($codePoint < 0x10000) {
            return chr(0xE0 | ($codePoint >> 12)) . chr(0x80 | (($codePoint >> 6) & 0x3F)) . chr(0x80 | ($codePoint & 0x3F));
        }

        return chr(0xF0 | ($codePoint >> 18)) . chr(0x80 | (($codePoint >> 12) & 0x3F)) . chr(0x80 | (($codePoint >> 6) & 0x3F)) . chr(0x80 | ($codePoint & 0x3F));
    }
}
