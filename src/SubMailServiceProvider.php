<?php
namespace Bingoo\Mail;

use Illuminate\Mail\TransportManager;
use Illuminate\Support\ServiceProvider;

class SubMailServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/config/services.php', 'services'
        );

        $this->app->resolving('swift.transport', function (TransportManager $tm) {
            $tm->extend('submail', function () {
                $appid = config('services.submail.appid');
                $appkey = config('services.submail.appkey');

                return new SendCloudTransport($appid, $appkey);
            });
        });
    }

    public function boot()
    {

    }
}
