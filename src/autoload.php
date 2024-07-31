<?php
spl_autoload_register(function ($class_name) {
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
    require_once __DIR__ . '/' . $path . '.php';
});
?>
