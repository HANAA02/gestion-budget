<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accounts = Auth::user()->accounts()->get();
        
        return response()->json($accounts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100',
            'solde' => 'required|numeric|min:0',
            'devise' => 'required|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $account = Account::create([
            'utilisateur_id' => Auth::id(),
            'nom' => $request->nom,
            'solde' => $request->solde,
            'devise' => $request->devise,
        ]);

        return response()->json([
            'message' => 'Compte créé avec succès',
            'account' => $account
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $account = Account::where('utilisateur_id', Auth::id())
                          ->findOrFail($id);
        
        return response()->json($account);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $account = Account::where('utilisateur_id', Auth::id())
                          ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:100',
            'solde' => 'sometimes|required|numeric|min:0',
            'devise' => 'sometimes|required|string|size:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('nom')) {
            $account->nom = $request->nom;
        }
        
        if ($request->has('solde')) {
            $account->solde = $request->solde;
        }
        
        if ($request->has('devise')) {
            $account->devise = $request->devise;
        }

        $account->save();

        return response()->json([
            'message' => 'Compte mis à jour avec succès',
            'account' => $account
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $account = Account::where('utilisateur_id', Auth::id())
                          ->findOrFail($id);
        
        $account->delete();

        return response()->json([
            'message' => 'Compte supprimé avec succès'
        ]);
    }
}