<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'nom' => 'sometimes|required|string|max:100',
            'solde' => 'sometimes|required|numeric|min:0',
            'devise' => 'sometimes|required|string|size:3',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du compte est obligatoire',
            'nom.max' => 'Le nom du compte ne peut pas dépasser 100 caractères',
            'solde.numeric' => 'Le solde doit être un nombre',
            'solde.min' => 'Le solde ne peut pas être négatif',
            'devise.size' => 'Le code de devise doit contenir exactement 3 caractères (ex: EUR, USD)',
        ];
    }
}