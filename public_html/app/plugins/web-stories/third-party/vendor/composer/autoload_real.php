<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit232f18315efa942e19a780653673f143
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Google_Web_Stories_Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Google_Web_Stories_Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInit232f18315efa942e19a780653673f143', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Google_Web_Stories_Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit232f18315efa942e19a780653673f143', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Google_Web_Stories_Composer\Autoload\ComposerStaticInit232f18315efa942e19a780653673f143::getInitializer($loader));

        $loader->setClassMapAuthoritative(true);
        $loader->register(true);

        return $loader;
    }
}
