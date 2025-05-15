<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CategoryBudget;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Expense::query()
                        ->join('category_budget', 'expenses.category_budget_id', '=', 'category_budget.id')
                        ->join('budgets', 'category_budget.budget_id', '=', 'budgets.id')
                        ->where('budgets.utilisateur_id', Auth::id());
        
        // Filtrer par budget si spécifié
        if ($request->has('budget_id')) {
            $query->where('category_budget.budget_id', $request->budget_id);
        }
        
        // Filtrer par catégorie si spécifiée
        if ($request->has('category_id')) {
            $query->where('category_budget.category_id', $request->category_id);
        }
        
        // Filtrer par date si spécifiée
        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('expenses.date_depense', [$request->date_debut, $request->date_fin]);
        }
        
        $expenses = $query->select('expenses.*')
                        ->with(['categoryBudget.budget', 'categoryBudget.category'])
                        ->orderBy('expenses.date_depense', 'desc')
                        ->get();
        
        return response()->json($expenses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_budget_id' => 'required|exists:category_budget,id',
            'description' => 'required|string|max:255',
            'montant' => 'required|numeric|min:0',
            'date_depense' => 'required|date',
            'statut' => 'sometimes|string|in:validée,en attente,annulée',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier que l'utilisateur est propriétaire du budget associé à cette allocation
        $categoryBudget = CategoryBudget::with('budget')->findOrFail($request->category_budget_id);
        if ($categoryBudget->budget->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à ajouter des dépenses à ce budget',
            ], 403);
        }

        $expense = Expense::create([
            'category_budget_id' => $request->category_budget_id,
            'description' => $request->description,
            'montant' => $request->montant,
            'date_depense' => $request->date_depense,
            'statut' => $request->statut ?? 'validée',
        ]);

        // Charger les relations pour la réponse
        $expense->load(['categoryBudget.budget', 'categoryBudget.category']);

        return response()->json([
            'message' => 'Dépense créée avec succès',
            'expense' => $expense
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $expense = Expense::with(['categoryBudget.budget', 'categoryBudget.category'])
                         ->findOrFail($id);
        
        // Vérifier que l'utilisateur est propriétaire du budget associé à cette dépense
        if ($expense->categoryBudget->budget->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à consulter cette dépense',
            ], 403);
        }
        
        return response()->json($expense);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $expense = Expense::findOrFail($id);
        
        // Vérifier que l'utilisateur est propriétaire du budget associé à cette dépense
        if ($expense->categoryBudget->budget->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à modifier cette dépense',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'category_budget_id' => 'sometimes|required|exists:category_budget,id',
            'description' => 'sometimes|required|string|max:255',
            'montant' => 'sometimes|required|numeric|min:0',
            'date_depense' => 'sometimes|required|date',
            'statut' => 'sometimes|required|string|in:validée,en attente,annulée',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Si catégorie changée, vérifier que l'utilisateur est aussi propriétaire du nouveau budget
        if ($request->has('category_budget_id') && $request->category_budget_id != $expense->category_budget_id) {
            $categoryBudget = CategoryBudget::with('budget')->findOrFail($request->category_budget_id);
            if ($categoryBudget->budget->utilisateur_id !== Auth::id()) {
                return response()->json([
                    'message' => 'Vous n\'êtes pas autorisé à associer cette dépense à ce budget',
                ], 403);
            }
        }

        if ($request->has('category_budget_id')) {
            $expense->category_budget_id = $request->category_budget_id;
        }
        
        if ($request->has('description')) {
            $expense->description = $request->description;
        }
        
        if ($request->has('montant')) {
            $expense->montant = $request->montant;
        }
        
        if ($request->has('date_depense')) {
            $expense->date_depense = $request->date_depense;
        }
        
        if ($request->has('statut')) {
            $expense->statut = $request->statut;
        }

        $expense->save();

        // Charger les relations pour la réponse
        $expense->load(['categoryBudget.budget', 'categoryBudget.category']);

        return response()->json([
            'message' => 'Dépense mise à jour avec succès',
            'expense' => $expense
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $expense = Expense::findOrFail($id);
        
        // Vérifier que l'utilisateur est propriétaire du budget associé à cette dépense
        if ($expense->categoryBudget->budget->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à supprimer cette dépense',
            ], 403);
        }
        
        $expense->delete();

        return response()->json([
            'message' => 'Dépense supprimée avec succès'
        ]);
    }

    /**
     * Get expenses by category budget.
     */
    public function getByCategory(string $categoryBudgetId)
    {
        $categoryBudget = CategoryBudget::with('budget')->findOrFail($categoryBudgetId);
        
        // Vérifier que l'utilisateur est propriétaire du budget
        if ($categoryBudget->budget->utilisateur_id !== Auth::id()) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à consulter ces dépenses',
            ], 403);
        }
        
        $expenses = Expense::where('category_budget_id', $categoryBudgetId)
                          ->orderBy('date_depense', 'desc')
                          ->get();
        
        return response()->json([
            'category_budget' => $categoryBudget,
            'expenses' => $expenses,
            'total_spent' => $expenses->sum('montant'),
            'remaining_amount' => $categoryBudget->montant_alloue - $expenses->sum('montant'),
        ]);
    }
}