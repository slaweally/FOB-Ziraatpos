<?php

namespace Botble\ZiraatBank\Providers;

use Botble\Base\Facades\Html;
use Botble\InvoicePayment\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Payment modülü hook'ları (opsiyonel)
        if (defined('PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS')) {
            add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, function (?string $html, array $data) {
                if (class_exists('\Botble\Payment\Facades\PaymentMethods')) {
                    \Botble\Payment\Facades\PaymentMethods::method(ZIRAAT_BANK_PAYMENT_METHOD_NAME, [
                        'html' => view('plugins/ziraat-bank::methods', $data)->render(),
                    ]);
                }

                return $html;
            }, 12, 2);
        }

        // Invoice payment processing
        add_action('invoice-payment.process', function (Invoice $invoice, Request $request) {
            if ($invoice->payment_provider_id !== ZIRAAT_BANK_PAYMENT_METHOD_NAME) {
                return;
            }

            $merchantId = $this->getSetting('payment_ziraat_bank_merchant_id');
            $storeKey = $this->getSetting('payment_ziraat_bank_store_key');
            $testMode = $this->getSetting('payment_ziraat_bank_test_mode', 0);

            $amount = $invoice->amount;
            $orderId = $invoice->id;
            $callback = route('invoice-payment.callback', ['gateway' => 'ziraat-bank']);

            // Currency conversion
            $currency = $invoice->currency ? $invoice->currency->title : 'TRY';
            $currencyCode = '949'; // TL
            if ($currency === 'USD') {
                $currencyCode = '840';
            } elseif ($currency === 'EUR') {
                $currencyCode = '978';
            }

            // API endpoint
            $apiEndpoint = $testMode 
                ? "https://entegrasyon.asseco-see.com.tr/fim/est3dgate"
                : "https://sanalpos2.ziraatbank.com.tr/fim/est3Dgate";

            $rnd = uniqid();
            $postParams = [
                "clientid" => $merchantId,
                "amount" => $amount,
                "okurl" => "",
                "failUrl" => $callback,
                "TranType" => "Auth",
                "Instalment" => "",
                "callbackUrl" => $callback,
                "rnd" => $rnd,
                "oid" => $orderId,
                "storetype" => "3D_PAY_HOSTING",
                "hashAlgorithm" => "ver3",
                "lang" => "tr",
                "currency" => $currencyCode,
            ];

            // Generate hash
            natcasesort($postParams);
            $hashval = "";
            foreach ($postParams as $param => $value) {
                $lowerParam = strtolower($param);
                if ($lowerParam != "hash" && $lowerParam != "encoding") {
                    $escapedValue = str_replace("|", "\\|", str_replace("\\", "\\\\", $value));
                    $hashval = $hashval . $escapedValue . "|";
                }
            }
            $escapedStoreKey = str_replace("|", "\\|", str_replace("\\", "\\\\", $storeKey));
            $hashval = $hashval . $escapedStoreKey;
            $calculatedHashValue = hash('sha512', $hashval);
            $hash = base64_encode(pack('H*', $calculatedHashValue));

            echo view('plugins/ziraat-bank::form', [
                'apiEndpoint' => $apiEndpoint,
                'postParams' => $postParams,
                'hash' => $hash,
            ])->render();

            exit;
        }, 10, 2);

        // Payment modülü settings hook'u (opsiyonel)
        if (defined('PAYMENT_METHODS_SETTINGS_PAGE')) {
            add_filter(PAYMENT_METHODS_SETTINGS_PAGE, function (?string $html) {
                return $html . view('plugins/ziraat-bank::settings')->render();
            }, 92);
        }

        // Payment modülü enum hook'ları (opsiyonel)
        if (class_exists('\Botble\Payment\Enums\PaymentMethodEnum')) {
            add_filter(BASE_FILTER_ENUM_ARRAY, function ($values, $class) {
                if ($class === \Botble\Payment\Enums\PaymentMethodEnum::class) {
                    $values['ZIRAAT_BANK'] = ZIRAAT_BANK_PAYMENT_METHOD_NAME;
                }

                return $values;
            }, 19, 2);

            add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class) {
                if ($class == \Botble\Payment\Enums\PaymentMethodEnum::class && $value == ZIRAAT_BANK_PAYMENT_METHOD_NAME) {
                    $value = 'Ziraat Bank';
                }

                return $value;
            }, 19, 2);

            add_filter(BASE_FILTER_ENUM_HTML, function ($value, $class) {
                if ($class == \Botble\Payment\Enums\PaymentMethodEnum::class && $value == ZIRAAT_BANK_PAYMENT_METHOD_NAME) {
                    $value = Html::tag(
                        'span',
                        \Botble\Payment\Enums\PaymentMethodEnum::getLabel($value),
                        ['class' => 'label-success status-label']
                    )->toHtml();
                }

                return $value;
            }, 19, 2);
        }

        // Handle callback
        add_action('invoice-payment.callback.ziraat-bank', function (Invoice $invoice, Request $request) {
            if (isset($request->Response) && $request->Response == "Approved" && 
                isset($request->mdStatus) && $request->mdStatus == 1 && 
                isset($request->oid)) {
                
                if ($invoice->id == $request->oid && $invoice->status === 'pending') {
                    // Payment işlemini kaydet
                    if (defined('PAYMENT_ACTION_PAYMENT_PROCESSED')) {
                        $status = class_exists('\Botble\Payment\Enums\PaymentStatusEnum') 
                            ? \Botble\Payment\Enums\PaymentStatusEnum::COMPLETED 
                            : 'completed';
                        
                        do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                            'amount' => $invoice->amount,
                            'currency' => $invoice->currency ? $invoice->currency->title : 'TRY',
                            'charge_id' => $request->TransId ?? uniqid(),
                            'payment_channel' => ZIRAAT_BANK_PAYMENT_METHOD_NAME,
                            'status' => $status,
                            'customer_id' => $invoice->customer_id,
                            'customer_type' => $invoice->customer_type,
                            'order_id' => $invoice->id,
                        ], $request);
                    }

                    $invoice->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    return redirect()->route('invoice-payment.success');
                }
            }
            
            return redirect()->route('invoice-payment.error')
                ->with('error', 'Ödeme işlemi başarısız oldu.');
        }, 10, 2);
    }

    protected function getSetting(string $key, $default = null)
    {
        // Önce direkt setting'den oku
        if (function_exists('setting')) {
            $value = setting($key, null);
            if ($value !== null) {
                return $value;
            }
        }

        // Eğer yoksa get_payment_setting ile dene
        if (function_exists('get_payment_setting')) {
            $gatewayId = str_replace(['payment_ziraat_bank_', 'payment_'], '', $key);
            return get_payment_setting($gatewayId, 'ziraat-bank', $default);
        }

        return $default;
    }
}
