<?php

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\FormatService;

class FormatServiceTest extends TestCase
{
    protected function getService()
    {
        return resolve(FormatService::class);
    }

    public function provideForBool()
    {
        return [
            [true, true],
            [false, false],
            [1, true],
            [0, false],
        ];
    }

    /**
     * @dataProvider provideForBool
     */
    public function testBool($arg, $expected)
    {
        $this->assertEquals($expected, $this->getService()->bool($arg));
    }

    public function provideForDate()
    {
        return [
            ['2020-12-18', '2020-12-18'],
            ['2020-3-5', '2020-03-05'],
            ['2020/6/11', '2020-06-11'],
        ];
    }

    /**
     * @dataProvider provideForDate
     */
    public function testDate($arg, $expected)
    {
        $this->assertEquals($expected, $this->getService()->date($arg));
    }

    public function provideForDateMonthDayWeek()
    {
        return [
            ['2021-1-3', '01/03', 0],
            ['2021-1-4', '01/04', 1],
            ['2021-1-5', '01/05', 2],
            ['2021-1-6', '01/06', 3],
            ['2021-1-7', '01/07', 4],
            ['2021-1-8', '01/08', 5],
            ['2021-1-9', '01/09', 6],
        ];
    }

    /**
     * @dataProvider provideForDateMonthDayWeek
     */
    public function testateMonthDayWeek($arg, $expectedMd, $expectedWeekIndex)
    {
        $week = __('date.day_of_week')[$expectedWeekIndex];
        $expected = "${expectedMd}(${week})";
        $this->assertEquals($expected, $this->getService()->dateMonthDayWeek($arg));
    }

    public function provideForTime()
    {
        return [
            ['12:34', '12:34'],
            ['3:2', '03:02'],
            ['9:06', '09:06'],
        ];
    }

    /**
     * @dataProvider provideForTime
     */
    public function testTime($arg, $expected)
    {
        $this->assertEquals($expected, $this->getService()->time($arg));
    }

    public function provideForHtmlMailMessage()
    {
        $br = '<br />';
        $a = fn ($url) => "<a href=\"{$url}\">{$url}</a>";
        return [
            [
                "aaa\nbbb\nccc",
                "aaa{$br}\nbbb{$br}\nccc",
            ],
            [
                "aaa http://www.sample.org/test bbb",
                "aaa " . $a("http://www.sample.org/test") .  " bbb",
            ],
            [
                "aaa\nbbb\nhttps://sample.com/aaa/bbb?ccc=3&ddd=5\nccc",
                "aaa{$br}\nbbb{$br}\n" . $a("https://sample.com/aaa/bbb?ccc=3&ddd=5") . "{$br}\nccc",
            ],
            [
                "aaa\r\nbbb\r\nhttps://sample.com/aaa/bbb?ccc=3&ddd=5\r\nccc",
                "aaa{$br}\r\nbbb{$br}\r\n" . $a("https://sample.com/aaa/bbb?ccc=3&ddd=5") . "{$br}\r\nccc",
            ],
        ];
    }

    /**
     * @dataProvider provideForHtmlMailMessage
     */
    public function testHtmlMailMessage($arg, $expected)
    {
        $this->assertEquals($expected, $this->getService()->htmlMailMessage($arg));
    }
}
