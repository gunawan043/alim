<?php

namespace App\Http\Requests\Connect;

use Illuminate\Foundation\Http\FormRequest;

class ExchangeConnectHandoffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:200'],
            // Audience/scope are validated by the service so the contract can
            // return the dedicated 403 error codes (audience_mismatch/scope_mismatch).
            'audience' => ['required', 'string', 'max:32'],
            'scope' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:191'],
        ];
    }
}
