<?php

namespace Jacobcyl\AliOSS;

use Jacobcyl\AliOSS\Plugins\PutFile;
use Jacobcyl\AliOSS\Plugins\PutRemoteFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use OSS\OssClient;

class AliOssServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //发布配置文件
        /*
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__ . '/config/config.php' => config_path('alioss.php'),
            ], 'config');
        }
        */

        Storage::extend('oss', function($app, $config)
        {
            $accessId  = $config['access_id'];
            $accessKey = $config['access_key'];

            $cdnDomain = empty($config['cdnDomain']) ? '' : $config['cdnDomain'];
            $bucket    = $config['bucket'];
            $ssl       = empty($config['ssl']) ? false : $config['ssl'];
            $isCname   = empty($config['isCName']) ? false : $config['isCName'];
            $isPrivate     = empty($config['isPrivate']) ? false : $config['isPrivate'];
            $debug     = empty($config['debug']) ? false : $config['debug'];

            $endPoint  = $config['endpoint']; // 默认作为外部节点
            $epInternal= $isCname?$cdnDomain:(empty($config['endpoint_internal']) ? $endPoint : $config['endpoint_internal']); // 内部节点

            if($debug) Log::debug('OSS config:', $config);
            //域名没有绑定bucket,但是有需要cdn,暂时这么修改，待完善。
            $client  = new OssClient($accessId, $accessKey, $endPoint, false);
            $client->setUseSSL($ssl);
//            $client  = new OssClient($accessId, $accessKey, $epInternal, $isCname);
            $adapter = new AliOssAdapter($client, $bucket, $endPoint, $ssl, $isCname, $isPrivate, $debug, $cdnDomain);

            //Log::debug($client);
            $filesystem =  new Filesystem($adapter);

            $filesystem->addPlugin(new PutFile());
            $filesystem->addPlugin(new PutRemoteFile());
            //$filesystem->addPlugin(new CallBack());
            return $filesystem;
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
    }

}
