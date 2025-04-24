<?php

namespace App\Http\Requests\Auth\Registration;

use App\Rules\ValidCpf;
use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class DriverRegistrationRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|max:50',
            'last_name' => 'max:50',
            'email' => 'required|email|max:150',
            'password' => 'sometimes|required|min:8',
            // 'uuid' => 'required|uuid|exists:mobile_otp_verifications,id,verified,1',
            'mobile' => 'required',
            'country'=>'required|exists:countries,dial_code',
            'device_token'=>'required',
            'login_by'=>'required|in:android,ios',
            'vehicle_type'=>'sometimes|required|exists:vehicle_types,id',
            'address'=>'min:15',
            'postal_code'=>'min:6|max:6',
            // 'car_make'=>'sometimes|required|exists:car_makes,id',
            // 'car_model'=>'sometimes|required|exists:car_models,id',
            'car_color'=>'sometimes|required',
            'car_number'=>'sometimes|required',
            'is_company_driver'=>'sometimes|required|boolean',
            'service_location_id'=>'required', //|exists,service_locations,id
            'cpf' => ['required', 'string', new ValidCpf, Rule::unique('drivers', 'cpf')],
            'data_nascimento' => ['required', 'date', 'before_or_equal:' . Carbon::now()->subYears(18)->format('Y-m-d')]
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'cpf.required' => 'O CPF é obrigatório.',
            'cpf.unique' => 'Este CPF já está sendo utilizado por outro motorista.',
            'data_nascimento.required' => 'A data de nascimento é obrigatória.',
            'data_nascimento.date' => 'A data de nascimento deve ser uma data válida.',
            'data_nascimento.before_or_equal' => 'O motorista deve ter pelo menos 18 anos de idade.'
        ];
    }
}
