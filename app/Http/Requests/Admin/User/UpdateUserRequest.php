<?php

namespace App\Http\Requests\Admin\User;

use App\Http\Requests\BaseRequest;

class UpdateUserRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'name' => 'required|max:50',
            'mobile' => 'required|mobile_number|min:8',
            'email' => 'required|email',
            'country' => 'required|exists:countries,id',
            'cpf' => 'required|regex:/^\d{11}$/|unique:users,cpf,' . $this->route('user')->id,
            'date_of_birth' => 'required|date|before:today',
        ];
    }
}