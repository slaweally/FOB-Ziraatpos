<div class="payment-setting-item">
    <div class="payment-setting-header">
        <h4>Ziraat Bank</h4>
    </div>
    <div class="payment-setting-body">
        <div class="form-group mb-3">
            <label class="text-title-field">{{ __('Merchant ID') }}</label>
            <input type="text" class="next-input" name="payment_ziraat_bank_merchant_id" 
                   value="{{ get_payment_setting('merchant_id', 'ziraat-bank') }}" 
                   placeholder="{{ __('Merchant ID') }}">
        </div>
        <div class="form-group mb-3">
            <label class="text-title-field">{{ __('Store Key') }}</label>
            <input type="password" class="next-input" name="payment_ziraat_bank_store_key" 
                   value="{{ get_payment_setting('store_key', 'ziraat-bank') }}" 
                   placeholder="{{ __('Store Key') }}">
        </div>
        <div class="form-group mb-3">
            <label class="text-title-field">{{ __('Test Mode') }}</label>
            <select class="next-input" name="payment_ziraat_bank_test_mode">
                <option value="0" {{ get_payment_setting('test_mode', 'ziraat-bank', 0) == 0 ? 'selected' : '' }}>
                    {{ __('Production') }}
                </option>
                <option value="1" {{ get_payment_setting('test_mode', 'ziraat-bank', 0) == 1 ? 'selected' : '' }}>
                    {{ __('Test') }}
                </option>
            </select>
        </div>
    </div>
</div>

