<?php

/*
|--------------------------------------------------------------------------
| WordPress-Style Escape Helpers (Blade Compatibility)
|--------------------------------------------------------------------------
|
| Beberapa blade template lama (terutama `gtk/profile.blade.php` yang dipakai
| sebagai referensi) menggunakan helper WordPress `esc_html()` dan
| `esc_attr()`. Laravel/Blade tidak menyediakan helper bawaan ini — di
| Blade, escaping dilakukan otomatis oleh sintaks `{{ }}` atau oleh helper
| `e()`. Helper di bawah ini menyediakan alias berbasis `e()` agar template
| lawas tidak meledak dengan `Call to undefined function`.
|
| Helper ini bukan pengganti escaping Blade otomatis; sebaiknya gunakan
| `{{ }}` di template baru.
|
*/

if (! function_exists('esc_html')) {
    /**
     * Escape a string for safe HTML output (mirrors WordPress esc_html).
     */
    function esc_html($text)
    {
        if ($text === null || $text === false) {
            return '';
        }

        return e((string) $text);
    }
}

if (! function_exists('esc_attr')) {
    /**
     * Escape a string for safe use in an HTML attribute
     * (mirrors WordPress esc_attr).
     */
    function esc_attr($text)
    {
        if ($text === null || $text === false) {
            return '';
        }

        return e((string) $text);
    }
}

if (! function_exists('esc_url')) {
    /**
     * Escape a URL for safe use in href/src attributes
     * (mirrors WordPress esc_url). Falls back to `e()` for now — URLs
     * should be validated by the caller for stricter needs.
     */
    function esc_url($url)
    {
        if ($url === null || $url === false) {
            return '';
        }

        return e((string) $url);
    }
}
