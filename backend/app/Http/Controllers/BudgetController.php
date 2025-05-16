<?php

namespace App\Http\Controllers;

use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Http\Requests\Budget\UpdateBudgetRequest;
use App\Models\Budget;
use App\Repositories\BudgetRepository;
use App\Services\BudgetService;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    protected $budgetRepository;
    protected $budgetService;

    public function __construct(BudgetRepository $budgetRepository, BudgetService $budgetService)
    {
        $this->budgetRepository = $budgetRepository;
        $this->budgetService = $budgetService;
        $this->middleware('auth:sanctum');
        $this->middleware('check.user.ownership')->except(['index', 'store']);
    }

    /**
     * Récupère la liste des budgets de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $budgets = $this->budgetRepository->getAllForUser($request->user()->id);
        return response()->json($budgets);
    }

    /**
     * Stocke un nouveau budget en base de données.
     */
    public function store(StoreBudgetRequest $request)
    {
        $data = $request->validated();
        $data['utilisateur_id'] = $request->user()->id;
        
        $budget = $this->budgetService->creerBudget(
            $data,
            $request->input('categories', [])
        );
        
        return response()->json($budget, 201);
    }

    /**
     * Affiche un budget spécifique avec ses catégories.
     */
    public function show(Budget $budget)
    {
        $budget->load('categories');
        return response()->json($budget);
    }

    /**
     * Met à jour un budget existant.
     */
    public function update(UpdateBudgetRequest $request, Budget $budget)
    {
        $budget = $this->budgetService->mettreAJourBudget(
            $budget,
            $request->validated(),
            $request->input('categories', [])
        );
        
        return response()->json($budget);
    }

    /**
     * Supprime un budget existant.
     */
    public function destroy(Budget $budget)
    {
        $this->budgetRepository->delete($budget);
        
        return response()->json(null, 204);
    }
    
    /**
     * Récupère les statistiques du budget.
     */
    public function statistiques(Budget $budget)
    {
        $stats = $this->budgetService->calculerStatistiques($budget);
        
        return response()->json($stats);
    }
}