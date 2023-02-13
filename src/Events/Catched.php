<?php

/*
 * This file is part of the sanlilin/laravel-ueditor-plus.
 *
 * (c) sanlilin <wanghongbin816@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sanlilin\LaravelUEditorPlus\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class Catched
 * @author  : hongbinwang
 * @time    : 2023/2/13 16:22
 * @package Sanlilin\LaravelUEditorPlus\Events
 *
 */
class Catched 
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var array
     */
    public $result;

    /**
     * Catched constructor.
     *
     * @param array  $result
     */
    public function __construct(array $result)
    {
        $this->result = $result;
    }
}
