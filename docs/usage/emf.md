# EMF

You can load `.emf` files.

## Backends

You can one of two current backends : `gd` or `imagick`.
If you don't know which one used, you can use the magic one.

By default, the order of the backends is Imagick, followed by GD.
Each backend is tested on different criteria: extension loaded, format support.

!!! note "Imagick & EMF"

    ImageMagick supports EMF files only on Windows.
    On other systems, the magic backend uses GD.

```php
<?php

use PhpOffice\WMF\Reader\EMF\GD;
use PhpOffice\WMF\Reader\EMF\Imagick;
use PhpOffice\WMF\Reader\EMF\Magic;

// Choose which backend you want
$reader = new GD();
$reader = new Imagick();
$reader = new Magic();

$reader->load('sample.emf');
```

For next samples, I will use the magic one.

### `getBackends`

This specific method for `Magic::class` returns backends sorted by priority.

```php
<?php

use PhpOffice\WMF\Reader\EMF\Magic;

$reader = new Magic();

var_dump($reader->getBackends());
```

### `setBackends`

This specific method for `Magic::class` defines backends sorted by priority.

```php
<?php

use PhpOffice\WMF\Reader\EMF\GD;
use PhpOffice\WMF\Reader\EMF\Imagick;
use PhpOffice\WMF\Reader\EMF\Magic;

$reader = new Magic();
$reader->setBackends([
  GD::class,
  Imagick::class,
]);

var_dump($reader->getBackends());
```

## GD backend

The `GD` backend is a renderer written in pure PHP, shared with the WMF reader (`PhpOffice\WMF\Renderer\GD`).
The image is generated at 96 DPI, based on the frame of the EMF file (big images are scaled down).

It supports :

- paths & shapes (polygons, polylines, Bézier curves, rectangles, rounded rectangles, ellipses, arcs, chords, pies)
- pens (width, end caps, joins) & brushes (solid, pattern brushes are approximated by their average color)
- world & page transforms (map modes, window, viewport)
- clipping (paths, rectangles, regions)
- bitmaps (`EMR_STRETCHDIBITS`, `EMR_BITBLT`, `EMR_STRETCHBLT`, including JPEG & PNG bitmaps) & gradients (`EMR_GRADIENTFILL`)
- texts (`EMR_EXTTEXTOUTW`, `EMR_EXTTEXTOUTA`, `EMR_SMALLTEXTOUT`), with TrueType/OpenType fonts

If a not supported record is found (like `EMR_ALPHABLEND`, `EMR_MASKBLT`, `EMR_PLGBLT`, `EMR_TRANSPARENTBLT`, `EMR_POLYDRAW` or `EMR_EXTFLOODFILL`),
or a bitmap which is not supported (like compressed bitmaps, `BI_RLE4` & `BI_RLE8`),
an exception is thrown (or `load` returns `false`, if exceptions are disabled).
Corrupted files throw an exception too.

### Known limitations

- The background of the image is white : the image is never transparent.
- The content outside of the frame of the file is clipped.
- EMF+ records are ignored : EMF+ "dual" files are drawn with their EMF records, EMF+ only files are empty.
- Pens :
    - dashed pens are drawn as solid lines,
    - pens have a minimal width of one pixel.
- Brushes :
    - hatched brushes are drawn as solid brushes,
    - pattern brushes are drawn with the average color of their pattern.
- Bitmaps :
    - compressed bitmaps (`BI_RLE4` & `BI_RLE8`) are not supported : an exception is thrown,
    - bitmaps using the palette of the file (`DIB_PAL_COLORS`) are drawn with a gray scale (the palette is not supported),
    - raster operations are not supported : bitmaps are copied (`SRCCOPY`), and rectangles without bitmap are only drawn
      for `PATCOPY`, `BLACKNESS` & `WHITENESS`,
    - corrupted JPEG & PNG bitmaps are not drawn.
- Texts :
    - texts are not drawn if no font is found (see [Fonts](#fonts)),
    - the metrics of fonts are approximated (ascent, descent), the width & the orientation of fonts are ignored,
    - the vertical advances of characters (`ETO_PDY`) are ignored,
    - the charset is ignored : symbol fonts (like `Symbol` or `Wingdings`) are drawn with another font.
- `EMR_SETROP2`, `EMR_SETSTRETCHBLTMODE` & palettes are ignored.

### Fonts

Texts are drawn with the TrueType/OpenType fonts (`.ttf` & `.otf`) found in the system directories :

- Windows : `C:\Windows\Fonts`
- MacOS : `/Library/Fonts`, `/System/Library/Fonts` & `~/Library/Fonts`
- Linux : `/usr/share/fonts`, `/usr/local/share/fonts`, `~/.fonts` & `~/.local/share/fonts`

If a font is not available, a similar font is used (for example, `Liberation Sans` or `DejaVu Sans` for `Arial`).
If no font is found, texts are not drawn.

You can define the directories where fonts are searched (recursively) with `setFontDirectories`.

```php
<?php

use PhpOffice\WMF\Reader\EMF\GD;

$reader = new GD();
$reader->setFontDirectories([
    __DIR__ . '/fonts',
]);
$reader->load('sample.emf');
```

## Methods

### `getResource`

The method returns the resource used in internal by the library.

The `GD` backend returns a `GDImage` object or resource, depending the PHP version.
The `Imagick` backend returns a `Imagick` object.

```php
<?php

use PhpOffice\WMF\Reader\EMF\Magic;

$reader = new Magic();
$reader->load('sample.emf');

var_dump($reader->getResource());
```

### `getMediaType`

The method returns the media type for a EMF file.

```php
<?php

use PhpOffice\WMF\Reader\EMF\Magic;

$reader = new Magic();

$mediaType = $reader->getMediaType();

echo 'The media type for a EMF file is ' . $mediaType;
```

### `isEMF`

The method returns if the file is supported by the library.

```php
<?php

use PhpOffice\WMF\Reader\EMF\Magic;

$reader = new Magic();
$reader->load('sample.emf');

$isEMF = $reader->isEMF();

echo 'The file sample.emf ' . ($isEMF ? 'is a EMF file' : 'is not a EMF file');
```

### `isSupported`

The method returns if the reader can be used : the extension is loaded (and supports the format, for `Imagick`).
For `Magic::class`, the method returns if one of its backends can be used.

```php
<?php

use PhpOffice\WMF\Reader\EMF\Imagick;

$reader = new Imagick();

echo 'The Imagick backend ' . ($reader->isSupported() ? 'can' : 'can not') . ' be used';
```

### `load`

The method loads a EMF file in the object.
The method returns `true` if the file has been correctly loaded, or `false` if it has not.

```php
<?php

use PhpOffice\WMF\Reader\EMF\Magic;

$reader = new Magic();
$reader->load('sample.emf');
```

### `loadFromString`

The method loads a EMF file in the object from a string.
The method returns `true` if the file has been correctly loaded, or `false` if it has not.

```php
<?php

use PhpOffice\WMF\Reader\EMF\Magic;

$reader = new Magic();
$reader->loadFromString(file_get_contents('sample.emf'));
```

### `save`

The method transforms the loaded EMF file in an another image.

```php
<?php

use PhpOffice\WMF\Reader\EMF\Magic;

$reader = new Magic();
$reader->load('sample.emf');
$reader->save('sample.png', 'png');
```
