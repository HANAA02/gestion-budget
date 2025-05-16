<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
        $this->middleware('auth:sanctum');
        $this->middleware('check.admin')->except(['show', 'update', 'updatePassword']);
    }

    /**
     * Récupère la liste des utilisateurs (admin seulement).
     */
    public function index(Request $request)
    {
        $filtres = [
            'tri' => $request->input('tri', 'created_at'),
            'ordre' => $request->input('ordre', 'desc'),
            'recherche' => $request->input('recherche'),
        ];
        
        $utilisateurs = $this->userRepository->paginate(
            $request->input('par_page', 15),
            $filtres
        );
        
        return response()->json($utilisateurs);
    }

    /**
     * Crée un nouvel utilisateur (admin seulement).
     */
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        
        $user = $this->userRepository->create($data);
        
        return response()->json($user, 201);
    }

    /**
     * Affiche un profil utilisateur.
     */
    public function show(Request $request, User $user = null)
    {
        // Si aucun utilisateur n'est spécifié ou si l'utilisateur n'est pas admin,
        // retourner le profil de l'utilisateur connecté
        if (!$user || !Auth::user()->est_admin) {
            $user = Auth::user();
        }
        
        // Charger les informations supplémentaires si c'est l'utilisateur connecté
        if ($user->id === Auth::id()) {
            $user->load('comptes');
        }
        
        return response()->json($user);
    }

    /**
     * Met à jour un profil utilisateur.
     */
    public function update(UpdateUserRequest $request, User $user = null)
    {
        // Si aucun utilisateur n'est spécifié ou si l'utilisateur n'est pas admin,
        // mettre à jour le profil de l'utilisateur connecté
        if (!$user || !Auth::user()->est_admin) {
            $user = Auth::user();
        }
        
        $data = $request->validated();
        
        // Seul l'admin peut modifier le statut est_admin
        if (isset($data['est_admin']) && !Auth::user()->est_admin) {
            unset($data['est_admin']);
        }
        
        $user = $this->userRepository->update($user, $data);
        
        return response()->json($user);
    }

    /**
     * Supprime un utilisateur (admin seulement).
     */
    public function destroy(User $user)
    {
        // Empêcher la suppression de son propre compte
        if ($user->id === Auth::id()) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre compte'
            ], 400);
        }
        
        $this->userRepository->delete($user);
        
        return response()->json(null, 204);
    }

    /**
     * Met à jour le mot de passe de l'utilisateur.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        $user = Auth::user();
        
        // Vérifier le mot de passe actuel
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect'
            ], 400);
        }
        
        $user->password = Hash::make($request->password);
        $user->save();
        
        return response()->json([
            'message' => 'Mot de passe mis à jour avec succès'
        ]);
    }

    /**
     * Met à jour les préférences de notification.
     */
    public function updateNotificationPreferences(Request $request)
    {
        $request->validate([
            'preferences_notification' => 'required|array',
        ]);
        
        $user = Auth::user();
        $user->preferences_notification = $request->preferences_notification;
        $user->save();
        
        return response()->json([
            'message' => 'Préférences de notification mises à jour avec succès',
            'preferences' => $user->preferences_notification
        ]);
    }

    /**
     * Met à jour les préférences d'interface.
     */
    public function updateInterfacePreferences(Request $request)
    {
        $request->validate([
            'preferences_interface' => 'required|array',
        ]);
        
        $user = Auth::user();
        $user->preferences_interface = $request->preferences_interface;
        $user->save();
        
        return response()->json([
            'message' => 'Préférences d\'interface mises à jour avec succès',
            'preferences' => $user->preferences_interface
        ]);
    }
}