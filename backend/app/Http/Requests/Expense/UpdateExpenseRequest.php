<?php

namespace App\Http\Requests\Expense;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Expense;
use App\Models\CategoryBudget;
use Illuminate\Support\Facades\Auth;

class UpdateExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $expense = Expense::with('categoryBudget.budget')->find($this->route('expense'));
        
        if (!$expense) {
            return false;
        }
        
        // Vérifier que l'utilisateur est propriétaire du budget
        if ($expense->categoryBudget->budget->utilisateur_id !== Auth::id()) {
            return false;
        }
        
        // Si l'utilisateur veut changer la catégorie, vérifier qu'il est aussi propriétaire du nouveau budget
        if ($this->has('category_budget_id') && $this->category_budget_id != $expense->category_budget_id) {
            $newCategoryBudget = CategoryBudget::with('budget')->find($this->category_budget_id);
            
            if (!$newCategoryBudget || $newCategoryBudget->budget->utilisateur_id !== Auth::id()) {
                return false;
            }
        }
        
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
            'category_budget_id' => 'sometimes|required|exists:category_budget,id',
            'description' => 'sometimes|required|string|max:255',
            'montant' => 'sometimes|required|numeric|min:0',
            'date_depense' => 'sometimes|required|date',
            'statut' => 'sometimes|required|string|in:validée,en attente,annulée',
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
            'category_budget_id.exists' => 'La catégorie de budget sélectionnée n\'existe pas',
            'description.max' => 'La description ne peut pas dépasser 255 caractères',
            'montant.numeric' => 'Le montant doit être un nombre',
            'montant.min' => 'Le montant ne peut pas être négatif',
            'date_depense.date' => 'La date de la dépense doit être une date valide',
            'statut.in' => 'Le statut doit être l\'une des valeurs suivantes : validée, en attente, annulée',
        ];
    }
}