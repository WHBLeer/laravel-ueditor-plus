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

use Illuminate\Support\Str;
use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use RuntimeException;

/**
 * Trait UrlResolverTrait.
 */
trait UrlResolverTrait
{
	/**
	 * @param $filename
	 * @return string
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:27
	 */
    public function getUrl($filename)
    {
	    $filename = ltrim($filename,'/');
	    if (config('ueditor-plus.disk')==='aws') {
		    return config('ueditor-plus.s3_url').$filename;
	    }
	    return asset($filename);
    }
	
	/**
	 * Get the URL for the file at the given path.
	 * @param $path
	 * @return string
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:28
	 */
    public function url($path)
    {
        $adapter = $this->disk->getDriver()->getAdapter();

        if (method_exists($adapter, 'getUrl')) {
            return $adapter->getUrl($path);
        } elseif ($adapter instanceof AwsS3Adapter) {
            return $this->getAwsUrl($adapter, $path);
        } elseif ($adapter instanceof LocalAdapter) {
            return $this->getLocalUrl($path);
        }

        throw new RuntimeException('This driver does not support retrieving URLs.');
    }
	
	/**
	 * Get the URL for the file at the given path.
	 * @param $adapter
	 * @param $path
	 * @return mixed
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:28
	 */
    protected function getAwsUrl($adapter, $path)
    {
        return $adapter->getClient()->getObjectUrl(
            $adapter->getBucket(), $adapter->getPathPrefix().$path
        );
    }
	
	/**
	 * Get the URL for the file at the given path.
	 * @param $path
	 * @return string
	 *
	 * @author: hongbinwang
	 * @time  : 2023/2/13 16:28
	 */
    protected function getLocalUrl($path)
    {
        $config = $this->disk->getDriver()->getConfig();

        // If an explicit base URL has been set on the disk configuration then we will use
        // it as the base URL instead of the default path. This allows the developer to
        // have full control over the base path for this filesystem's generated URLs.
        if ($config->has('url')) {
            return rtrim($config->get('url'), '/').'/'.ltrim($path, '/');
        }

        $path = '/storage/'.ltrim($path, '/');

        // If the path contains "storage/public", it probably means the developer is using
        // the default disk to generate the path instead of the "public" disk like they
        // are really supposed to use. We will remove the public from this path here.
        if (Str::contains($path, '/storage/public/')) {
            return Str::replaceFirst('/public/', '/', $path);
        }

        return $path;
    }
}
