<?php

namespace App\Http\Requests\Alert;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\CategoryBudget;
use Illuminate\Support\Facades\Auth;

class StoreAlertRequest extends FormRequest
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
            'type' => 'required|string|in:pourcentage,montant,reste',
            'seuil' => 'required|numeric|min:0',
            'active' => 'sometimes|boolean',
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
            'type.required' => 'Le type d\'alerte est obligatoire',
            'type.in' => 'Le type doit être l\'une des valeurs suivantes : pourcentage, montant, reste',
            'seuil.required' => 'Le seuil est obligatoire',
            'seuil.numeric' => 'Le seuil doit être un nombre',
            'seuil.min' => 'Le seuil ne peut pas être négatif',
            'active.boolean' => 'Le statut d\'activation doit être vrai ou faux',
        ];
    }
}