<?php

/*
 * Bear Framework
 * http://bearframework.com
 * Copyright (c) 2016 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace BearFramework;

use BearFramework\App;

/**
 * The is the class used to instantiate and configure you application.
 * 
 * @property-read \BearFramework\App\Config $config The application configuration.
 * @property-read \BearFramework\App\Container $container Services container.
 * @property-read \BearFramework\App\Request $request Provides information about the current request.
 * @property-read \BearFramework\App\RoutesRepository $routes Stores the data about the defined routes callbacks.
 * @property-read \BearFramework\App\Logger $logger Provides logging functionality.
 * @property-read \BearFramework\App\AddonsRepository $addons Provides a way to enable addons and manage their options.
 * @property-read \BearFramework\App\HooksRepository $hooks Provides functionality for notifications and data requests.
 * @property-read \BearFramework\App\Assets $assets Provides utility functions for assets.
 * @property-read \BearFramework\App\DataRepository $data
 * @property-read \BearFramework\App\CacheRepository $cache Data cache.
 * @property-read \BearFramework\App\ClassesRepository $classes Provides functionality for autoloading classes.
 * @property-read \BearFramework\App\Urls $urls URLs utilities.
 * @property-read \BearFramework\App\Images $images Images utilities.
 * @property-read \BearFramework\App\ContextsRepository $context Context information object locator.
 * @property-read \BearFramework\App\ShortcutsRepository $shortcuts Allow registration of $app object properties (shortcuts).
 */
class App
{

    use \IvoPetkov\DataObjectTrait;

    /**
     * Current Bear Framework version
     * 
     * @var string
     */
    const VERSION = 'dev';

    /**
     * The instance of the App object. Only one can be created.
     * 
     * @var \BearFramework\App 
     */
    private static $instance = null;

    /**
     * Information about whether the application is initialized
     * 
     * @var bool 
     */
    private $initialized = false;

    /**
     * The constructor
     * 
     * @throws \Exception
     */
    public function __construct()
    {
        if (self::$instance !== null) {
            throw new \Exception('App already constructed');
        }
        self::$instance = &$this;

        $this->defineProperty('config', [
            'init' => function() {
                return new App\Config();
            },
            'readonly' => true
        ]);
        $this->defineProperty('container', [
            'init' => function() {
                $container = new App\Container();
                $container->set('app.logger', App\Logger::class);
                $container->set('app.cache', App\CacheRepository::class);
                return $container;
            },
            'readonly' => true
        ]);
        $request = null;
        $this->defineProperty('request', [
            'get' => function() use (&$request) {
                if ($this->initialized) {
                    if ($request === null) {
                        $request = new App\Request(true);
                    }
                    return $request;
                }
                return null;
            },
            'readonly' => true
        ]);
        $this->defineProperty('routes', [
            'init' => function() {
                $routes = new App\RoutesRepository();
                if ($this->config->assetsPathPrefix !== null) {
                    $routes->add($this->config->assetsPathPrefix . '*', function() {
                                return $this->assets->getResponse($this->request);
                            });
                    return $routes;
                }
            },
            'readonly' => true
        ]);
        $this->defineProperty('logger', [
            'get' => function() {
                return $this->container->get('app.logger');
            },
            'readonly' => true
        ]);
        $this->defineProperty('addons', [
            'init' => function() {
                return new App\AddonsRepository();
            },
            'readonly' => true
        ]);
        $this->defineProperty('hooks', [
            'init' => function() {
                return new App\HooksRepository();
            },
            'readonly' => true
        ]);
        $this->defineProperty('assets', [
            'init' => function() {
                $assets = new App\Assets();
                if ($this->config->dataDir !== null) {
                    $dataAssetsDir = $this->config->dataDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
                    $assets->addDir($dataAssetsDir);
                    $this->hooks->add('assetPrepare', function($data) use ($dataAssetsDir) {
                                if (strpos($data->filename, $dataAssetsDir) === 0) {
                                    $key = str_replace('\\', '/', substr($data->filename, strlen($dataAssetsDir)));
                                    if ($this->data->isPublic($key)) {
                                        $data->filename = $this->data->getFilename($key);
                                    } else {
                                        $data->filename = null;
                                    }
                                }
                            });
                }
                return $assets;
            },
            'readonly' => true
        ]);
        $this->defineProperty('data', [
            'init' => function() {
                return new App\DataRepository();
            },
            'readonly' => true
        ]);
        $this->defineProperty('cache', [
            'get' => function() {
                return $this->container->get('app.cache');
            },
            'readonly' => true
        ]);
        $this->defineProperty('classes', [
            'init' => function() {
                return new App\ClassesRepository();
            },
            'readonly' => true
        ]);
        $this->defineProperty('urls', [
            'init' => function() {
                return new App\Urls();
            },
            'readonly' => true
        ]);
        $this->defineProperty('images', [
            'init' => function() {
                return new App\Images();
            },
            'readonly' => true
        ]);
        $this->defineProperty('context', [
            'init' => function() {
                return new App\ContextsRepository();
            },
            'readonly' => true
        ]);
        $this->defineProperty('shortcuts', [
            'init' => function() {
                $initPropertyMethod = function($callback) { // needed to preserve the $this context
                            return $callback();
                        };
                $addPropertyMethod = function($name, $callback) use (&$initPropertyMethod) {
                            $this->defineProperty($name, [
                                'init' => function() use (&$callback, &$initPropertyMethod) {
                                    return $initPropertyMethod($callback);
                                },
                                'readonly' => true
                            ]);
                        };

                return new class($addPropertyMethod) {

                    private $addPropertyMethod = null;

                    public function __construct($addPropertyMethod)
                    {
                        $this->addPropertyMethod = $addPropertyMethod;
                    }

                    public function add(string $name, callable $callback)
                    {
                        call_user_func($this->addPropertyMethod, $name, $callback);
                    }
                };
            },
            'readonly' => true
        ]);
    }

