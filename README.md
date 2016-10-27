# phulp-inject

The inject addon for [PHULP](https://github.com/reisraff/phulp).

It's like [gulp-inject](https://github.com/klei/gulp-inject) with some modifications.

## Install

```bash
$ composer require reisraff/phulp-inject
```

## Usage

```php
<?php

use Phulp\Inject\Inject;

$phulp->task('inject', function ($phulp) {
    $injectionFiles = $phulp->src(['src/scripts/'], '/js$/');

    $phulp->src(['src/'], '/html$/')
        ->pipe(
            new Inject(
                $injectionFiles->getDistFiles(),
                [
                    'tagname' => 'inject',
                    // 'starttag' => null,
                    // 'endtag' => null,
                    'filterFilename' => function ($filename) {
                        return 'path/' . $filename;
                    },
                ]
            )
        );
});

```
