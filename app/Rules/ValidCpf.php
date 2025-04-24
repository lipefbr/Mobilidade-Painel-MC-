<?php

namespace App\Rules;

use App\Helpers\CpfValidator;
use Illuminate\Contracts\Validation\Rule;

class ValidCpf implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return CpfValidator::validate($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'O :attribute não é um CPF válido.';
    }
}
