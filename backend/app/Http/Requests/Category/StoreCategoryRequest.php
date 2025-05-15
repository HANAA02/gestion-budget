<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
            'nom' => 'required|string|max:100|unique:categories',
            'description' => 'nullable|string',
            'icone' => 'nullable|string|max:50',
            'pourcentage_defaut' => 'required|numeric|min:0|max:100',
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
            'nom.required' => 'Le nom de la catégorie est obligatoire',
            'nom.max' => 'Le nom de la catégorie ne peut pas dépasser 100 caractères',
            'nom.unique' => 'Une catégorie avec ce nom existe déjà',
            'icone.max' => 'Le nom de l\'icône ne peut pas dépasser 50 caractères',
            'pourcentage_defaut.required' => 'Le pourcentage par défaut est obligatoire',
            'pourcentage_defaut.numeric' => 'Le pourcentage par défaut doit être un nombre',
            'pourcentage_defaut.min' => 'Le pourcentage par défaut ne peut pas être négatif',
            'pourcentage_defaut.max' => 'Le pourcentage par défaut ne peut pas dépasser 100',
        ];
    }
}