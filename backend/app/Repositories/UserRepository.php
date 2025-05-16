<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository extends BaseRepository
{
    /**
     * UserRepository constructor.
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Récupère un utilisateur par son email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Pagine les utilisateurs avec des filtres.
     *
     * @param int $perPage
     * @param array $filtres
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $filtres = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();
        
        // Tri
        $orderBy = $filtres['tri'] ?? 'created_at';
        $ordre = $filtres['ordre'] ?? 'desc';
        
        // Recherche
        if (isset($filtres['recherche'])) {
            $query->where(function($q) use ($filtres) {
                $q->where('nom', 'like', '%' . $filtres['recherche'] . '%')
                  ->orWhere('prenom', 'like', '%' . $filtres['recherche'] . '%')
                  ->orWhere('email', 'like', '%' . $filtres['recherche'] . '%');
            });
        }
        
        return $query->orderBy($orderBy, $ordre)->paginate($perPage);
    }

    /**
     * Récupère les utilisateurs actifs.
     *
     * @param int $joursActivite
     * @return Collection
     */
    public function getUtilisateursActifs(int $joursActivite = 30): Collection
    {
        return $this->model->whereNotNull('dernier_login')
            ->where('dernier_login', '>=', now()->subDays($joursActivite))
            ->get();
    }

    /**
     * Met à jour les préférences de notification d'un utilisateur.
     *
     * @param int $userId
     * @param array $preferences
     * @return User
     */
    public function updatePreferencesNotification(int $userId, array $preferences): User
    {
        $user = $this->findOrFail($userId);
        $user->preferences_notification = $preferences;
        $user->save();
        
        return $user;
    }

    /**
     * Met à jour les préférences d'interface d'un utilisateur.
     *
     * @param int $userId
     * @param array $preferences
     * @return User
     */
    public function updatePreferencesInterface(int $userId, array $preferences): User
    {
        $user = $this->findOrFail($userId);
        $user->preferences_interface = $preferences;
        $user->save();
        
        return $user;
    }
}