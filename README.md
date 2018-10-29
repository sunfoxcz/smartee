# Smartee

Smartee is inplace replacement of [Smarty](https://github.com/smarty-php/smarty)
template engine with [Latte](https://github.com/nette/latte) template engine.
You don't need to edit anything in template, just switch libraries and enjoy
much faster rendering.

## Proof of concept

For now, this library is just proof of concept. If you are willing to help me
finish it, you are very welcome, just send PR.

## Usage

```php
<?php

$latte = new Sunfox\Smartee\SmartyEngine;
$latte->setTempDirectory(__DIR__ . '/tmp');
$latte->render(__DIR__ . '/templates/template.tpl', [
    'var' => 'value',
]);
```

## What is done
 * capture
 * foreach
 * if
 * include
 * literal
 * var

## TODO
 * add `$smarty` variables
 * add rest of the functions
 * add variable modifier
 * create `Smarty` bridge class for easier transition
 * create bridge class to allow register Smarty functions to Latte
