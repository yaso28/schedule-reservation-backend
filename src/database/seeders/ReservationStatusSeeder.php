<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReservationStatus as Master;

class ReservationStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Master::updateOrCreate(
            ['name' => '未予約'],
            [
                'description' => 'まだ予約していないものです。',
                'reserved' => false,
                'order_reverse' => 6,
            ]
        );
        Master::updateOrCreate(
            ['name' => '電話予約済'],
            [
                'description' => '電話予約したものです。申請書はまだ提出していません。',
                'reserved' => true,
                'order_reverse' => 5,
            ]
        );
        Master::updateOrCreate(
            ['name' => '書類提出済'],
            [
                'description' => '他の方が申請書を提出して予約したものです。申請書の控えはまだ手元にありません。',
                'reserved' => true,
                'order_reverse' => 4,
            ]
        );
        Master::updateOrCreate(
            ['name' => '書類控取得'],
            [
                'description' => '申請書を提出して予約し、控えが手元にあるものです。まだ料金を支払っていません。',
                'reserved' => true,
                'order_reverse' => 3,
            ]
        );
        Master::updateOrCreate(
            ['name' => '料金支払済'],
            [
                'description' => '予約して使用料を立て替えたものです。まだ精算してもらっていません。',
                'reserved' => true,
                'order_reverse' => 2,
            ]
        );
        Master::updateOrCreate(
            ['name' => '会計精算済'],
            [
                'description' => '立て替えていた使用料を会計の方に精算してもらったものです。',
                'reserved' => true,
                'order_reverse' => 1,
            ]
        );
        Master::updateOrCreate(
            ['name' => 'キャンセル'],
            [
                'description' => 'いったん予約した後にキャンセルしたもの、または予約しない事にしたものです。',
                'reserved' => false,
                'order_reverse' => 0,
            ]
        );
    }
}
