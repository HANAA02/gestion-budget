<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Income;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IncomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Income::where('utilisateur_id', Auth::id());
        
        // Filtrer par compte si spécifié
        if ($request->has('compte_id')) {
            $query->where('compte_id', $request->compte_id);
        }
        
        // Filtrer par date si spécifiée
        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('date_perception', [$request->date_debut, $request->date_fin]);
        }
        
        $incomes = $query->orderBy('date_perception', 'desc')->get();
        
        return response()->json($incomes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'compte_id' => 'required|exists:accounts,id,utilisateur_id,' . Auth::id(),
            'source' => 'required|string|max:100',
            'montant' => 'required|numeric|min:0',
            'date_perception' => 'required|date',
            'periodicite' => 'sometimes|string|in:unique,quotidien,hebdomadaire,mensuel,trimestriel,annuel',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Créer le revenu
            $income = Income::create([
                'utilisateur_id' => Auth::id(),
                'compte_id' => $request->compte_id,
                'source' => $request->source,
                'montant' => $request->montant,
                'date_perception' => $request->date_perception,
                'periodicite' => $request->periodicite ?? 'mensuel',
            ]);

            // Mettre à jour le solde du compte
            $account = Account::findOrFail($request->compte_id);
            $account->solde += $request->montant;
            $account->save();

            DB::commit();

            return response()->json([
                'message' => 'Revenu ajouté avec succès',
                'income' => $income
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erreur lors de l\'ajout du revenu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $income = Income::where('utilisateur_id', Auth::id())
                        ->findOrFail($id);
        
        return response()->json($income);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $income = Income::where('utilisateur_id', Auth::id())
                        ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'compte_id' => 'sometimes|required|exists:accounts,id,utilisateur_id,' . Auth::id(),
            'source' => 'sometimes|required|string|max:100',
            'montant' => 'sometimes|required|numeric|min:0',
            'date_perception' => 'sometimes|required|date',
            'periodicite' => 'sometimes|string|in:unique,quotidien,hebdomadaire,mensuel,trimestriel,annuel',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Récupérer le montant avant modification
            $oldAmount = $income->montant;
            $oldAccountId = $income->compte_id;
            
            // Mettre à jour le revenu
            if ($request->has('compte_id')) {
                $income->compte_id = $request->compte_id;
            }
            
            if ($request->has('source')) {
                $income->source = $request->source;
            }
            
            if ($request->has('montant')) {
                $income->montant = $request->montant;
            }
            
            if ($request->has('date_perception')) {
                $income->date_perception = $request->date_perception;
            }
            
            if ($request->has('periodicite')) {
                $income->periodicite = $request->periodicite;
            }
            
            $income->save();

            // Mettre à jour les soldes des comptes concernés
            if ($request->has('montant') || ($request->has('compte_id') && $oldAccountId != $request->compte_id)) {
                // Si le compte a changé, ajuster les deux comptes
                if ($request->has('compte_id') && $oldAccountId != $request->compte_id) {
                    // Retirer de l'ancien compte
                    $oldAccount = Account::findOrFail($oldAccountId);
                    $oldAccount->solde -= $oldAmount;
                    $oldAccount->save();
                    
                    // Ajouter au nouveau compte
                    $newAccount = Account::findOrFail($request->compte_id);
                    $newAccount->solde += $request->montant ?? $oldAmount;
                    $newAccount->save();
                } else {
                    // Sinon, juste ajuster le montant dans le compte actuel
                    $account = Account::findOrFail($income->compte_id);
                    $account->solde = $account->solde - $oldAmount + ($request->montant ?? $oldAmount);
                    $account->save();
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Revenu mis à jour avec succès',
                'income' => $income
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du revenu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $income = Income::where('utilisateur_id', Auth::id())
                        ->findOrFail($id);

        DB::beginTransaction();

        try {
            // Récupérer le montant avant suppression
            $amount = $income->montant;
            $accountId = $income->compte_id;
            
            // Supprimer le revenu
            $income->delete();

            // Mettre à jour le solde du compte
            $account = Account::findOrFail($accountId);
            $account->solde -= $amount;
            $account->save();

            DB::commit();

            return response()->json([
                'message' => 'Revenu supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erreur lors de la suppression du revenu',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}