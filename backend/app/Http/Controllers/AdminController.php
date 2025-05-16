<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\StatistiqueService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected $userRepository;
    protected $statistiqueService;

    public function __construct(UserRepository $userRepository, StatistiqueService $statistiqueService)
    {
        $this->userRepository = $userRepository;
        $this->statistiqueService = $statistiqueService;
        $this->middleware('auth:sanctum');
        $this->middleware('check.admin');
    }

    /**
     * Tableau de bord administrateur.
     */
    public function dashboard()
    {
        $stats = [
            'total_utilisateurs' => User::count(),
            'utilisateurs_actifs' => User::where('dernier_login', '>=', now()->subDays(30))->count(),
            'statistiques_systeme' => $this->statistiqueService->getStatistiquesSysteme(),
        ];
        
        return response()->json($stats);
    }
    
    /**
     * Liste des utilisateurs pour l'administrateur.
     */
    public function utilisateurs(Request $request)
    {
        $utilisateurs = $this->userRepository->paginate(
            $request->input('par_page', 15),
            $request->input('tri', 'created_at'),
            $request->input('ordre', 'desc')
        );
        
        return response()->json($utilisateurs);
    }
    
    /**
     * Modifie le statut d'un utilisateur (actif/inactif).
     */
    public function modifierStatutUtilisateur(Request $request, User $user)
    {
        $request->validate([
            'actif' => 'required|boolean',
        ]);
        
        $user->actif = $request->input('actif');
        $user->save();
        
        return response()->json($user);
    }
    
    /**
     * Réinitialise le mot de passe d'un utilisateur.
     */
    public function reinitialiserMotDePasse(User $user)
    {
        $newPassword = \Str::random(10);
        $user->password = \Hash::make($newPassword);
        $user->save();
        
        // Envoyer un email avec le nouveau mot de passe
        // ...
        
        return response()->json(['message' => 'Mot de passe réinitialisé avec succès']);
    }
}