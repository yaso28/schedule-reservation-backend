<?php

namespace App\Http\Requests;

abstract class ReorderRequestBase extends RequestBase
{
    protected $model;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id_list' => "bail|required|array",
            'id_list.*' => "bail|distinct|exists:{$this->model},id",
        ];
    }

    public function values()
    {
        return $this->only([
            'id_list',
        ]);
    }
}
