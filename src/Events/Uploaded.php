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
 * Class Uploaded
 * @author  : hongbinwang
 * @time    : 2023/2/13 16:22
 * @package Sanlilin\LaravelUEditorPlus\Events
 *
 */
class Uploaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var UploadedFile
     */
    public $file;

    /**
     * @var array
     */
    public $result;
	
	/**
	 * Uploaded constructor.
	 * @param UploadedFile $file
	 * @param array        $result
	 */
    public function __construct(UploadedFile $file, array $result)
    {
        $this->file = $file;
        $this->result = $result;
    }
}
