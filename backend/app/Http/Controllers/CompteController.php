<?php

namespace App\Http\Controllers;

use App\Http\Requests\Compte\StoreCompteRequest;
use App\Http\Requests\Compte\UpdateCompteRequest;
use App\Models\Compte;
use App\Repositories\CompteRepository;
use Illuminate\Http\Request;

class CompteController extends Controller
{
    protected $compteRepository;

    public function __construct(CompteRepository $compteRepository)
    {
        $this->compteRepository = $compteRepository;
        $this->middleware('auth:sanctum');
        $this->middleware('check.user.ownership')->except(['index', 'store']);
    }

    /**
     * Récupère la liste des comptes de l'utilisateur connecté.
     */
    public function index(Request $request)
    {
        $comptes = $this->compteRepository->getAllForUser($request->user()->id);
        return response()->json($comptes);
    }

    /**
     * Stocke un nouveau compte en base de données.
     */
    public function store(StoreCompteRequest $request)
    {
        $data = $request->validated();
        $data['utilisateur_id'] = $request->user()->id;
        
        $compte = $this->compteRepository->create($data);
        
        return response()->json($compte, 201);
    }

    /**
     * Affiche un compte spécifique.
     */
    public function show(Compte $compte)
    {
        return response()->json($compte);
    }

    /**
     * Met à jour un compte existant.
     */
    public function update(UpdateCompteRequest $request, Compte $compte)
    {
        $compte = $this->compteRepository->update($compte, $request->validated());
        
        return response()->json($compte);
    }

    /**
     * Supprime un compte existant.
     */
    public function destroy(Compte $compte)
    {
        $this->compteRepository->delete($compte);
        
        return response()->json(null, 204);
    }
}