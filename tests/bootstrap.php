<?php declare(strict_types=1);

$root = dirname(__DIR__);

spl_autoload_register(static function (string $class) use ($root): void {
    if (str_starts_with($class, 'HtmlPlucker\\Tests\\')) {
        $rel  = str_replace('\\', '/', substr($class, strlen('HtmlPlucker\\Tests\\')));
        $file = $root . '/tests/' . $rel . '.php';
        if (file_exists($file)) require $file;
        return;
    }

    if (str_starts_with($class, 'HtmlPlucker\\')) {
        $rel  = str_replace('\\', '/', substr($class, strlen('HtmlPlucker\\')));
        $file = $root . '/src/' . $rel . '.php';
        if (file_exists($file)) require $file;
    }
});
