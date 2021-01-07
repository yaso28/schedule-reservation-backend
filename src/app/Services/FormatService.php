<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class FormatService
{
    public const DATE_FORMAT = 'Y-m-d';
    public const TIME_FORMAT = 'H:i';

    public function bool($value)
    {
        return !!$value;
    }

    protected function dateTimeObj($value)
    {
        return Carbon::parse($value);
    }

    public function date($value)
    {
        return $this->dateTimeObj($value)->format(self::DATE_FORMAT);
    }

    public function dateMonthDayWeek($value)
    {
        $date = $this->dateTimeObj($value);
        $md = $date->format('m/d');
        $week = __('date.day_of_week')[$date->weekday()];
        return "${md}(${week})";
    }

    public function time($value)
    {
        return $this->dateTimeObj($value)->format(self::TIME_FORMAT);
    }

    public function htmlMailMessage($value)
    {
        return nl2br(preg_replace(
            "{https?://[\w!#$%&'()*+,-./:;=?@_~]+}",
            '<a href="\0">\0</a>',
            $value
        ));
    }
}
