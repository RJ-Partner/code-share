<?php
if (!function_exists('project_name')) {
    function project_name()
    {
        return config('app.name');
    }
}