    /**
     * Returns the app instance
     * 
     * @return \BearFramework\App
     * @throws \Exception
     */
    static function get(): \BearFramework\App
    {
        if (self::$instance === null) {
            throw new \Exception('App is not constructed yet');
        }
        return self::$instance;
    }

    /**
     * Initializes the environment, the error handlers, includes the app index.php file, the addons index.php files, and registers the assets handler
     */
    public function initialize(): void
    {
        if (!$this->initialized) {
            // @codeCoverageIgnoreStart
            if ($this->config->updateEnvironment) {
                ini_set('mbstring.func_overload', 7);
                ini_set("pcre.backtrack_limit", 100000000);
                ini_set("pcre.recursion_limit", 100000000);
            }
            if ($this->config->handleErrors) {
                set_exception_handler(function($exception) {
                    \BearFramework\App\ErrorHandler::handleException($exception);
                });
                register_shutdown_function(function() {
                    $errorData = error_get_last();
                    if (is_array($errorData)) {
                        \BearFramework\App\ErrorHandler::handleFatalError($errorData);
                    }
                });
                set_error_handler(function($errorNumber, $errorMessage, $errorFile, $errorLine) {
                    throw new \ErrorException($errorMessage, 0, $errorNumber, $errorFile, $errorLine);
                });
            }
            // @codeCoverageIgnoreEnd

            $this->initialized = true; // The request property counts on this. It must be here so that app and addons index.php files can access it.

            if (strlen($this->config->appDir) > 0) {
                $indexFilename = realpath($this->config->appDir . DIRECTORY_SEPARATOR . 'index.php');
                if ($indexFilename !== false) {
                    ob_start();
                    try {
                        (static function($__filename) {
                            include $__filename;
                        })($indexFilename);
                        ob_end_clean();
                    } catch (\Exception $e) {
                        ob_end_clean();
                        throw $e;
                    }
                }
            }

            $this->hooks->execute('initialized');
        }
    }

    /**
     * Call this method to start the application. This method initializes the app and outputs the response.
     * 
     * @return void No value is returned
     */
    public function run(): void
    {
        $this->initialize();
        $response = $this->routes->getResponse($this->request);
        if (!($response instanceof App\Response)) {
            $response = new App\Response\NotFound();
        }
        $this->respond($response);
    }

    /**
     * Outputs a response
     * 
     * @param \BearFramework\App\Response $response The response object to output
     * @return void No value is returned
     */
    public function respond(\BearFramework\App\Response $response): void
    {
        $this->hooks->execute('responseCreated', $response);
        http_response_code($response->statusCode);
        if (!headers_sent()) {
            $headers = $response->headers->getList();
            foreach ($headers as $header) {
                if ($header->name === 'Content-Type') {
                    $header->value .= '; charset=' . $response->charset;
                }
                header($header->name . ': ' . $header->value);
            }
            $cookies = $response->cookies->getList();
            if ($cookies->length > 0) {
                $baseUrlParts = parse_url($this->request->base);
                foreach ($cookies as $cookie) {
                    setcookie($cookie->name, $cookie->value, $cookie->expire, $cookie->path === null ? (isset($baseUrlParts['path']) ? $baseUrlParts['path'] . '/' : '/') : $cookie->path, $cookie->domain === null ? (isset($baseUrlParts['host']) ? $baseUrlParts['host'] : '') : $cookie->domain, $cookie->secure === null ? $this->request->scheme === 'https' : $cookie->secure, $cookie->httpOnly);
                }
            }
        }
        if ($response instanceof App\Response\FileReader) {
            readfile($response->filename);
        } else {
            echo $response->content;
        }
        $this->hooks->execute('responseSent', $response);
    }

    /**
     * Prevents multiple app instances
     * 
     * @throws \Exception
     * @return void No value is returned
     */
    public function __clone()
    {
        throw new \Exception('Cannot have multiple App instances');
    }

    /**
     * Prevents multiple app instances
     * 
     * @throws \Exception
     * @return void No value is returned
     */
    public function __wakeup()
    {
        throw new \Exception('Cannot have multiple App instances');
    }

}
