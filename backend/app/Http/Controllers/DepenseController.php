<?php

namespace App\Http\Controllers;

use App\Http\Requests\Depense\StoreDepenseRequest;
use App\Http\Requests\Depense\UpdateDepenseRequest;
use App\Models\Depense;
use App\Repositories\DepenseRepository;
use App\Services\BudgetService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class DepenseController extends Controller
{
    protected $depenseRepository;
    protected $budgetService;
    protected $notificationService;

    public function __construct(
        DepenseRepository $depenseRepository,
        BudgetService $budgetService,
        NotificationService $notificationService
    ) {
        $this->depenseRepository = $depenseRepository;
        $this->budgetService = $budgetService;
        $this->notificationService = $notificationService;
        $this->middleware('auth:sanctum');
        $this->middleware('check.user.ownership')->except(['index', 'store']);
    }

    /**
     * Récupère les dépenses de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $filtres = $request->all();
        $depenses = $this->depenseRepository->getAllForUser($request->user()->id, $filtres);
        
        return response()->json($depenses);
    }

    /**
     * Stocke une nouvelle dépense.
     */
    public function store(StoreDepenseRequest $request)
    {
        $data = $request->validated();
        
        // Vérifie que la catégorie de budget appartient à l'utilisateur
        $this->budgetService->verifierProprieteCategoriesBudget(
            $data['categorie_budget_id'],
            $request->user()->id
        );
        
        $depense = $this->depenseRepository->create($data);
        
        // Vérifie si des alertes doivent être déclenchées
        $this->notificationService->verifierAlertes($depense->categorie_budget_id);
        
        return response()->json($depense, 201);
    }

    /**
     * Affiche une dépense spécifique.
     */
    public function show(Depense $depense)
    {
        $depense->load('categorieBudget.categorie');
        return response()->json($depense);
    }

    /**
     * Met à jour une dépense existante.
     */
    public function update(UpdateDepenseRequest $request, Depense $depense)
    {
        $data = $request->validated();
        
        // Si on change la catégorie de budget, vérifier la propriété
        if (isset($data['categorie_budget_id']) && $data['categorie_budget_id'] != $depense->categorie_budget_id) {
            $this->budgetService->verifierProprieteCategoriesBudget(
                $data['categorie_budget_id'],
                $request->user()->id
            );
        }
        
        $depense = $this->depenseRepository->update($depense, $data);
        
        // Vérifier les alertes
        $this->notificationService->verifierAlertes($depense->categorie_budget_id);
        
        return response()->json($depense);
    }

    /**
     * Supprime une dépense existante.
     */
    public function destroy(Depense $depense)
    {
        $categorieBudgetId = $depense->categorie_budget_id;
        
        $this->depenseRepository->delete($depense);
        
        // Vérifier les alertes après suppression
        $this->notificationService->verifierAlertes($categorieBudgetId);
        
        return response()->json(null, 204);
    }
    
    /**
     * Récupère les dépenses par catégorie.
     */
    public function parCategorie(Request $request)
    {
        $data = $this->depenseRepository->getByCategorie(
            $request->user()->id,
            $request->input('date_debut'),
            $request->input('date_fin')
        );
        
        return response()->json($data);
    }
}