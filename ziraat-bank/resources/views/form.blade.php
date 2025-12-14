<!DOCTYPE html>
<html>
<head>
    <title>Ödeme Yönlendiriliyor...</title>
</head>
<body>
    <form method="post" name="pay_form" action="{{ $apiEndpoint }}">
        @foreach($postParams as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
        @endforeach
        <input type="hidden" name="HASH" value="{{ $hash }}" />
    </form>
    <script>
        document.pay_form.submit();
    </script>
    <p>Ödeme sayfasına yönlendiriliyorsunuz...</p>
</body>
</html>

