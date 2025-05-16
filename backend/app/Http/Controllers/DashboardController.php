<?php

namespace App\Http\Controllers;

use App\Services\BudgetService;
use App\Services\StatistiqueService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $budgetService;
    protected $statistiqueService;

    public function __construct(BudgetService $budgetService, StatistiqueService $statistiqueService)
    {
        $this->budgetService = $budgetService;
        $this->statistiqueService = $statistiqueService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Récupère les données pour le tableau de bord.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        
        // Récupère les budgets en cours
        $budgetActuel = $this->budgetService->getBudgetActuel($userId);
        
        // Récupère les statistiques globales
        $statistiques = $this->statistiqueService->getStatistiquesGlobales($userId);
        
        // Récupère les dépenses récentes
        $depensesRecentes = $this->statistiqueService->getDepensesRecentes($userId, 5);
        
        // Récupère l'avancement des objectifs
        $objectifs = $this->statistiqueService->getAvancementObjectifs($userId);
        
        return response()->json([
            'budget_actuel' => $budgetActuel,
            'statistiques' => $statistiques,
            'depenses_recentes' => $depensesRecentes,
            'objectifs' => $objectifs
        ]);
    }
    
    /**
     * Récupère les tendances de dépenses.
     */
    public function tendances(Request $request)
    {
        $userId = $request->user()->id;
        $periode = $request->input('periode', 6); // Par défaut 6 mois
        
        $tendances = $this->statistiqueService->getTendancesDepenses($userId, $periode);
        
        return response()->json($tendances);
    }
    
    /**
     * Récupère le résumé financier de l'utilisateur.
     */
    public function resume(Request $request)
    {
        $userId = $request->user()->id;
        
        $resume = $this->statistiqueService->getResumeMensuel($userId);
        
        return response()->json($resume);
    }
}