<?php

namespace App\Http\Controllers;

use App\Http\Requests\Objectif\StoreObjectifRequest;
use App\Http\Requests\Objectif\UpdateObjectifRequest;
use App\Models\Objectif;
use App\Repositories\ObjectifRepository;
use App\Services\StatistiqueService;
use Illuminate\Http\Request;

class ObjectifController extends Controller
{
    protected $objectifRepository;
    protected $statistiqueService;

    public function __construct(ObjectifRepository $objectifRepository, StatistiqueService $statistiqueService)
    {
        $this->objectifRepository = $objectifRepository;
        $this->statistiqueService = $statistiqueService;
        $this->middleware('auth:sanctum');
        $this->middleware('check.user.ownership')->except(['index', 'store']);
    }

    /**
     * Récupère la liste des objectifs de l'utilisateur.
     */
    public function index(Request $request)
    {
        $filtres = [
            'statut' => $request->input('statut'),
            'categorie_id' => $request->input('categorie_id')
        ];
        
        $objectifs = $this->objectifRepository->getAllForUser(
            $request->user()->id,
            $filtres
        );
        
        return response()->json($objectifs);
    }

    /**
     * Crée un nouvel objectif.
     */
    public function store(StoreObjectifRequest $request)
    {
        $data = $request->validated();
        $data['utilisateur_id'] = $request->user()->id;
        $data['statut'] = 'en cours';
        
        $objectif = $this->objectifRepository->create($data);
        
        return response()->json($objectif, 201);
    }

    /**
     * Affiche un objectif spécifique avec sa progression.
     */
    public function show(Objectif $objectif)
    {
        $objectif->load('categorie');
        
        // Calculer la progression actuelle
        $progression = $this->statistiqueService->calculerProgressionObjectif($objectif);
        $objectif->progression = $progression;
        
        return response()->json($objectif);
    }

    /**
     * Met à jour un objectif existant.
     */
    public function update(UpdateObjectifRequest $request, Objectif $objectif)
    {
        $data = $request->validated();
        
        // Si l'objectif est déjà terminé, ne pas permettre de le modifier
        if ($objectif->statut === 'atteint' || $objectif->statut === 'échoué') {
            return response()->json([
                'message' => 'Impossible de modifier un objectif déjà terminé'
            ], 400);
        }
        
        $objectif = $this->objectifRepository->update($objectif, $data);
        
        // Recalculer le statut après la mise à jour
        $this->statistiqueService->mettreAJourStatutObjectif($objectif);
        
        return response()->json($objectif);
    }

    /**
     * Supprime un objectif existant.
     */
    public function destroy(Objectif $objectif)
    {
        $this->objectifRepository->delete($objectif);
        
        return response()->json(null, 204);
    }

    /**
     * Récupère la progression de tous les objectifs actifs de l'utilisateur.
     */
    public function progression(Request $request)
    {
        $objectifs = $this->objectifRepository->getAllForUser(
            $request->user()->id,
            ['statut' => 'en cours']
        );
        
        foreach ($objectifs as $objectif) {
            $objectif->progression = $this->statistiqueService->calculerProgressionObjectif($objectif);
        }
        
        return response()->json($objectifs);
    }

    /**
     * Marque un objectif comme atteint manuellement.
     */
    public function marquerCommeAtteint(Objectif $objectif)
    {
        if ($objectif->statut !== 'en cours') {
            return response()->json([
                'message' => 'Cet objectif n\'est pas en cours'
            ], 400);
        }
        
        $objectif->statut = 'atteint';
        $objectif->save();
        
        return response()->json($objectif);
    }

    /**
     * Marque un objectif comme abandonné.
     */
    public function abandonner(Objectif $objectif)
    {
        if ($objectif->statut !== 'en cours') {
            return response()->json([
                'message' => 'Cet objectif n\'est pas en cours'
            ], 400);
        }
        
        $objectif->statut = 'abandonné';
        $objectif->save();
        
        return response()->json($objectif);
    }
}