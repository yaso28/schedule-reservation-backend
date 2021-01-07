<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SettingService;
use App\Http\Resources\SettingResource;

class SettingController extends Controller
{
    protected $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function get($category, $key)
    {
        return $this->responseWithCode(new SettingResource(
            $this->settingService->get($category, $key)
        ));
    }

    public function update(Request $request, $category, $key)
    {
        $this->settingService->updateValue($category, $key, $request->input('value'));
        return $this->success();
    }
}
