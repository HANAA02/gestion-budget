<?php

namespace App\Http\Requests\Goal;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Goal;
use Illuminate\Support\Facades\Auth;

class UpdateGoalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $goal = Goal::find($this->route('goal'));
        return $goal && $goal->utilisateur_id === Auth::id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => 'sometimes|required|exists:categories,id',
            'titre' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'montant_cible' => 'sometimes|required|numeric|min:0',
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'sometimes|required|date|after:date_debut',
            'statut' => 'sometimes|required|string|in:en cours,atteint,abandonné',
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
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas',
            'titre.max' => 'Le titre de l\'objectif ne peut pas dépasser 100 caractères',
            'montant_cible.numeric' => 'Le montant cible doit être un nombre',
            'montant_cible.min' => 'Le montant cible ne peut pas être négatif',
            'date_debut.date' => 'La date de début doit être une date valide',
            'date_fin.date' => 'La date de fin doit être une date valide',
            'date_fin.after' => 'La date de fin doit être postérieure à la date de début',
            'statut.in' => 'Le statut doit être l\'une des valeurs suivantes : en cours, atteint, abandonné',
        ];
    }
}