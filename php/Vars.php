<?php
class Vars
{
    public static function get($key, $default = false)
    {
        if (!isset($default)) $default = false;
        return isset($_GET[$key]) ? $_GET[$key] : (isset($_POST[$key]) ? $_POST[$key] : $default);
    }

    public static function getPostVar($key, $defaul = false)
    {
        return isset($_POST[$key]) ? $_POST[$key] : $defaul;
    }

    public static function getRequest($key, $default = false)
    {
        if (!isset($default)) $default = false;
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
    }

    public static function getAll()
    {
        return $_GET + $_POST;
    }

    public static function gets($key, $default = false)
    {
        return isset($_SERVER[$key]) ? $_SERVER[$key] : (isset($_GET[$key]) ? $_GET[$key] : (isset($_POST[$key]) ? $_POST[$key] : $default));
    }

    public static function getValidate($key, $allowed_values, $default = false)
    {
        $value = Vars::get($key, $default);
        if ($value === $default || !in_array($value, $allowed_values)) return $default;
        return $value;
    }

    public static function check($key)
    {
        return isset($_GET[$key]) || isset($_POST[$key]) || isset($_FILES[$key]);
    }
}

