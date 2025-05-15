<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\CategoryBudget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CategoryBudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CategoryBudget::query()
                        ->join('budgets', 'category_budget.budget_id', '=', 'budgets.id')
                        ->where('budgets.utilisateur_id', Auth::id());
        
        // Filtrer par budget si spécifié
        if ($request->has('budget_id')) {
            $query->where('budget_id', $request->budget_id);
        }
        
        // Filtrer par catégorie si spécifiée
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        $categoryBudgets = $query->select('category_budget.*')
                                ->with(['budget', 'category', 'expenses'])
                                ->get();
        
        return response()->json($categoryBudgets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'budget_id' => 'required|exists:budgets,id',
            'category_id' => 'required|exists:categories,id',
            'montant_alloue' => 'required|numeric|min:0',
            'pourcentage' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier que l'utilisateur est propriétaire du budget
        $budget = Budget::findOrFail($request->budget_id);
        if ($budget->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à modifier ce budget',
            ], 403);
        }

        // Vérifier si la catégorie existe déjà pour ce budget
        $existingCategoryBudget = CategoryBudget::where('budget_id', $request->budget_id)
                                              ->where('category_id', $request->category_id)
                                              ->first();
        
        if ($existingCategoryBudget) {
            return response()->json([
                'message' => 'Cette catégorie est déjà associée à ce budget',
            ], 422);
        }

        $categoryBudget = CategoryBudget::create([
            'budget_id' => $request->budget_id,
            'category_id' => $request->category_id,
            'montant_alloue' => $request->montant_alloue,
            'pourcentage' => $request->pourcentage,
        ]);

        return response()->json([
            'message' => 'Allocation de budget créée avec succès',
            'category_budget' => $categoryBudget
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $categoryBudget = CategoryBudget::with(['budget', 'category', 'expenses'])
                                      ->findOrFail($id);
        
        // Vérifier que l'utilisateur est propriétaire du budget
        if ($categoryBudget->budget->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à consulter cette allocation',
            ], 403);
        }
        
        return response()->json($categoryBudget);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $categoryBudget = CategoryBudget::findOrFail($id);
        
        // Vérifier que l'utilisateur est propriétaire du budget
        if ($categoryBudget->budget->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à modifier cette allocation',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'montant_alloue' => 'sometimes|required|numeric|min:0',
            'pourcentage' => 'sometimes|required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('montant_alloue')) {
            $categoryBudget->montant_alloue = $request->montant_alloue;
        }
        
        if ($request->has('pourcentage')) {
            $categoryBudget->pourcentage = $request->pourcentage;
        }

        $categoryBudget->save();

        return response()->json([
            'message' => 'Allocation de budget mise à jour avec succès',
            'category_budget' => $categoryBudget
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $categoryBudget = CategoryBudget::findOrFail($id);
        
        // Vérifier que l'utilisateur est propriétaire du budget
        if ($categoryBudget->budget->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à supprimer cette allocation',
            ], 403);
        }
        
        // Vérifier si l'allocation a des dépenses
        if ($categoryBudget->expenses()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette allocation car elle contient des dépenses',
            ], 422);
        }
        
        $categoryBudget->delete();

        return response()->json([
            'message' => 'Allocation de budget supprimée avec succès'
        ]);
    }
}