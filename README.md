# phulp-inject

The inject addon for [PHULP](https://github.com/reisraff/phulp).

It's like [gulp-inject](https://github.com/klei/gulp-inject) with some modifications.

## Install

```bash
$ composer require reisraff/phulp-inject
```

## Usage

**The target file `src/index.html`:**

Each pair of comments are the injection placeholders

```html
<!DOCTYPE html>
<html>
<head>
  <title>App</title>
  <!-- inject:css -->
  <!-- endinject -->
</head>
<body>

  <!-- inject:js -->
  <!-- endinject -->
</body>
</html>
```

**The `phulpfile.php`:**

```php
<?php

use Phulp\Inject\Inject;

$phulp->task('inject', function ($phulp) {
    $injectionFiles = $phulp->src(['src/'], '/(js|css)$/', true);

    $phulp->src(['src/'], '/html$/')
        // injecting
        ->pipe(new Inject($injectionFiles->getDistFiles()))
        // write the html file with the injected files
        ->pipe($phulp->dest('dist/'));
});
```

**`dist/index.html` after running `phulp inject`:**

```html
<!DOCTYPE html>
<html>
<head>
  <title>App</title>
  <!-- inject:css -->
  <link rel="stylesheet" href="css/sytle.css">
<link rel="stylesheet" href="css/style2.css">
<!-- endinject -->
</head>
<body>

  <!-- inject:js -->
  <script src="js/script.js"></script>
<script src="js/script2.js"></script>
<!-- endinject -->
</body>
</html>
```

## Options

Set in the constructor.

***tagname*** : default: inject, it is used to define a global tagname as placeholder.

***starttag*** : default: null, it is used to replace the default starttag

***endtag*** : default: null, it is used to replace the default endtag

***filterFilename*** : default: null, it is used to replace the filename

```php
<?php

use Phulp\Inject\Inject;

$cssMinifier = new Inject([
    'tagname' => 'replace-inject',
    'starttag' => '<-- replace-inject -->',
    'endtag' => '<-- endreplace-inject -->,
    'filterFilename' => function ($filename) {
      return 'path/' . $filename;
    },
]);

```
