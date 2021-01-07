<?php

namespace App\Repositories;

use App\Models\Setting;

class SettingRepository extends RepositoryBase
{
    public function selectSetting($categoryName, $keyName)
    {
        return $this->selectCategoryRecord(Setting::class, $categoryName, $keyName);
    }

    public function updateSetting($categoryName, $keyName, $attributes)
    {
        $this->updateCategoryRecord(Setting::class, $categoryName, $keyName, $attributes);
    }
}
