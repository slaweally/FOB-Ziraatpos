<?php

namespace Botble\ZiraatBank\Providers;

use Botble\Base\Traits\LoadAndPublishDataTrait;
use Illuminate\Support\ServiceProvider;

class ZiraatBankServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        if (! is_plugin_active('invoice-payment')) {
            return;
        }

        $this->setNamespace('plugins/ziraat-bank')
            ->loadHelpers()
            ->loadAndPublishViews()
            ->publishAssets()
            ->loadAndPublishTranslations()
            ->loadRoutes();

        $this->app->register(HookServiceProvider::class);
    }
}

