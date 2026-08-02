<?php

use App\Services\NotificationUniversalService;

if (! function_exists('notify')) {
    function notify()
    {
        return app(NotificationUniversalService::class);
    }
}

if (! function_exists('notify_user')) {
    function notify_user($userId, $title, $message, $data = [])
    {
        return app(NotificationUniversalService::class)->send($userId, array_merge([
            'title' => $title,
            'message' => $message,
            'type' => 'info',
            'module' => 'system',
        ], $data));
    }
}

if (! function_exists('notify_role')) {
    function notify_role($roleName, $title, $message, $data = [])
    {
        return app(NotificationUniversalService::class)->sendToRole($roleName, array_merge([
            'title' => $title,
            'message' => $message,
            'type' => 'info',
            'module' => 'system',
        ], $data));
    }
}

if (! function_exists('notify_admins')) {
    function notify_admins($title, $message, $data = [])
    {
        return app(NotificationUniversalService::class)->sendToRole('admin', array_merge([
            'title' => $title,
            'message' => $message,
            'type' => 'warning',
            'module' => 'system',
            'priority' => 'high',
        ], $data));
    }
}

if (! function_exists('format_currency')) {
    function format_currency($amount)
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}

if (! function_exists('format_date')) {
    function format_date($date, $format = 'd F Y')
    {
        return $date ? \Carbon\Carbon::parse($date)->translatedFormat($format) : '-';
    }
}

if (! function_exists('generate_uuid')) {
    function generate_uuid()
    {
        return (string) \Illuminate\Support\Str::uuid();
    }
}
