<?php

/*
 * Bear Framework
 * http://bearframework.com
 * Copyright (c) 2016 Ivo Petkov
 * Free to use under the MIT license.
 */

/**
 * The is the class used to instantiate and configure you application.
 */
class App
{

    /**
     * Current version
     * @var string
     */
    const VERSION = '0.5.0';

    /**
     * The configuration of the application
     * @var App\Config 
     */
    public $config = null;

    /**
     * This object contains information about the request
     * @var App\Request
     */
    public $request = null;

    /**
     * This object hold the data about defined routes
     * @var App\Routes 
     */
    public $routes = null;

    /**
     * Logs data
     * @var App\Log 
     */
    public $log = null;

    /**
     * The object that is responsible for processing HTML Server Components
     * @var App\Components
     */
    public $components = null;

    /**
     * The place to register addons
     * @var App\Addons
     */
    public $addons = null;

    /**
     * List of hooks
     * @var App\Hooks
     */
    public $hooks = null;

    /**
     * Assets utility functions
     * @var App\Assets
     */
    public $assets = null;

    /**
     * Data storage
     * @var App\Data
     */
    public $data = null;

    /**
     * Data cache
     * @var App\Cache 
     */
    public $cache = null;

    /**
     * Registered classes for autoloading
     * @var array 
     */
    public $classes = [];

    /**
     * The instance of the App object. Only one can be created.
     * @var App 
     */
    public static $instance = null;

    /**
     * The constructor
     * @param array $config
     */
    function __construct($config = [])
    {

        if (self::$instance === null) {
            if (version_compare(phpversion(), '5.6.0', '<')) {
                ini_set('default_charset', 'UTF-8');
                ini_set('mbstring.internal_encoding', 'UTF-8');
            }
            ini_set('mbstring.func_overload', 7);
            ini_set("pcre.backtrack_limit", 100000000);
            ini_set("pcre.recursion_limit", 100000000);
            self::$instance = &$this;
        } else {
            throw new \Exception('App already constructed');
        }

        $this->config = new \App\Config($config);

        if ($this->config->handleErrors) {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            $handleError = function($message, $file, $line, $trace) {
                $data = "Error:";
                $data .= "\nMessage: " . $message;
                $data .= "\nFile: " . $file;
                $data .= "\nLine: " . $line;
                $data .= "\nTrace: " . $trace;
                $data .= "\nGET: " . print_r($_GET, true);
                $data .= "\nPOST: " . print_r($_POST, true);
                $data .= "\nSERVER: " . print_r($_SERVER, true);
                if ($this->config->logErrors && strlen($this->config->logsDir) > 0 && strlen($this->config->errorLogFilename) > 0) {
                    try {
                        $this->log->write($this->config->errorLogFilename, $data);
                    } catch (\Exception $e) {
                        
                    }
                }
                if ($this->config->displayErrors) {
                    ob_clean();
                    $response = new \App\Response\TemporaryUnavailable($data);
                    $response->disableHooks = true;
                } else {
                    $response = new \App\Response\TemporaryUnavailable();
                }
                $this->respond($response);
            };
            set_exception_handler(function($exception) use($handleError) {
                $handleError($exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTraceAsString());
            });
            register_shutdown_function(function() use($handleError) {
                $errorData = error_get_last();
                if (is_array($errorData)) {
                    $messageParts = explode(' in ' . $errorData['file'] . ':' . $errorData['line'], $errorData['message'], 2);
                    $handleError(trim($messageParts[0]), $errorData['file'], $errorData['line'], isset($messageParts[1]) ? trim(str_replace('Stack trace:', '', $messageParts[1])) : '');
                }
            });
            set_error_handler(function($errorNumber, $errorMessage, $errorFile, $errorLine) {
                throw new \ErrorException($errorMessage, 0, $errorNumber, $errorFile, $errorLine);
            }, E_ALL | E_STRICT);
            spl_autoload_register(function ($class) {
                if (isset($this->classes[$class])) {
                    $this->load($this->classes[$class]);
                }
            });
        }

        $this->request = new \App\Request();

        if (isset($_SERVER)) {

            if (isset($_SERVER['REQUEST_METHOD'])) {
                $this->request->method = $_SERVER['REQUEST_METHOD'];
            }
            if (isset($_SERVER['REQUEST_SCHEME'])) {
                $this->request->scheme = $_SERVER['REQUEST_SCHEME'] === 'https' || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
            }
            if (isset($_SERVER['SERVER_NAME'])) {
                $this->request->host = $_SERVER['SERVER_NAME'];
            }

            $path = isset($_SERVER['REQUEST_URI']) && strlen($_SERVER['REQUEST_URI']) > 0 ? urldecode($_SERVER['REQUEST_URI']) : '/';
            $position = strpos($path, '?');
            if ($position !== false) {
                $this->request->query = new \App\Request\Query(substr($path, $position + 1));
                $path = substr($path, 0, $position);
            }
            unset($position);

            $basePath = '';
            if (isset($_SERVER['SCRIPT_NAME'])) {
                $scriptName = $_SERVER['SCRIPT_NAME'];
                if (strpos($path, $scriptName) === 0) {
                    $basePath = $scriptName;
                    $path = substr($path, strlen($scriptName));
                } else {
                    $pathInfo = pathinfo($_SERVER['SCRIPT_NAME']);
                    $dirName = $pathInfo['dirname'];
                    if ($dirName === DIRECTORY_SEPARATOR) {
                        $basePath = '';
                        $path = $path;
                    } else {
                        $basePath = $dirName;
                        $path = substr($path, strlen($dirName));
                    }
                    unset($dirName);
                    unset($pathInfo);
                }
                unset($scriptName);
            }

            if ($this->request->scheme !== '' && $this->request->host !== '') {
                $this->request->path = new \App\Request\Path(isset($path{0}) ? $path : '/');
                $this->request->base = $this->request->scheme . '://' . $this->request->host . $basePath;
            }
            unset($path);
            unset($basePath);
        }
        $this->routes = new \App\Routes();
        $this->log = new \App\Log();
        $this->components = new \App\Components();
        $this->addons = new \App\Addons();
        $this->hooks = new \App\Hooks();
        $this->assets = new \App\Assets();
        if ($this->config->dataDir !== null) {
            $this->data = new \App\Data($this->config->dataDir);
            $this->cache = new \App\Cache();
        }
    }

