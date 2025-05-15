<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\CategoryBudget;
use Illuminate\Support\Facades\Auth;

class UpdateCategoryBudgetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $categoryBudget = CategoryBudget::with('budget')->find($this->route('category_budget'));
        return $categoryBudget && $categoryBudget->budget->utilisateur_id === Auth::id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'montant_alloue' => 'sometimes|required|numeric|min:0',
            'pourcentage' => 'sometimes|required|numeric|min:0|max:100',
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
            'montant_alloue.numeric' => 'Le montant alloué doit être un nombre',
            'montant_alloue.min' => 'Le montant alloué ne peut pas être négatif',
            'pourcentage.numeric' => 'Le pourcentage doit être un nombre',
            'pourcentage.min' => 'Le pourcentage ne peut pas être négatif',
            'pourcentage.max' => 'Le pourcentage ne peut pas dépasser 100',
        ];
    }
}