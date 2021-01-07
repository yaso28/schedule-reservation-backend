<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\Category;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->save(Category::RESERVATION, Setting::KEY_MAIL_TO, null, '練習予定を送信する宛先のメールアドレスです。');
        $this->save(Category::RESERVATION, Setting::KEY_MAIL_SUBJECT, '{month_name}の練習予定', 'メールの件名です。{month_name}は"yyyy年mm月"に置き換わります。');
        $this->save(Category::RESERVATION, Setting::KEY_MAIL_MESSAGE_BEGIN, "こんにちは。\n{month_name}の練習予定をご連絡します。", 'メール本文の最初に載せる文章です。{month_name}は"yyyy年mm月"に置き換わります。この文章のあとに予定が載ります。');
        $this->save(Category::RESERVATION, Setting::KEY_MAIL_MESSAGE_END, "以上です。\nよろしくお願いします。", 'メール本文の最後に載せる文章です。');
        $this->save(Category::RESERVATION_PUBLIC, Setting::KEY_NOTES, null, 'メール本文の途中（予定のあと）に載せる注意事項の文章です。また、公開している練習予定ページの注意事項にも同じ文章が載ります。');
    }

    protected function save($category, $key, $value, $description)
    {
        $where = [
            'category_name' => $category,
            'key_name' => $key,
        ];
        $query = Setting::query();
        foreach ($where as $column => $condition) {
            $query = $query->where($column, $condition);
        }
        if ($query->count()) {
            $query->update(['description' => $description]);
        } else {
            Setting::create(array_merge($where, [
                'value' => $value,
                'description' => $description
            ]));
        }
    }
}
