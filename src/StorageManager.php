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

use SplFileInfo;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Sanlilin\LaravelUEditorPlus\Events\Uploaded;
use Sanlilin\LaravelUEditorPlus\Events\Uploading;
use Sanlilin\LaravelUEditorPlus\Events\Catched;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class StorageManager.
 */
class StorageManager
{
    use UrlResolverTrait;

    /**
     * @var Filesystem
     */
    protected $disk;

    /**
     * Constructor.
     *
     * @param Filesystem $disk
     */
    public function __construct(Filesystem $disk)
    {
        $this->disk = $disk;
    }
	
	/**
	 * Upload a file.
	 * @param Request $request
	 * @return JsonResponse
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:08
	 */
    public function upload(Request $request)
    {
        $config = $this->getUploadConfig($request->get('action'));

        if (!$request->hasFile($config['field_name'])) {
            return $this->error('UPLOAD_ERR_NO_FILE');
        }

        $file = $request->file($config['field_name']);

        if ($error = $this->fileHasError($file, $config)) {
            return $this->error($error);
        }

        $filename = $this->getFilename($file, $config);

        if ($this->eventSupport()) {
            $modifiedFilename = event(new Uploading($file, $filename, $config), [], true);
            $filename = !is_null($modifiedFilename) ? $modifiedFilename : $filename;
        }

        $this->store($file, $filename);

        $response = [
            'state' => 'SUCCESS',
            'url' => $this->getUrl($filename),
            'title' => $filename,
            'original' => $file->getClientOriginalName(),
            'type' => $file->getExtension(),
            'size' => $file->getSize(),
        ];

        if ($this->eventSupport()) {
            event(new Uploaded($file, $response));
        }

        return response()->json($response);
    }
	
	/**
	 * Fetch a file.
	 * @param Request $request
	 * @return JsonResponse
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:07
	 */
    public function fetch(Request $request)
    {
        $config = $this->getUploadConfig($request->get('action'));
        $urls = $request->get($config['field_name']);
        if (count($urls) === 0) {
            return $this->error('UPLOAD_ERR_NO_FILE');
        }
        $urls = array_unique($urls);

        $list = array();
        foreach ($urls as $key => $url) {
            $img = $this->download($url, $config);
            $item = [];
            if ($img['state'] === 'SUCCESS') {
                $file = $img['file'];
                $filename = $img['filename'];
                $this->storeContent($file, $filename);
                if ($this->eventSupport()) {
                    unset($img['file']);
                    event(new Catched($img));
                }
            }
            unset($img['file']);
            $list[] = $img;
        }

        $response = [
            'state'=> count($list) ? 'SUCCESS':'ERROR',
            'list'=> $list
        ];

        return response()->json($response);
    }
	
	/**
	 * Download a file.
	 * @param $url
	 * @param $config
	 * @return array|JsonResponse
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:07
	 */
    private function download($url, $config)
    {
        if (strpos($url, 'http') !== 0) {
            return $this->error('ERROR_HTTP_LINK');
        }
        $pathRes = parse_url($url);
        $img = new SplFileInfo($pathRes['path']);
        $original = $img->getFilename();
        $ext = $img->getExtension();
        $title = md5($url) . '.' . $ext;
        $filename = $this->formatPath($config['path_format'], $title);
        $info = [
            'state' => 'SUCCESS',
            'url' => $this->getUrl($filename),
            'title' => $title,
            'original' => $original,
            'source' => $url,
            'size' => 0,
            'file' => '',
            'filename' => $filename,
        ];

        $context = stream_context_create(
            array('http' => array(
                'follow_location' => false, // don't follow redirects
            ))
        );
        $file = fopen($url, 'r', false, $context);
        if ($file === false) {
            $info['state'] = 'ERROR';
            return $info;
        }
        $content = stream_get_contents($file);
        fclose($file);

        $info['file'] = $content;
        $info['siez'] = strlen($content);
        return $info;
    }

    /**
     * @return bool
     */
    public function eventSupport()
    {
        return trait_exists('Illuminate\Foundation\Events\Dispatchable');
    }
	
	/**
	 * List all files of dir.
	 * @param       $path
	 * @param       $start
	 * @param       $size
	 * @param array $allowFiles
	 * @return array
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:08
	 */
    public function listFiles($path, $start, $size = 20, array $allowFiles = [])
    {
        $allFiles = $this->disk->listContents($path, true);
        $files = $this->paginateFiles($allFiles, $start, $size);

        return [
            'state' => empty($files) ? 'EMPTY' : 'SUCCESS',
            'list' => $files,
            'start' => $start,
            'total' => count($allFiles),
        ];
    }
	
