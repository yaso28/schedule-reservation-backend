<?php

namespace Tests\Utilities;

use App\Services\FormatService;

class DataHelper
{
    protected static function getFaker()
    {
        return resolve('Faker\Generator');
    }

    public static function randomText($maxLength = 10)
    {
        $result = self::getFaker()->realText(max($maxLength, 10));
        $result = str_replace(['、', '。'], [], $result);
        $result = mb_substr($result, 0, $maxLength);
        return $result;
    }

    public static function randomDate($from = '-1 months', $to = '+4 months')
    {
        return self::getFaker()
            ->valid(fn ($d) => in_array($d->format('w'), [6, 0, 1]))
            ->dateTimeBetween($from, $to)
            ->format(FormatService::DATE_FORMAT);
    }

    public static function randomTime($hourFrom = 0, $hourTo = 23)
    {
        $faker = self::getFaker();
        $hour = $faker->numberBetween($hourFrom, $hourTo);
        $minute = $faker->randomElement([0, 15, 30, 45]);
        return sprintf('%02d:%02d', $hour, $minute);
    }
}
