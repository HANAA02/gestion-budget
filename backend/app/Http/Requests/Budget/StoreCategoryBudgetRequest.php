<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Budget;

class StoreCategoryBudgetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->has('budget_id')) {
            $budget = Budget::find($this->budget_id);
            return $budget && $budget->utilisateur_id === Auth::id();
        }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'budget_id' => 'required|exists:budgets,id',
            'category_id' => 'required|exists:categories,id',
            'montant_alloue' => 'required|numeric|min:0',
            'pourcentage' => 'required|numeric|min:0|max:100',
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
            'budget_id.required' => 'Le budget est obligatoire',
            'budget_id.exists' => 'Le budget sélectionné n\'existe pas',
            'category_id.required' => 'La catégorie est obligatoire',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas',
            'montant_alloue.required' => 'Le montant alloué est obligatoire',
            'montant_alloue.numeric' => 'Le montant alloué doit être un nombre',
            'montant_alloue.min' => 'Le montant alloué ne peut pas être négatif',
            'pourcentage.required' => 'Le pourcentage est obligatoire',
            'pourcentage.numeric' => 'Le pourcentage doit être un nombre',
            'pourcentage.min' => 'Le pourcentage ne peut pas être négatif',
            'pourcentage.max' => 'Le pourcentage ne peut pas dépasser 100',
        ];
    }
}