# WMF

You can load `.wmf` files.

## Backends

You can one of two current backends : `gd` or `imagick`.
If you don't know which one used, you can use the magic one.

By default, the order of the backends is Imagick, followed by GD.
Each backend is tested on different criteria: extension loaded, format support.

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

For next samples, I will use the magic one.

### `getBackends`

This specific method for `Magic::class` returns backends sorted by priority.

```php
<?php

use PhpOffice\WMF\Reader\WMF\Magic;

$reader = new Magic();

var_dump($reader->getBackends());
```

### `setBackends`

This specific method for `Magic::class` defines backends sorted by priority.

```php
<?php

use PhpOffice\WMF\Reader\WMF\GD;
use PhpOffice\WMF\Reader\WMF\Imagick;
use PhpOffice\WMF\Reader\WMF\Magic;

$reader = new Magic();
$reader->setBackends([
  GD::class,
  Imagick::class,
]);

var_dump($reader->getBackends());
```

## Methods

### `getResource`

The method returns the resource used in internal by the library.

The `GD` backend returns a `GDImage` object or resource, depending the PHP version.
The `Imagick` backend returns a `Imagick` object.

```php
<?php

use PhpOffice\WMF\Reader\WMF\Magic;

$reader = new Magic();
$reader->load('sample.wmf');

var_dump($reader->getResource());
```

### `getMediaType`

The method returns the media type for a WMF file.

```php
<?php

use PhpOffice\WMF\Reader\WMF\Magic;

$reader = new Magic();

$mediaType = $reader->getMediaType();

echo 'The media type for a WMF file is ' . $$mediaType;
```

### `isWMF`

The method returns if the file is supported by the library.

```php
<?php

use PhpOffice\WMF\Reader\WMF\Magic;

$reader = new Magic();
$reader->load('sample.wmf');

$isWMF = $reader->isWMF();

echo 'The file sample.wmf ' . ($isWMF ? 'is a WMF file' : 'is not a WMF file');
```

### `load`

The method loads a WMF file in the object.
The method returns `true` if the file has been correctly loaded, or `false` if it has not. 

```php
<?php

use PhpOffice\WMF\Reader\WMF\Magic;

$reader = new Magic();
$reader->load('sample.wmf');
```

### `loadFromString`

The method loads a WMF file in the object from a string.
The method returns `true` if the file has been correctly loaded, or `false` if it has not. 

```php
<?php

use PhpOffice\WMF\Reader\WMF\Magic;

$reader = new Magic();
$reader->loadFromString(file_get_contents('sample.wmf'));
```

### `save`

The method transforms the loaded WMF file in an another image. 

```php
<?php

use PhpOffice\WMF\Reader\WMF\Magic;

$reader = new Magic();
$reader->load('sample.wmf');
$reader->save('sample.png', 'png');
```
