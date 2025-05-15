<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'nom' => 'Logement',
                'description' => 'Loyer, hypothèque, charges, etc.',
                'icone' => 'home',
                'pourcentage_defaut' => 30.00,
            ],
            [
                'nom' => 'Alimentation',
                'description' => 'Courses, restaurants, etc.',
                'icone' => 'food',
                'pourcentage_defaut' => 15.00,
            ],
            [
                'nom' => 'Transport',
                'description' => 'Carburant, transports en commun, etc.',
                'icone' => 'transport',
                'pourcentage_defaut' => 10.00,
            ],
            [
                'nom' => 'Santé',
                'description' => 'Médicaments, consultations, etc.',
                'icone' => 'health',
                'pourcentage_defaut' => 5.00,
            ],
            [
                'nom' => 'Loisirs',
                'description' => 'Sorties, activités, etc.',
                'icone' => 'leisure',
                'pourcentage_defaut' => 10.00,
            ],
            [
                'nom' => 'Épargne',
                'description' => 'Économies, investissements, etc.',
                'icone' => 'savings',
                'pourcentage_defaut' => 20.00,
            ],
            [
                'nom' => 'Divers',
                'description' => 'Autres dépenses',
                'icone' => 'misc',
                'pourcentage_defaut' => 10.00,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}