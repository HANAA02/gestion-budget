<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GoalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Goal::where('utilisateur_id', Auth::id());
        
        // Filtrer par catégorie si spécifiée
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // Filtrer par statut si spécifié
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }
        
        $goals = $query->with('category')
                      ->orderBy('date_fin')
                      ->get();
        
        return response()->json($goals);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'titre' => 'required|string|max:100',
            'description' => 'nullable|string',
            'montant_cible' => 'required|numeric|min:0',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'statut' => 'sometimes|string|in:en cours,atteint,abandonné',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $goal = Goal::create([
            'utilisateur_id' => Auth::id(),
            'category_id' => $request->category_id,
            'titre' => $request->titre,
            'description' => $request->description,
            'montant_cible' => $request->montant_cible,
            'date_debut' => $request->date_debut,
            'date_fin' => $request->date_fin,
            'statut' => $request->statut ?? 'en cours',
        ]);

        // Charger la relation pour la réponse
        $goal->load('category');

        return response()->json([
            'message' => 'Objectif créé avec succès',
            'goal' => $goal
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $goal = Goal::where('utilisateur_id', Auth::id())
                   ->with('category')
                   ->findOrFail($id);
        
        return response()->json($goal);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $goal = Goal::where('utilisateur_id', Auth::id())
                   ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|required|exists:categories,id',
            'titre' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'montant_cible' => 'sometimes|required|numeric|min:0',
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'sometimes|required|date|after:date_debut',
            'statut' => 'sometimes|required|string|in:en cours,atteint,abandonné',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('category_id')) {
            $goal->category_id = $request->category_id;
        }
        
        if ($request->has('titre')) {
            $goal->titre = $request->titre;
        }
        
        if ($request->has('description')) {
            $goal->description = $request->description;
        }
        
        if ($request->has('montant_cible')) {
            $goal->montant_cible = $request->montant_cible;
        }
        
        if ($request->has('date_debut')) {
            $goal->date_debut = $request->date_debut;
        }
        
        if ($request->has('date_fin')) {
            $goal->date_fin = $request->date_fin;
        }
        
        if ($request->has('statut')) {
            $goal->statut = $request->statut;
        }

        $goal->save();

        // Charger la relation pour la réponse
        $goal->load('category');

        return response()->json([
            'message' => 'Objectif mis à jour avec succès',
            'goal' => $goal
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $goal = Goal::where('utilisateur_id', Auth::id())
                   ->findOrFail($id);
        
        $goal->delete();

        return response()->json([
            'message' => 'Objectif supprimé avec succès'
        ]);
    }
}