	/**
	 * Split results.
	 * @param array $files
	 * @param       $start
	 * @param       $size
	 * @return array
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:08
	 */
    protected function paginateFiles(array $files, $start = 0, $size = 50)
    {
        return collect($files)->where('type', 'file')->splice($start)->take($size)->map(function ($file) {
            return [
                'url' => $this->getUrl($file['path']),
                'mtime' => $file['timestamp'],
            ];
        })->all();
    }
	
	/**
	 * Store file.
	 * @param UploadedFile $file
	 * @param              $filename
	 * @return bool
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:08
	 */
    protected function store(UploadedFile $file, $filename)
    {
        return $this->disk->put($filename, fopen($file->getRealPath(), 'r+'));
    }
	
	/**
	 * Store file from content.
	 * @param $content
	 * @param $filename
	 * @return bool
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:09
	 */
    protected function storeContent($content, $filename)
    {
        return $this->disk->put($filename, $content);
    }
	
	/**
	 * Validate the input file.
	 * @param UploadedFile $file
	 * @param array        $config
	 * @return false|int|string
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:09
	 */
    protected function fileHasError(UploadedFile $file, array $config)
    {
        $error = false;

        if (!$file->isValid()) {
            $error = $file->getError();
        } elseif ($file->getSize() > $config['max_size']) {
            $error = 'upload.ERROR_SIZE_EXCEED';
        } elseif (!empty($config['allow_files']) &&
            !in_array('.'.$file->getClientOriginalExtension(), $config['allow_files'])) {
            $error = 'upload.ERROR_TYPE_NOT_ALLOWED';
        }

        return $error;
    }
	
	/**
	 * Get the new filename of file.
	 * @param UploadedFile $file
	 * @param array        $config
	 * @return array|string|string[]|null
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:09
	 */
    protected function getFilename(UploadedFile $file, array $config)
    {
        $ext = '.'.$file->getClientOriginalExtension();

        $filename = config('ueditor-plus.hash_filename') ? md5($file->getFilename()).$ext : $file->getClientOriginalName();

        return $this->formatPath($config['path_format'], $filename);
    }
	
	/**
	 * Get configuration of current action.
	 * @param $action
	 * @return array
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:09
	 */
    protected function getUploadConfig($action)
    {
        $upload = config('ueditor-plus.upload');

        $prefixes = [
            'image', 'scrawl', 'snapscreen', 'catcher', 'video', 'file',
            'imageManager', 'fileManager',
        ];

        $config = [];

        foreach ($prefixes as $prefix) {
            if ($action == $upload[$prefix.'ActionName']) {
                $config = [
                    'action' => Arr::get($upload, $prefix.'ActionName'),
                    'field_name' => Arr::get($upload, $prefix.'FieldName'),
                    'max_size' => Arr::get($upload, $prefix.'MaxSize'),
                    'allow_files' => Arr::get($upload, $prefix.'AllowFiles', []),
                    'path_format' => Arr::get($upload, $prefix.'PathFormat'),
                ];

                break;
            }
        }

        return $config;
    }
	
	/**
	 * Make error response.
	 * @param $message
	 * @return JsonResponse
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:09
	 */
    protected function error($message)
    {
        return response()->json(['state' => trans("ueditor-plus::upload.{$message}")]);
    }
	
	/**
	 * Format the storage path.
	 * @param $path
	 * @param $filename
	 * @return array|string|string[]|null
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:10
	 */
    protected function formatPath($path, $filename)
    {
        $replacement = array_merge(explode('-', date('Y-y-m-d-H-i-s')), [$filename, time()]);
        $placeholders = ['{yyyy}', '{yy}', '{mm}', '{dd}', '{hh}', '{ii}', '{ss}', '{filename}', '{time}'];
        $path = str_replace($placeholders, $replacement, $path);

        //替换随机字符串
        if (preg_match('/\{rand\:([\d]*)\}/i', $path, $matches)) {
            $length = min($matches[1], strlen(PHP_INT_MAX));
            $path = preg_replace('/\{rand\:[\d]*\}/i', str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT), $path);
        }

        if (!str_contains($path, $filename)) {
            $path = Str::finish($path, '/').$filename;
        }

        return $path;
    }
}
