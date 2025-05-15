<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Account;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un utilisateur administrateur
        $admin = User::create([
            'nom' => 'Admin',
            'prenom' => 'Système',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin1234'),
            'role' => 'admin', // Définir comme admin
            'email_verified_at' => now(),
        ]);

        // Créer un compte pour l'administrateur
        Account::create([
            'utilisateur_id' => $admin->id,
            'nom' => 'Compte principal',
            'solde' => 10000.00,
            'devise' => 'EUR',
        ]);

        // Créer un utilisateur de démonstration
        $user = User::create([
            'nom' => 'Utilisateur',
            'prenom' => 'Démo',
            'email' => 'demo@example.com',
            'password' => Hash::make('password'),
            'role' => 'user', // Définir comme utilisateur normal
            'email_verified_at' => now(),
        ]);

        // Créer un compte pour cet utilisateur
        Account::create([
            'utilisateur_id' => $user->id,
            'nom' => 'Compte principal',
            'solde' => 5000.00,
            'devise' => 'EUR',
        ]);

        // Créer des utilisateurs de test supplémentaires si l'environnement est local ou de test
        if (app()->environment(['local', 'testing'])) {
            // Créer un deuxième administrateur
            $anotherAdmin = User::create([
                'nom' => 'Gestionnaire',
                'prenom' => 'Finance',
                'email' => 'manager@example.com',
                'password' => Hash::make('manager1234'),
                'role' => 'admin', // Définir comme admin
                'email_verified_at' => now(),
            ]);

            Account::create([
                'utilisateur_id' => $anotherAdmin->id,
                'nom' => 'Compte courant',
                'solde' => 15000.00,
                'devise' => 'EUR',
            ]);

            // Créer 5 utilisateurs normaux supplémentaires avec des comptes
            \App\Models\User::factory(5)->create([
                'role' => 'user' // S'assurer que tous sont des utilisateurs normaux
            ])->each(function ($user) {
                $user->accounts()->saveMany([
                    Account::factory()->make([
                        'nom' => 'Compte courant',
                        'devise' => 'EUR',
                    ]),
                    Account::factory()->make([
                        'nom' => 'Compte épargne',
                        'devise' => 'EUR',
                    ]),
                ]);
            });
        }
    }
}