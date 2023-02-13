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
 * Class Uploading
 * @author  : hongbinwang
 * @time    : 2023/2/13 16:23
 * @package Sanlilin\LaravelUEditorPlus\Events
 *
 */
class Uploading
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var UploadedFile
     */
    public $file;

    /**
     * @var string
     */
    public $filename;

    /**
     * @var array
     */
    public $config;
	
	/**
	 * Uploading constructor.
	 * @param UploadedFile $file
	 * @param              $filename
	 * @param array        $config
	 */
    public function __construct(UploadedFile $file, $filename, array $config)
    {
        $this->file = $file;
        $this->filename = $filename;
        $this->config = $config;
    }
}
