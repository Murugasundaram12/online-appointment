@php
    $timezone = $business['timezone'] ?? config('app.timezone');
    $mapAddress = $business['address'] ?? ($appointment->location->address ?? null);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
</head>
<body style="margin:0;background:#f6f7fb;color:#111827;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f6f7fb;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:#4f46e5;padding:28px 32px;color:#ffffff;">
                            @if(!empty($business['logo']))
                                <img src="{{ $business['logo'] }}" alt="{{ $business['name'] ?? config('app.name') }}" style="max-height:48px;margin-bottom:16px;">
                            @endif
                            <div style="font-size:14px;opacity:.9;">{{ $business['name'] ?? config('app.name') }}</div>
                            <h1 style="margin:8px 0 0;font-size:26px;line-height:1.25;">@yield('heading')</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px;background:#f9fafb;border-top:1px solid #e5e7eb;color:#6b7280;font-size:13px;line-height:1.6;">
                            <strong style="color:#111827;">{{ $business['name'] ?? config('app.name') }}</strong><br>
                            @if(!empty($business['phone'])) Phone: {{ $business['phone'] }}<br>@endif
                            @if(!empty($business['email'])) Email: {{ $business['email'] }}<br>@endif
                            @if(!empty($mapAddress))
                                Address: {{ $mapAddress }}<br>
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($mapAddress) }}" style="color:#4f46e5;text-decoration:none;">Open in Google Maps</a>
                            @endif
                            <div style="margin-top:14px;">This message was sent about your appointment. Please contact us if anything looks incorrect.</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
