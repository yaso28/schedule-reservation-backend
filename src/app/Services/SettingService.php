<?php

namespace App\Services;

use App\Repositories\SettingRepository;

class SettingService
{
    protected $settingRepository;

    public function __construct(SettingRepository $settingRepository)
    {
        $this->settingRepository = $settingRepository;
    }

    public function get($categoryName, $keyName)
    {
        return $this->settingRepository->selectSetting($categoryName, $keyName);
    }

    public function updateValue($categoryName, $keyName, $value)
    {
        $this->settingRepository->updateSetting($categoryName, $keyName, ['value' => $value]);
    }
}
