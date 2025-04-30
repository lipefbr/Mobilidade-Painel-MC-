<?php

namespace App\Http\Requests\Admin\User;

use App\Http\Requests\BaseRequest;

class CreateUserRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'name' => 'required|max:50',
            'mobile' => 'required|mobile_number|min:8',
            'email' => 'required|email',
            'country' => 'required|exists:countries,id',
            'password' => 'required|min:8|max:32|confirmed',
            'cpf' => 'required|unique:users,cpf|regex:/^\d{11}$/',
            'date_of_birth' => 'required|date|before:today',
        ];
    }
}