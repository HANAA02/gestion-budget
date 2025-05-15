<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\CategoryBudget;
use Illuminate\Support\Facades\Auth;

class StoreExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if ($this->has('category_budget_id')) {
            $categoryBudget = CategoryBudget::with('budget')->find($this->category_budget_id);
            return $categoryBudget && $categoryBudget->budget->utilisateur_id === Auth::id();
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
            'category_budget_id' => 'required|exists:category_budget,id',
            'description' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0',
            'date_depense' => 'required|date',
            'statut' => 'sometimes|string|in:validée,en attente,annulée',
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
            'category_budget_id.required' => 'La catégorie de budget est obligatoire',
            'category_budget_id.exists' => 'La catégorie de budget sélectionnée n\'existe pas',
            'description.required' => 'La description est obligatoire',
            'description.max' => 'La description ne peut pas dépasser 255 caractères',
            'montant.required' => 'Le montant est obligatoire',
            'montant.numeric' => 'Le montant doit être un nombre',
            'montant.min' => 'Le montant ne peut pas être négatif',
            'date_depense.required' => 'La date de la dépense est obligatoire',
            'date_depense.date' => 'La date de la dépense doit être une date valide',
            'statut.in' => 'Le statut doit être l\'une des valeurs suivantes : validée, en attente, annulée',
        ];
    }
}