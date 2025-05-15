<?php

namespace App\Http\Requests\Goal;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoalRequest extends FormRequest
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
            'category_id' => 'required|exists:categories,id',
            'titre' => 'required|string|max:100',
            'description' => 'nullable|string',
            'montant_cible' => 'required|numeric|min:0',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'statut' => 'sometimes|string|in:en cours,atteint,abandonné',
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
            'category_id.required' => 'La catégorie est obligatoire',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas',
            'titre.required' => 'Le titre de l\'objectif est obligatoire',
            'titre.max' => 'Le titre de l\'objectif ne peut pas dépasser 100 caractères',
            'montant_cible.required' => 'Le montant cible est obligatoire',
            'montant_cible.numeric' => 'Le montant cible doit être un nombre',
            'montant_cible.min' => 'Le montant cible ne peut pas être négatif',
            'date_debut.required' => 'La date de début est obligatoire',
            'date_debut.date' => 'La date de début doit être une date valide',
            'date_fin.required' => 'La date de fin est obligatoire',
            'date_fin.date' => 'La date de fin doit être une date valide',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début',
            'statut.in' => 'Le statut doit être l\'une des valeurs suivantes : en cours, atteint, abandonné',
        ];
    }
}