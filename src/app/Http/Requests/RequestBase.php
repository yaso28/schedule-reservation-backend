<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class RequestBase extends FormRequest
{
    public function getUniqueRule($table, $column)
    {
        $ignore = '';
        $parameters = $this->route()->parameters;
        if (array_key_exists('id', $parameters)) {
            $ignore = ",{$parameters['id']}";
        }

        return "unique:{$table},{$column}{$ignore}";
    }
}
