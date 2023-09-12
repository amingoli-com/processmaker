<?php

namespace ProcessMaker\Helpers;

class MobileHelper
{
    public static function isMobile($userAgent)
    {
        $device = '/(Mobile|Android|Tablet|GoBrowser|[0-9]x[0-9]*|uZardWeb\/|Mini|Doris\/|Skyfire\/|iPhone|Fennec\/|Maemo|Iris\/|CLDC\-|Mobi\/)/uis';
        if (isset($_COOKIE["isMobile"]) && preg_match($device, $userAgent) && $_COOKIE["isMobile"] === "true") {
          return true;
        }

        return false;
    }
}
