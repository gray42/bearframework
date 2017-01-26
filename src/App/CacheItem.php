<?php

/*
 * Bear Framework
 * http://bearframework.com
 * Copyright (c) 2016 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace BearFramework\App;

/**
 * @property string|null $key
 * @property mixed $value
 * @property int|null $ttl Time in seconds to stay in the cache
 */
class CacheItem
{

    use \IvoPetkov\DataObjectTrait;

    function __construct()
    {
        $this->defineProperty('key', [
            'type' => '?string'
        ]);
        $this->defineProperty('value');
        $this->defineProperty('ttl', [
            'type' => '?int'
        ]);
    }

}
