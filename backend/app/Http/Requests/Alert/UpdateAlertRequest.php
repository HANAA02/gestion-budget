<?php

namespace App\Http\Requests\Alert;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Alert;
use Illuminate\Support\Facades\Auth;

class UpdateAlertRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $alert = Alert::with('categoryBudget.budget')->find($this->route('alert'));
        return $alert && $alert->categoryBudget->budget->utilisateur_id === Auth::id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'sometimes|required|string|in:pourcentage,montant,reste',
            'seuil' => 'sometimes|required|numeric|min:0',
            'active' => 'sometimes|required|boolean',
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
            'type.in' => 'Le type doit être l\'une des valeurs suivantes : pourcentage, montant, reste',
            'seuil.numeric' => 'Le seuil doit être un nombre',
            'seuil.min' => 'Le seuil ne peut pas être négatif',
            'active.boolean' => 'Le statut d\'activation doit être vrai ou faux',
        ];
    }
}