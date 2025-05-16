<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categorie\StoreCategorieRequest;
use App\Http\Requests\Categorie\UpdateCategorieRequest;
use App\Models\Categorie;
use App\Repositories\CategorieRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategorieController extends Controller
{
    protected $categorieRepository;

    public function __construct(CategorieRepository $categorieRepository)
    {
        $this->categorieRepository = $categorieRepository;
        $this->middleware('auth:sanctum');
        $this->middleware('check.admin')->only(['store', 'update', 'destroy']);
    }

    /**
     * Récupère la liste des catégories.
     */
    public function index(Request $request)
    {
        // Si un paramètre utilisateur_id est fourni et que l'utilisateur est admin,
        // récupérer les catégories personnalisées de cet utilisateur
        if ($request->has('utilisateur_id') && Auth::user()->est_admin) {
            $categories = $this->categorieRepository->getAllForUser($request->utilisateur_id);
        } else {
            // Sinon récupérer toutes les catégories (globales et personnalisées pour l'utilisateur courant)
            $categories = $this->categorieRepository->getAll($request->user()->id);
        }
        
        return response()->json($categories);
    }

    /**
     * Crée une nouvelle catégorie.
     */
    public function store(StoreCategorieRequest $request)
    {
        $data = $request->validated();
        
        // Si c'est l'administrateur qui crée la catégorie, elle sera globale
        // Sinon, elle sera liée à l'utilisateur courant
        if (!$request->user()->est_admin && !isset($data['est_global'])) {
            $data['est_global'] = false;
            $data['utilisateur_id'] = $request->user()->id;
        }
        
        $categorie = $this->categorieRepository->create($data);
        
        return response()->json($categorie, 201);
    }

    /**
     * Affiche une catégorie spécifique.
     */
    public function show(Categorie $categorie)
    {
        // Si la catégorie est personnalisée et n'appartient pas à l'utilisateur courant
        // et que l'utilisateur n'est pas admin, refuser l'accès
        if (!$categorie->est_global && 
            $categorie->utilisateur_id !== Auth::id() && 
            !Auth::user()->est_admin) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        return response()->json($categorie);
    }

    /**
     * Met à jour une catégorie existante.
     */
    public function update(UpdateCategorieRequest $request, Categorie $categorie)
    {
        // Seul l'admin peut modifier les catégories globales
        // Les utilisateurs ne peuvent modifier que leurs propres catégories
        if (($categorie->est_global && !Auth::user()->est_admin) || 
            (!$categorie->est_global && $categorie->utilisateur_id !== Auth::id() && !Auth::user()->est_admin)) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        $categorie = $this->categorieRepository->update($categorie, $request->validated());
        
        return response()->json($categorie);
    }

    /**
     * Supprime une catégorie existante.
     */
    public function destroy(Categorie $categorie)
    {
        // Seul l'admin peut supprimer les catégories globales
        // Les utilisateurs ne peuvent supprimer que leurs propres catégories
        if (($categorie->est_global && !Auth::user()->est_admin) || 
            (!$categorie->est_global && $categorie->utilisateur_id !== Auth::id() && !Auth::user()->est_admin)) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        
        // Vérifier si la catégorie est utilisée dans des budgets ou dépenses
        if ($this->categorieRepository->estUtilisee($categorie->id)) {
            return response()->json([
                'message' => 'Impossible de supprimer cette catégorie car elle est utilisée dans des budgets ou dépenses'
            ], 400);
        }
        
        $this->categorieRepository->delete($categorie);
        
        return response()->json(null, 204);
    }

    /**
     * Récupère les catégories les plus utilisées par l'utilisateur.
     */
    public function populaires(Request $request)
    {
        $limit = $request->input('limit', 5);
        
        $categories = $this->categorieRepository->getMostUsedByUser(
            $request->user()->id,
            $limit
        );
        
        return response()->json($categories);
    }

    /**
     * Récupère les statistiques d'utilisation des catégories pour l'utilisateur.
     */
    public function statistiques(Request $request)
    {
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');
        
        $stats = $this->categorieRepository->getStatistiquesUtilisation(
            $request->user()->id,
            $dateDebut,
            $dateFin
        );
        
        return response()->json($stats);
    }
}