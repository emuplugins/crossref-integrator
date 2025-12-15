<?php

define('CARBON_FIELDS_FRONTEND_PATH', plugin_dir_path(__FILE__));
define('CARBON_FIELDS_FRONTEND_URL', plugin_dir_url(__FILE__));


spl_autoload_register(function ($class) {

    $prefix = 'Carbon_Fields\\Frontend\\';

    // só carrega classes desse namespace
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));

    $path = __DIR__ . '/src/' .
        str_replace('\\', '/', $relative) .
        '.php';

    if (file_exists($path)) {
        require_once $path;
    }
});
