<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateUserRequest extends FormRequest
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
            'prenom' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|string|email|max:100|unique:users,email,' . Auth::id(),
            'current_password' => 'sometimes|required_with:password',
            'password' => 'sometimes|required|string|min:8|confirmed',
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
            'nom.required' => 'Le nom est obligatoire',
            'nom.max' => 'Le nom ne peut pas dépasser 100 caractères',
            'prenom.required' => 'Le prénom est obligatoire',
            'prenom.max' => 'Le prénom ne peut pas dépasser 100 caractères',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être une adresse email valide',
            'email.unique' => 'Cet email est déjà utilisé',
            'current_password.required_with' => 'Le mot de passe actuel est obligatoire pour changer de mot de passe',
            'password.required' => 'Le nouveau mot de passe est obligatoire',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas',
        ];
    }
}