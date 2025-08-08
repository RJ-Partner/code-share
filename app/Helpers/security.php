<?php
if (!function_exists('encrypt_string')) {
    function encrypt_string($value)
    {
        return encrypt($value);
    }
}

if (!function_exists('decrypt_string')) {
    function decrypt_string($value)
    {
        return decrypt($value);
    }
}
