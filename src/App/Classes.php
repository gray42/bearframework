<?php

/*
 * Bear Framework
 * http://bearframework.com
 * Copyright (c) 2016 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace BearFramework\App;

/**
 * Provides functionality for autoloading classes
 */
class Classes
{

    /**
     * Registered classes
     * 
     * @var array 
     */
    private $data = [];

    /**
     * The constructor
     */
    public function __construct()
    {
        spl_autoload_register(function ($class) {
            $this->load($class);
        });
    }

    /**
     * Registers a class for autoloading
     * 
     * @param string $class The class name
     * @param string $filename The filename that contains the class
     * @throws \InvalidArgumentException
     * @return void No value is returned
     */
    public function add($class, $filename)
    {
        if (!is_string($class)) {
            throw new \InvalidArgumentException('The class argument must be of type string');
        }
        if (!is_string($filename)) {
            throw new \InvalidArgumentException('The filename argument must be of type string');
        }
        $filename = realpath($filename);
        if ($filename === false) {
            throw new \InvalidArgumentException('The filename specified does not exist');
        }
        $this->data[$class] = $filename;
    }

    /**
     * Returns information about whether a class is registered for autoloading
     * 
     * @param string $class The class name
     * @return boolen TRUE if the class is registered for autoloading. FALSE otherwise.
     * @throws \InvalidArgumentException
     */
    public function exists($class)
    {
        if (!is_string($class)) {
            throw new \InvalidArgumentException('The class argument must be of type string');
        }
        return isset($this->data[$class]);
    }

    /**
     * Loads a class if registered
     * 
     * @param string $class
     * @throws \InvalidArgumentException
     * @return void No value is returned
     */
    public function load($class)
    {
        if (!is_string($class)) {
            throw new \InvalidArgumentException('The class argument must be of type string');
        }
        if (isset($this->data[$class])) {
            include_once $this->data[$class];
        }
    }

}
