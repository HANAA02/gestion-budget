<?php

namespace App\Http\Controllers;

use App\Http\Requests\Revenu\StoreRevenuRequest;
use App\Http\Requests\Revenu\UpdateRevenuRequest;
use App\Models\Revenu;
use App\Repositories\RevenuRepository;
use App\Services\BudgetService;
use Illuminate\Http\Request;

class RevenuController extends Controller
{
    protected $revenuRepository;
    protected $budgetService;

    public function __construct(RevenuRepository $revenuRepository, BudgetService $budgetService)
    {
        $this->revenuRepository = $revenuRepository;
        $this->budgetService = $budgetService;
        $this->middleware('auth:sanctum');
        $this->middleware('check.user.ownership')->except(['index', 'store']);
    }

    /**
     * Récupère la liste des revenus de l'utilisateur.
     */
    public function index(Request $request)
    {
        $filtres = [
            'compte_id' => $request->input('compte_id'),
            'date_debut' => $request->input('date_debut'),
            'date_fin' => $request->input('date_fin'),
            'periodicite' => $request->input('periodicite'),
        ];
        
        $revenus = $this->revenuRepository->getAllForUser(
            $request->user()->id,
            $filtres
        );
        
        return response()->json($revenus);
    }

    /**
     * Crée un nouveau revenu.
     */
    public function store(StoreRevenuRequest $request)
    {
        $data = $request->validated();
        $data['utilisateur_id'] = $request->user()->id;
        
        // Vérifier que le compte appartient à l'utilisateur
        $this->budgetService->verifierProprieteCompte(
            $data['compte_id'],
            $request->user()->id
        );
        
        $revenu = $this->revenuRepository->create($data);
        
        // Si demandé, créer un budget automatiquement
        if ($request->input('creer_budget', false)) {
            $budget = $this->budgetService->creerBudgetAutomatique(
                $request->user()->id,
                $revenu->montant,
                $revenu->date_perception
            );
            
            $revenu->budget_id = $budget->id;
            $revenu->save();
        }
        
        return response()->json($revenu, 201);
    }

    /**
     * Affiche un revenu spécifique.
     */
    public function show(Revenu $revenu)
    {
        $revenu->load('compte');
        return response()->json($revenu);
    }

    /**
     * Met à jour un revenu existant.
     */
    public function update(UpdateRevenuRequest $request, Revenu $revenu)
    {
        $data = $request->validated();
        
        // Si on change le compte, vérifier la propriété
        if (isset($data['compte_id']) && $data['compte_id'] != $revenu->compte_id) {
            $this->budgetService->verifierProprieteCompte(
                $data['compte_id'],
                $request->user()->id
            );
        }
        
        $revenu = $this->revenuRepository->update($revenu, $data);
        
        return response()->json($revenu);
    }

    /**
     * Supprime un revenu existant.
     */
    public function destroy(Revenu $revenu)
    {
        $this->revenuRepository->delete($revenu);
        
        return response()->json(null, 204);
    }

    /**
     * Récupère les statistiques des revenus.
     */
    public function statistiques(Request $request)
    {
        $periode = $request->input('periode', 12); // en mois
        
        $stats = $this->revenuRepository->getStatistiques(
            $request->user()->id,
            $periode
        );
        
        return response()->json($stats);
    }

    /**
     * Récupère la prévision des revenus pour les prochains mois.
     */
    public function previsions(Request $request)
    {
        $mois = $request->input('mois', 3); // nombre de mois à prévoir
        
        $previsions = $this->revenuRepository->getPrevisions(
            $request->user()->id,
            $mois
        );
        
        return response()->json($previsions);
    }

    /**
     * Clone un revenu récurrent pour le mois suivant.
     */
    public function cloner(Revenu $revenu)
    {
        // Vérifier si le revenu est récurrent
        if ($revenu->periodicite !== 'mensuel' && $revenu->periodicite !== 'hebdomadaire') {
            return response()->json([
                'message' => 'Seuls les revenus récurrents peuvent être clonés'
            ], 400);
        }
        
        $nouveauRevenu = $this->revenuRepository->cloner($revenu);
        
        return response()->json($nouveauRevenu, 201);
    }
}