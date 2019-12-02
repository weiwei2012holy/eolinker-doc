<?php
/**
 * Desc:
 * Author: 余伟<weiwei2012holy@hotmail.com>
 * Date: 2019-09-02,19:41
 */

namespace Weiwei2012holy\EolinkerDoc;


use Weiwei2012holy\EolinkerDoc\Commands\CreateCode;
use Weiwei2012holy\EolinkerDoc\Commands\CreateDoc;
use Weiwei2012holy\EolinkerDoc\Models\ApiDocGenerateTool;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/eolinker.php' => config_path('eolinker.php'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateDoc::class,
                CreateCode::class,
            ]);
        }
    }

    public function register()
    {
        $this->app->singleton(ApiDocGenerateTool::class, function () {
            return new ApiDocGenerateTool();
        });

        $this->app->alias(ApiDocGenerateTool::class, 'eolinker');
    }

    public function provides()
    {
        return [ApiDocGenerateTool::class, 'eolinker'];
    }


}
