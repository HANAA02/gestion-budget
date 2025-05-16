<?php

namespace App\Http\Controllers;

use App\Models\CategorieBudget;
use App\Models\Budget;
use App\Models\Categorie;
use App\Repositories\CategorieBudgetRepository;
use App\Services\BudgetService;
use Illuminate\Http\Request;

class CategorieBudgetController extends Controller
{
    protected $categorieBudgetRepository;
    protected $budgetService;

    public function __construct(CategorieBudgetRepository $categorieBudgetRepository, BudgetService $budgetService)
    {
        $this->categorieBudgetRepository = $categorieBudgetRepository;
        $this->budgetService = $budgetService;
        $this->middleware('auth:sanctum');
        $this->middleware('check.user.ownership')->except(['index', 'store']);
    }

    /**
     * Récupère les catégories associées à un budget spécifique.
     */
    public function index(Request $request)
    {
        $budgetId = $request->input('budget_id');
        
        if (!$budgetId) {
            return response()->json(['message' => 'budget_id requis'], 400);
        }
        
        // Vérifier que le budget appartient à l'utilisateur
        $budget = Budget::findOrFail($budgetId);
        if ($budget->utilisateur_id != $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        $categoriesBudget = $this->categorieBudgetRepository->getAllForBudget($budgetId);
        
        return response()->json($categoriesBudget);
    }

    /**
     * Associe une catégorie à un budget avec un montant alloué.
     */
    public function store(Request $request)
    {
        $request->validate([
            'budget_id' => 'required|exists:budgets,id',
            'categorie_id' => 'required|exists:categories,id',
            'montant_alloue' => 'required|numeric|min:0',
            'pourcentage' => 'required|numeric|min:0|max:100',
        ]);
        
        // Vérifier que le budget appartient à l'utilisateur
        $budget = Budget::findOrFail($request->budget_id);
        if ($budget->utilisateur_id != $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        // Vérifier que la catégorie existe
        $categorie = Categorie::findOrFail($request->categorie_id);
        
        // Créer ou mettre à jour la relation
        $categorieBudget = $this->budgetService->ajouterCategorieBudget(
            $budget->id,
            $categorie->id,
            $request->montant_alloue,
            $request->pourcentage
        );
        
        return response()->json($categorieBudget, 201);
    }

    /**
     * Affiche les détails d'une association spécifique.
     */
    public function show(CategorieBudget $categorieBudget)
    {
        $categorieBudget->load('categorie', 'budget', 'depenses');
        
        // Calculer les statistiques pour cette catégorie
        $stats = $this->budgetService->calculerStatistiquesCategorieBudget($categorieBudget);
        $categorieBudget->statistiques = $stats;
        
        return response()->json($categorieBudget);
    }

    /**
     * Met à jour l'allocation pour une catégorie dans un budget.
     */
    public function update(Request $request, CategorieBudget $categorieBudget)
    {
        $request->validate([
            'montant_alloue' => 'numeric|min:0',
            'pourcentage' => 'numeric|min:0|max:100',
        ]);
        
        $categorieBudget = $this->budgetService->mettreAJourCategorieBudget(
            $categorieBudget,
            $request->all()
        );
        
        return response()->json($categorieBudget);
    }

    /**
     * Supprime une association catégorie-budget.
     */
    public function destroy(CategorieBudget $categorieBudget)
    {
        // Vérifier s'il y a des dépenses associées
        if ($categorieBudget->depenses()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer cette catégorie car des dépenses y sont associées'
            ], 400);
        }
        
        $this->categorieBudgetRepository->delete($categorieBudget);
        
        return response()->json(null, 204);
    }

    /**
     * Rééquilibre les pourcentages de toutes les catégories d'un budget.
     */
    public function reequilibrer(Request $request)
    {
        $request->validate([
            'budget_id' => 'required|exists:budgets,id',
        ]);
        
        // Vérifier que le budget appartient à l'utilisateur
        $budget = Budget::findOrFail($request->budget_id);
        if ($budget->utilisateur_id != $request->user()->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        $this->budgetService->reequilibrerCategories($budget->id);
        
        return response()->json([
            'message' => 'Catégories rééquilibrées avec succès',
            'categories' => $this->categorieBudgetRepository->getAllForBudget($budget->id)
        ]);
    }
}