    /**
     * Loads a file
     * @param string $filename The filename to be loaded
     * @throws \InvalidArgumentException
     * @return boolean TRUE if file loaded successfully. Otherwise returns FALSE.
     */
    function load($filename)
    {
        if (!is_string($filename)) {
            throw new \InvalidArgumentException('');
        }
        if (is_string($filename)) {
            $filename = realpath($filename);
            if ($filename !== false) {
                include_once $filename;
                return true;
            }
        }
        return false;
    }

    /**
     * Registers a class for autoloading
     * @param string $class The class name
     * @param string $filename The filename that contains the class
     * @throws \InvalidArgumentException
     */
    function registerClass($class, $filename)
    {
        if (!is_string($class)) {
            throw new \InvalidArgumentException('');
        }
        if (!is_string($filename)) {
            throw new \InvalidArgumentException('');
        }
        $this->classes[$class] = $filename;
    }

    /**
     * Constructs a url for the path specified
     * @param string $path The path
     * @return string Absolute URL containing the base URL plus the path given
     * @throws \InvalidArgumentException
     */
    function getUrl($path = '/')
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException('');
        }
        return $this->request->base . $path;
    }

    /**
     * Call this method to start the application. This method outputs the response.
     * @return void
     */
    function run()
    {
        $app = &$this; // needed for the app index file
        $context = new \App\Context($this->config->appDir);

        $this->hooks->execute('requestStarted');

        if (is_file($this->config->appDir . 'index.php')) {
            include realpath($this->config->appDir . 'index.php');

            if ($this->config->assetsPathPrefix !== null) {
                $this->routes->add($this->config->assetsPathPrefix . '*', function() use ($app) {
                    $filename = $app->assets->getFilename($app->request->path);
                    if ($filename === false) {
                        return new \App\Response\NotFound();
                    } else {
                        $response = new \App\Response\FileReader($filename);
                        if ($app->config->assetsMaxAge !== null) {
                            $response->setMaxAge((int) $app->config->assetsMaxAge);
                        }
                        $mimeType = $app->assets->getMimeType($filename);
                        if ($mimeType !== null) {
                            $response->headers[] = 'Content-Type: ' . $mimeType;
                        }
                        return $response;
                    }
                });
            }

            ob_start();
            $response = $this->routes->getResponse($this->request);
            ob_end_clean();
            if (!($response instanceof \App\Response)) {
                $response = new \App\Response\NotFound("Not Found");
            }
        } else {
            $response = new \App\Response\TemporaryUnavailable('Add your application code in ' . $this->config->appDir . 'index.php');
        }
        $this->respond($response);
    }

    /**
     * Outputs a response
     * @param App\Response $response The reponse object to output
     * @throws \InvalidArgumentException
     * @return void
     */
    function respond($response)
    {
        if ($response instanceof \App\Response) {
            if (!isset($response->disableHooks) || $response->disableHooks === false) {
                $response->content = $this->components->process($response->content);
                $this->hooks->execute('responseCreated', $response);
                $response->content = $this->components->process($response->content);
            }
            if ($response instanceof \App\Response) {
                if (!headers_sent()) {
                    foreach ($response->headers as $header) {
                        header($header);
                    }
                }
                if ($response instanceof \App\Response\FileReader) {
                    readfile($response->filename);
                } else {
                    echo $response->content;
                }
                return;
            }
        }
        throw new \InvalidArgumentException('The response argument must be of type \App\Response');
    }

    /**
     * Prevents multiple app instances
     * @return void
     */
    private function __clone()
    {
        
    }

    /**
     * Prevents multiple app instances
     * @return void
     */
    private function __wakeup()
    {
        
    }

}
