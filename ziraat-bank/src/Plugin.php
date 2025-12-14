<?php

namespace Botble\ZiraatBank;

use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Botble\Setting\Facades\Setting;

class Plugin extends PluginOperationAbstract
{
    public static function remove(): void
    {
        Setting::delete([
            'payment_ziraat_bank_name',
            'payment_ziraat_bank_description',
            'payment_ziraat_bank_merchant_id',
            'payment_ziraat_bank_store_key',
            'payment_ziraat_bank_test_mode',
            'payment_ziraat_bank_status',
        ]);
    }
}

