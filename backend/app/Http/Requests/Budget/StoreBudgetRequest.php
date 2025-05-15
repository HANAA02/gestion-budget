<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetRequest extends FormRequest
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
            'nom' => 'required|string|max:100',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'montant_total' => 'required|numeric|min:0',
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.pourcentage' => 'required|numeric|min:0',
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
            'nom.required' => 'Le nom du budget est obligatoire',
            'nom.max' => 'Le nom du budget ne peut pas dépasser 100 caractères',
            'date_debut.required' => 'La date de début est obligatoire',
            'date_debut.date' => 'La date de début doit être une date valide',
            'date_fin.required' => 'La date de fin est obligatoire',
            'date_fin.date' => 'La date de fin doit être une date valide',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début',
            'montant_total.required' => 'Le montant total est obligatoire',
            'montant_total.numeric' => 'Le montant total doit être un nombre',
            'montant_total.min' => 'Le montant total ne peut pas être négatif',
            'categories.required' => 'Les catégories sont obligatoires',
            'categories.array' => 'Les catégories doivent être un tableau',
            'categories.*.id.required' => 'L\'identifiant de la catégorie est obligatoire',
            'categories.*.id.exists' => 'Une des catégories sélectionnées n\'existe pas',
            'categories.*.pourcentage.required' => 'Le pourcentage pour chaque catégorie est obligatoire',
            'categories.*.pourcentage.numeric' => 'Le pourcentage doit être un nombre',
            'categories.*.pourcentage.min' => 'Le pourcentage ne peut pas être négatif',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Vérifier que la somme des pourcentages est égale à 100
            if ($this->has('categories')) {
                $totalPercentage = array_sum(array_column($this->categories, 'pourcentage'));
                if (abs($totalPercentage - 100) > 0.01) {
                    $validator->errors()->add('categories', 'La somme des pourcentages doit être égale à 100%');
                }
            }
        });
    }
}