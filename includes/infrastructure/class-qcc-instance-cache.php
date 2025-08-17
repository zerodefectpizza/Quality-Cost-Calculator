<?php
/**
 * Instance Caching für Performance
 */
class QCC_Instance_Cache {
    private static $cache = array();
    
    public static function get($key) {
        return self::$cache[$key] ?? null;
    }
    
    public static function set($key, $value) {
        self::$cache[$key] = $value;
    }
    
    public static function clear($key = null) {
        if ($key) {
            unset(self::$cache[$key]);
        } else {
            self::$cache = array();
        }
    }
}