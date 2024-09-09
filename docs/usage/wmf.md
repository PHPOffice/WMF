# WMF

You can load `.wmf` files.

## Backends

You can one of two current backends : `gd` or `imagick`.
If you don't know which one used, you can use the magic one.

```php
<?php

use PhpOffice\WMF\Reader\WMF\GD;
use PhpOffice\WMF\Reader\WMF\Imagick;
use PhpOffice\WMF\Reader\WMF\Magic;

// Choose which backend you want
$reader = new GD();
$reader = new Imagick();
$reader = new Magic();

$reader->load('sample.wmf');
```

For next sample, I will use the magic one.

## Methods

### `getResource`

The method returns the resource used in internal by the library.

The `GD` backend returns a `GDImage` object or resource, depending the PHP version.
The `Imagick` backend returns a `Imagick` object.

```php
<?php

use PhpOffice\WMF\Reader\WMF\Magic;

$reader = new Magic();

$wmf = $reader->load('sample.wmf');

var_dump($wmf->getResource());
```

### `getMediaType`

The method returns the media type for a WMF file

```php
<?php

use PhpOffice\WMF\Reader\WMF\Magic;

$reader = new Magic();

$mediaType = $reader->getMediaType();

echo 'The media type for a WMF file is ' . $$mediaType;
```

### `isWMF`

The method allows to know if the file is supported by the library.

```php
<?php

use PhpOffice\WMF\Reader\WMF\Magic;

$reader = new Magic();

$isWMF = $reader->isWMF('sample.wmf');

echo 'The file sample.wmf ' . ($isWMF ? 'is a WMF file' : 'is not a WMF file');
```

### `load`

The method load a WMF file in the object

```php
<?php

use PhpOffice\WMF\Reader\WMF\Magic;

$reader = new Magic();

$wmf = $reader->load('sample.wmf');
```

### `save`

The method transforms the loaded WMF file in an another image. 

```php
<?php

use PhpOffice\WMF\Reader\WMF\Magic;

$reader = new Magic();

$wmf = $reader->load('sample.wmf');
$wmf->save('sample.png', 'png');
```
