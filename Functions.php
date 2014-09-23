<?php

namespace Kf\KitBundle;

class Functions
{
    /**
     * @param string       $k
     * @param string|array $data
     * @return bool
     */
    public static function has($k, $data)
    {
        if (!is_array($data)) {
            return strcmp($k, $data) == 0;
        } else {
            foreach ($data as $p) {
                if (strcmp($k, $p) == 0) {
                    return true;
                }
            }

            return false;
        }
    }
} 
