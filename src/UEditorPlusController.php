<?php

/*
 * This file is part of the sanlilin/laravel-ueditor-plus.
 *
 * (c) sanlilin <wanghongbin816@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sanlilin\LaravelUEditorPlus;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;

/**
 * Class UEditorPlusController.
 */
class UEditorPlusController extends Controller
{
    public function serve(Request $request)
    {
	    $upload = config('ueditor-plus.upload');
	    $storage = app('ueditor-plus.storage');
	    switch ($request->get('action')) {
		    case 'config':
			    return config('ueditor-plus.upload');
		
		    // lists
		    case $upload['imageManagerActionName']:
			    $listPath = ($request->user) ? str_replace('/{user}', '/' . $request->get('user'), $upload['imageManagerListPath']) : $upload['imageManagerListPath'];
			    return $storage->listFiles(
				    $listPath,
				    $request->get('start'),
				    $request->get('size'),
				    $upload['imageManagerAllowFiles']);
		    case $upload['fileManagerActionName']:
			    $listPath = ($request->user) ? str_replace('/{user}', '/' . $request->get('user'), $upload['fileManagerListPath']) : $upload['fileManagerListPath'];
			    return $storage->listFiles(
				    $listPath,
				    $request->get('start'),
				    $request->get('size'),
				    $upload['fileManagerAllowFiles']);
		    case $upload['catcherActionName']:
			    return $storage->fetch($request);
		    default:
			    return $storage->upload($request);
	    }
    }
}
