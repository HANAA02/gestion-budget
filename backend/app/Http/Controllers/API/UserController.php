<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Obtenir les informations de l'utilisateur connecté
     */
    public function show()
    {
        return response()->json(Auth::user());
    }

    /**
     * Mettre à jour les informations de l'utilisateur
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:100',
            'prenom' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|string|email|max:100|unique:users,email,' . $user->id,
            'current_password' => 'sometimes|required_with:password',
            'password' => 'sometimes|required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérification du mot de passe actuel
        if ($request->has('password') && $request->has('current_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Le mot de passe actuel est incorrect'
                ], 401);
            }
        }

        // Mise à jour des données
        if ($request->has('nom')) {
            $user->nom = $request->nom;
        }
        
        if ($request->has('prenom')) {
            $user->prenom = $request->prenom;
        }
        
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => $user
        ]);
    }
}