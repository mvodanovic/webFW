<?php

namespace mvodanovic\WebFW;

use Exception;

/**
 * Class Bootstrap
 *
 * Framework bootstrap.
 * Loads required constants and initializes the autoloader.
 * This class is used before the framework is started.
 *
 * @package mvodanovic\WebFW
 */
final class Bootstrap
{
    private static $instance = null;

    private $autoLoadDefinitions = [];

    /**
     * Returns the Bootstrap instance.
     *
     * @return Bootstrap
     * @internal
     */
    private static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Initializes the bootstrap.
     */
    public static function init()
    {
        spl_autoload_unregister([static::getInstance(), 'loadClass']);
        spl_autoload_register([static::getInstance(), 'loadClass']);
    }

    /**
     * Function used by the autoloader to load classes.
     *
     * @param string $className Name of the class to load
     * @return bool True if class is found
     * @throws BootstrapException if class is not found
     */
    private function loadClass($className)
    {
        $classNameChunks = explode('\\', $className);
        $file = str_replace(['\\', '_'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $className);
        $pointer = &$this->autoLoadDefinitions;
        foreach ($classNameChunks as $chunk) {

            if (is_array($pointer) && array_key_exists($chunk, $pointer)) {
                $file = substr($file, strlen($chunk . DIRECTORY_SEPARATOR));
                $pointer = &$pointer[$chunk];
            } elseif (is_string($pointer)) {
                $file = $pointer . $file;
                break;
            }
        }
        $file = Core\BASE_PATH . DIRECTORY_SEPARATOR . $file . '.php';

        if (is_readable($file)) {
            /** @noinspection PhpIncludeInspection */
            require $file;
            return true;
        }

        throw new BootstrapException('Class "' . $className . '" not found in "' . $file . '"', 1);
    }

    /**
     * Initializes most of the framework's constants.
     */
    private function initBasicConstants()
    {
        switch (PHP_SAPI) {
            case 'apache':
            case 'apache2filter':
            case 'apache2handler':
                define('mvodanovic\WebFW\Core\PUBLIC_PATH', realpath(dirname($_SERVER['SCRIPT_FILENAME'])));
                define('mvodanovic\WebFW\Core\BASE_PATH', realpath(Core\PUBLIC_PATH . '/..'));
                break;
            case 'cli':
            case 'cli-server':
                define('mvodanovic\WebFW\Core\BASE_PATH', realpath(dirname(__FILE__)));
                break;
            default:
                throw new BootstrapException('PHP SAPI "' . PHP_SAPI . '" not supported', 1);
        }
        define('mvodanovic\WebFW\Core\GENERAL_TEMPLATE_PATH', realpath(Core\BASE_PATH . '/Templates'));
        define('mvodanovic\WebFW\Core\CTL_TEMPLATE_PATH', realpath(Core\GENERAL_TEMPLATE_PATH . '/Controllers'));
        define('mvodanovic\WebFW\Core\BASE_TEMPLATE_PATH', realpath(Core\GENERAL_TEMPLATE_PATH . '/Base'));
        define('mvodanovic\WebFW\Core\CMP_TEMPLATE_PATH', realpath(Core\GENERAL_TEMPLATE_PATH . '/Components'));
    }

    /**
     * Initializes the FW_PATH constant which is dependant on the settings in composer.json.
     */
    private function initFrameworkConstant()
    {
        if (array_key_exists('mvodanovic', $this->autoLoadDefinitions)) {
            if (array_key_exists('WebFW', $this->autoLoadDefinitions['mvodanovic'])) {
                define(
                'mvodanovic\WebFW\Core\FW_PATH',
                realpath(Core\BASE_PATH . DIRECTORY_SEPARATOR . $this->autoLoadDefinitions['mvodanovic']['WebFW'])
                );
            }
        }
    }

    /**
     * Reads composer.json and parses relevant data from it.
     */
    private function loadComposerDefinitions()
    {
        $composerJSONPath = Core\BASE_PATH . '/composer.json';
        if (!file_exists($composerJSONPath)) {
            return;
        }

        $composerDefinition = json_decode(file_get_contents($composerJSONPath), true);
        if ($composerDefinition === null) {
            return;
        }

        if (!array_key_exists('autoload', $composerDefinition)) {
            return;
        }

        $this->processAutoLoadDefinitions($composerDefinition['autoload'], 'psr-0');
        $this->processAutoLoadDefinitions($composerDefinition['autoload'], 'psr-4');
    }

    /**
     * Processes autoload definitions and prepares the for use by the autoloader.
     *
     * @param array $autoLoadDefinitions JSON-decoded array of autoload definitions from composer.json
     * @param string $autoLoadType Type of definitions to process ('psr-0' or 'psr-4')
     */
    private function processAutoLoadDefinitions($autoLoadDefinitions, $autoLoadType)
    {
        if (!array_key_exists($autoLoadType, $autoLoadDefinitions)) {
            return;
        }

        foreach ($autoLoadDefinitions[$autoLoadType] as $class => $path) {
            $classChunks = explode('\\', $class);
            $pointer = &$this->autoLoadDefinitions;
            foreach ($classChunks as $classChunk) {
                if ($classChunk === '') {
                    continue;
                }
                if (!array_key_exists($classChunk, $pointer)) {
                    $pointer[$classChunk] = [];
                }
                $pointer = &$pointer[$classChunk];
            }

            if ($autoLoadType === 'psr-0') {
                $path = str_replace(['\\', '_'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $class) . $path;
            }

            $pointer = $path;
        }
    }

    /**
     * Class constructor.
     */
    private function __construct() {
        $this->initBasicConstants();
        $this->loadComposerDefinitions();
        $this->initFrameworkConstant();
        set_include_path(get_include_path() . ':' . Core\BASE_PATH);
    }

    /**
     * Class cannot be cloned.
     */
    private function __clone() {}
}

/**
 * Class BootstrapException
 *
 * This exception should be thrown only within the Bootstrap class.
 * If it is thrown, it is probable it occurred before the framework was started.
 * This is why all exception details are echoed in plain text.
 *
 * @package mvodanovic\WebFW
 */
final class BootstrapException extends Exception
{
    /**
     * Echoes exception details in plain text.
     */
    public function errorMessage()
    {
        header('Content-type: text/plain; charset=UTF-8');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo "*** Bootstrap Exception ***\n\n";
        echo 'File: ', $this->getFile(), ', line: ', $this->getLine(), "\n";
        echo $this->getMessage(), "\n";
        echo "\nStack trace:\n", $this->getTraceAsString();
    }
}
