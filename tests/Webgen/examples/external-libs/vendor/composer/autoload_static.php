<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0c3d87ebee7a4d334d2dc7bb479e1ca1
{
    public static $classMap = array (
        'CzProject\\Arrays' => __DIR__ . '/..' . '/czproject/arrays/src/Arrays.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit0c3d87ebee7a4d334d2dc7bb479e1ca1::$classMap;

        }, null, ClassLoader::class);
    }
}
