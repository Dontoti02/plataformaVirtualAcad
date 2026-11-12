<?php

namespace Modules\Shared\Utils;

use DateTime;

class Date
{
    public static function invertDateFormat($date)
    {
        $date = str_replace('/', '-', $date);
        $date = DateTime::createFromFormat('d-m-Y', $date);
        $date = $date->format('Y-m-d');

        return $date;
    }

    public static function invertDateTimeFormat($date)
    {
        $date = str_replace('/', '-', $date);
        $date = DateTime::createFromFormat('d-m-Y H:i:s', $date);
        $date = $date->format('Y-m-d H:i:s');

        return $date;
    }
}
