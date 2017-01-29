<?php

/*
 * Bear Framework
 * http://bearframework.com
 * Copyright (c) 2016 Ivo Petkov
 * Free to use under the MIT license.
 */

namespace BearFramework\App\Hooks;

/**
 * @property string|null $filename The file that is used as a source for the response.
 * @property int|null $width The width of the outputted file.
 * @property int|null $height The height of the outputted file.
 */
class AssetPrepareData
{

    use \IvoPetkov\DataObjectTrait;

    function __construct()
    {
        $this->defineProperty('filename', [
            'type' => '?string'
        ]);
        $this->defineProperty('width', [
            'type' => '?int'
        ]);
        $this->defineProperty('height', [
            'type' => '?int'
        ]);
    }

}
