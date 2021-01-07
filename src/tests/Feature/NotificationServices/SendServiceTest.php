<?php

namespace Tests\Feature\NotificationServices;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\NotificationServices\SendService;

class SendServiceTest extends TestCase
{
    protected function getService()
    {
        return resolve(SendService::class);
    }

    public function testSend()
    {
        $message =
            "SendService.send()のテストを実行し、この通知が送信されました。\n" .
            "以下の内容を確認してください。\n" .
            "\n" .
            "・URL:https://readouble.com/laravel/8.x/ja/ ※HTML形式の場合はリンクが有効であること\n" .
            "・サイト名（HTML形式の場合はヘッダー、Text形式の場合はフッター）が正しいこと\n" .
            "・URL（フッター）がフロントエンドになっていること ※HTML形式の場合はリンクが有効であること\n" .
            "・HTML形式の場合はサイト名にフロントエンドのリンクが貼ってあること ※\n" .
            "・メールの場合は宛先（From,To,Bcc）が正しく設定されていること\n" .
            "\n" .
            "以上";
        $this->assertNull($this->getService()->send([
            'mail_to' => 'test@sample.com',
            'subject' => '【テスト実行メール】SendService.send()',
            'message' => $message,
        ]));
    }
}
