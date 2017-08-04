<?php

/*
 * Bear Framework
 * http://bearframework.com
 * Copyright (c) 2016-2017 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace BearFramework\App;

use BearFramework\App;

/**
 * Provides information about your code context (is it in the app dir, or is it in an addon dir).
 */
class ContextsRepository
{

    /**
     *
     * @var array 
     */
    private static $dirsCache = [];

    /**
     *
     * @var array 
     */
    private static $objectsCache = [];

    /**
     * Returns a context object for the filename specified.
     * 
     * @param string $filename The filename used to find the context.
     * @throws \Exception
     * @return \BearFramework\App\Context The context object for the filename specified.
     */
    public function get(string $filename)
    {
        if (isset(self::$objectsCache[$filename])) {
            return clone(self::$objectsCache[$filename]);
        }
        $matchedDir = null;
        for ($i = 0; $i < 2; $i++) { // first try - check cache, second try - update cache and check again
            foreach (self::$dirsCache as $dir) {
                if (substr($filename, 0, $dir[1]) === $dir[0] || $dir[0] === $filename . DIRECTORY_SEPARATOR) {
                    $matchedDir = $dir[0];
                    break;
                }
            }
            if ($matchedDir !== null) {
                break;
            }
            if ($i === 0) {
                $app = App::get();
                if (!isset(self::$dirsCache['app'])) {
                    $appDir = $app->config->appDir;
                    if ($appDir !== null) {
                        $dir = $appDir . DIRECTORY_SEPARATOR;
                        self::$dirsCache['app'] = [$dir, strlen($dir)];
                    } else {
                        self::$dirsCache['app'] = ['', 0];
                    }
                }
                $addons = $app->addons->getList();
                foreach ($addons as $addon) {
                    if (!isset(self::$dirsCache[$addon->id])) {
                        $dir = $addon->dir . DIRECTORY_SEPARATOR;
                        self::$dirsCache[$addon->id] = [$dir, strlen($dir)];
                    }
                }
            }
        }
        if ($matchedDir !== null) {
            if (isset(self::$objectsCache[$matchedDir])) {
                return clone(self::$objectsCache[$matchedDir]);
            }
            self::$objectsCache[$matchedDir] = new App\Context(substr($matchedDir, 0, -1));
            self::$objectsCache[$filename] = clone(self::$objectsCache[$matchedDir]);
            return clone(self::$objectsCache[$matchedDir]);
        }
        throw new \Exception('Connot find context for ' . $filename);
    }

}
