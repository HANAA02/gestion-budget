<?php

namespace App\Services;

use App\Models\Objectif;
use App\Repositories\BudgetRepository;
use App\Repositories\DepenseRepository;
use App\Repositories\ObjectifRepository;
use App\Repositories\RevenuRepository;
use App\Repositories\UserRepository;
use App\Repositories\CompteRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class StatistiqueService
{
    protected $budgetRepository;
    protected $depenseRepository;
    protected $objectifRepository;
    protected $revenuRepository;
    protected $userRepository;
    protected $compteRepository;

    /**
     * StatistiqueService constructor.
     *
     * @param BudgetRepository $budgetRepository
     * @param DepenseRepository $depenseRepository
     * @param ObjectifRepository $objectifRepository
     * @param RevenuRepository $revenuRepository
     * @param UserRepository $userRepository
     * @param CompteRepository $compteRepository
     */
    public function __construct(
        BudgetRepository $budgetRepository,
        DepenseRepository $depenseRepository,
        ObjectifRepository $objectifRepository,
        RevenuRepository $revenuRepository,
        UserRepository $userRepository,
        CompteRepository $compteRepository
    ) {
        $this->budgetRepository = $budgetRepository;
        $this->depenseRepository = $depenseRepository;
        $this->objectifRepository = $objectifRepository;
        $this->revenuRepository = $revenuRepository;
        $this->userRepository = $userRepository;
        $this->compteRepository = $compteRepository;
    }

    /**
     * Récupère les statistiques globales pour un utilisateur.
     *
     * @param int $userId
     * @return array
     */
    public function getStatistiquesGlobales(int $userId): array
    {
        $now = Carbon::now();
        $debutMois = $now->copy()->startOfMonth();
        $finMois = $now->copy()->endOfMonth();
        $debutAnnee = $now->copy()->startOfYear();
        $finAnnee = $now->copy()->endOfYear();
        
        // Statistiques mensuelles
        $depensesMois = $this->depenseRepository->getTotalForPeriod($userId, $debutMois, $finMois);
        $revenusMois = $this->revenuRepository->getTotalForPeriod($userId, $debutMois, $finMois);
        $soldeMois = $revenusMois - $depensesMois;
        
        // Statistiques annuelles
        $depensesAnnee = $this->depenseRepository->getTotalForPeriod($userId, $debutAnnee, $finAnnee);
        $revenusAnnee = $this->revenuRepository->getTotalForPeriod($userId, $debutAnnee, $finAnnee);
        $soldeAnnee = $revenusAnnee - $depensesAnnee;
        
        // Solde total des comptes
        $soldeTotal = $this->compteRepository->getSoldeTotalUtilisateur($userId);
        
        return [
            'mois' => [
                'depenses' => $depensesMois,
                'revenus' => $revenusMois,
                'solde' => $soldeMois,
                'periode' => $debutMois->format('Y-m')
            ],
            'annee' => [
                'depenses' => $depensesAnnee,
                'revenus' => $revenusAnnee,
                'solde' => $soldeAnnee,
                'periode' => $debutAnnee->format('Y')
            ],
            'global' => [
                'solde_total' => $soldeTotal
            ]
        ];
    }

    /**
     * Récupère les dépenses récentes pour un utilisateur.
     *
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getDepensesRecentes(int $userId, int $limit = 5): Collection
    {
        return $this->depenseRepository->getRecentesForUser($userId, $limit);
    }

    /**
     * Récupère l'avancement des objectifs d'un utilisateur.
     *
     * @param int $userId
     * @return array
     */
    public function getAvancementObjectifs(int $userId): array
    {
        $objectifs = $this->objectifRepository->getObjectifsEnCours($userId);
        $result = [];
        
        foreach ($objectifs as $objectif) {
            $result[] = [
                'id' => $objectif->id,
                'titre' => $objectif->titre,
                'categorie' => $objectif->categorie->nom,
                'montant_cible' => $objectif->montant_cible,
                'date_fin' => $objectif->date_fin,
                'progression' => $this->calculerProgressionObjectif($objectif)
            ];
        }
        
        return $result;
    }

    /**
     * Calcule la progression d'un objectif.
     *
     * @param Objectif $objectif
     * @return array
     */
    public function calculerProgressionObjectif(Objectif $objectif): array
    {
        $now = Carbon::now();
        $dateDebut = Carbon::parse($objectif->date_debut);
        $dateFin = Carbon::parse($objectif->date_fin);
        
        // Calculer le temps écoulé en pourcentage
        $dureeTotal = $dateDebut->diffInDays($dateFin);
        $joursEcoules = $dateDebut->diffInDays($now);
        $progressionTemps = $dureeTotal > 0 ? min(100, ($joursEcoules / $dureeTotal) * 100) : 100;
        
        // Calculer la progression financière
        $depensesCategorie = $this->getDepensesPourObjectif($objectif);
        $progressionMontant = $objectif->montant_cible > 0 ? min(100, ($depensesCategorie / $objectif->montant_cible) * 100) : 0;
        
        return [
            'temps' => $progressionTemps,
            'montant' => $progressionMontant,
            'montant_actuel' => $depensesCategorie,
            'jours_restants' => max(0, $now->diffInDays($dateFin))
        ];
    }

    /**
     * Met à jour le statut d'un objectif en fonction de sa progression.
     *
     * @param Objectif $objectif
     * @return Objectif
     */
    public function mettreAJourStatutObjectif(Objectif $objectif): Objectif
    {
        if ($objectif->statut !== 'en cours') {
            return $objectif;
        }
        
        $progression = $this->calculerProgressionObjectif($objectif);
        $now = Carbon::now();
        $dateFin = Carbon::parse($objectif->date_fin);
        
        if ($progression['montant'] >= 100) {
            $objectif->statut = 'atteint';
            $objectif->save();
        } else if ($now->isAfter($dateFin) && $progression['montant'] < 100) {
            $objectif->statut = 'échoué';
            $objectif->save();
        }
        
        return $objectif;
    }

    /**
     * Récupère les tendances des dépenses pour un utilisateur.
     *
     * @param int $userId
     * @param int $periode
     * @return array
     */
    public function getTendancesDepenses(int $userId, int $periode = 6): array
    {
        return $this->depenseRepository->getTendances($userId, $periode);
    }

    /**
     * Génère un rapport mensuel pour un utilisateur.
     *
     * @param int $userId
     * @param int $mois
     * @param int $annee
     * @return array
     */
    public function genererRapportMensuel(int $userId, int $mois, int $annee): array
    {
        $dateDebut = Carbon::createFromDate($annee, $mois, 1)->startOfMonth();
        $dateFin = $dateDebut->copy()->endOfMonth();
        
        // Récupérer les revenus et dépenses pour la période
        $revenus = $this->revenuRepository->getRevenusPourPeriode($userId, $dateDebut, $dateFin);
        $totalRevenus = $revenus->sum('montant');
        
        // Récupérer les dépenses par catégorie
        $depensesParCategorie = $this->depenseRepository->getByCategorie(
            $userId,
            $dateDebut->format('Y-m-d'),
            $dateFin->format('Y-m-d')
        );
        
        $totalDepenses = array_sum(array_column($depensesParCategorie, 'total'));
        
        // Récupérer le budget de la période
        $budget = $this->budgetRepository->getBudgetsPourPeriode(
            $userId,
            $dateDebut->format('Y-m-d'),
            $dateFin->format('Y-m-d')
        )->first();
        
        // Calculer les économies
        $economies = $totalRevenus - $totalDepenses;
        
        return [
            'periode' => [
                'mois' => $dateDebut->format('m'),
                'annee' => $dateDebut->format('Y'),
                'date_debut' => $dateDebut->format('Y-m-d'),
                'date_fin' => $dateFin->format('Y-m-d')
            ],
            'revenus' => [
                'total' => $totalRevenus,
                'detail' => $revenus->toArray()
            ],
            'depenses' => [
                'total' => $totalDepenses,
                'par_categorie' => $depensesParCategorie
            ],
            'budget' => $budget ? $budget->toArray() : null,
            'economies' => $economies,
            'taux_epargne' => $totalRevenus > 0 ? ($economies / $totalRevenus) * 100 : 0
        ];
    }

    /**
     * Génère un rapport annuel pour un utilisateur.
     *
     * @param int $userId
     * @param int $annee
     * @return array
     */
    public function genererRapportAnnuel(int $userId, int $annee): array
    {
        $rapportsMensuels = [];
        $totalRevenus = 0;
        $totalDepenses = 0;
        
        // Générer un rapport pour chaque mois de l'année
        for ($mois = 1; $mois <= 12; $mois++) {
            $rapport = $this->genererRapportMensuel($userId, $mois, $annee);
            $rapportsMensuels[] = $rapport;
            
            $totalRevenus += $rapport['revenus']['total'];
            $totalDepenses += $rapport['depenses']['total'];
        }
        
        // Calculer les économies annuelles
        $economies = $totalRevenus - $totalDepenses;
        
        return [
            'periode' => [
                'annee' => $annee,
                'date_debut' => Carbon::createFromDate($annee, 1, 1)->startOfYear()->format('Y-m-d'),
                'date_fin' => Carbon::createFromDate($annee, 12, 31)->endOfYear()->format('Y-m-d')
            ],
            'revenus' => [
                'total' => $totalRevenus,
                'detail_mensuel' => array_map(function($rapport) {
                    return [
                        'mois' => $rapport['periode']['mois'],
                        'total' => $rapport['revenus']['total']
                    ];
                }, $rapportsMensuels)
            ],
            'depenses' => [
                'total' => $totalDepenses,
                'detail_mensuel' => array_map(function($rapport) {
                    return [
                        'mois' => $rapport['periode']['mois'],
                        'total' => $rapport['depenses']['total']
                    ];
                }, $rapportsMensuels)
            ],
            'economies' => $economies,
            'taux_epargne' => $totalRevenus > 0 ? ($economies / $totalRevenus) * 100 : 0,
            'rapports_mensuels' => $rapportsMensuels
        ];
    }

    /**
     * Génère un rapport comparatif entre deux périodes.
     *
     * @param int $userId
     * @param string $periode1Debut
     * @param string $periode1Fin
     * @param string $periode2Debut
     * @param string $periode2Fin
     * @return array
     */
    public function genererRapportComparatif(
        int $userId,
        string $periode1Debut,
        string $periode1Fin,
        string $periode2Debut,
        string $periode2Fin
    ): array {
        // Période 1
        $revenus1 = $this->revenuRepository->getTotalForPeriod($userId, $periode1Debut, $periode1Fin);
        $depenses1 = $this->depenseRepository->getTotalForPeriod($userId, $periode1Debut, $periode1Fin);
        $depensesParCategorie1 = $this->depenseRepository->getByCategorie($userId, $periode1Debut, $periode1Fin);
        
        // Période 2
        $revenus2 = $this->revenuRepository->getTotalForPeriod($userId, $periode2Debut, $periode2Fin);
        $depenses2 = $this->depenseRepository->getTotalForPeriod($userId, $periode2Debut, $periode2Fin);
        $depensesParCategorie2 = $this->depenseRepository->getByCategorie($userId, $periode2Debut, $periode2Fin);
        
        // Calcul des variations
        $variationRevenus = $this->calculerVariation($revenus1, $revenus2);
        $variationDepenses = $this->calculerVariation($depenses1, $depenses2);
        
        // Comparaison par catégorie
        $categoriesComparees = $this->comparerDepensesParCategorie($depensesParCategorie1, $depensesParCategorie2);
        
        return [
            'periodes' => [
                'periode1' => [
                    'debut' => $periode1Debut,
                    'fin' => $periode1Fin
                ],
                'periode2' => [
                    'debut' => $periode2Debut,
                    'fin' => $periode2Fin
                ]
            ],
            'revenus' => [
                'periode1' => $revenus1,
                'periode2' => $revenus2,
                'variation' => $variationRevenus
            ],
            'depenses' => [
                'periode1' => $depenses1,
                'periode2' => $depenses2,
                'variation' => $variationDepenses
            ],
            'economies' => [
                'periode1' => $revenus1 - $depenses1,
                'periode2' => $revenus2 - $depenses2,
                'variation' => $this->calculerVariation($revenus1 - $depenses1, $revenus2 - $depenses2)
            ],
            'categories_comparees' => $categoriesComparees
        ];
    }

    /**
     * Exporte un rapport au format spécifié.
     *
     * @param int $userId
     * @param string $type
     * @param string $format
     * @param array $params
     * @return string
     */
    public function exporterRapport(int $userId, string $type, string $format, array $params): string
    {
        // Récupérer les données du rapport
        $donnees = [];
        
        if ($type === 'mensuel') {
            $donnees = $this->genererRapportMensuel(
                $userId,
                $params['mois'] ?? Carbon::now()->month,
                $params['annee'] ?? Carbon::now()->year
            );
        } elseif ($type === 'annuel') {
            $donnees = $this->genererRapportAnnuel(
                $userId,
                $params['annee'] ?? Carbon::now()->year
            );
        } elseif ($type === 'comparatif') {
            $donnees = $this->genererRapportComparatif(
                $userId,
                $params['periode1_debut'],
                $params['periode1_fin'],
                $params['periode2_debut'],
                $params['periode2_fin']
            );
        }
        
        // Générer le fichier d'export
        // Cette partie dépend de l'implémentation spécifique pour chaque format
        
        // Exemple pour PDF
        if ($format === 'pdf') {
            // Implémenter la génération de PDF
            $nomFichier = storage_path('app/rapports/' . uniqid('rapport_') . '.pdf');
            
            // Code pour générer le PDF
            
            return $nomFichier;
        }
        
        // Exemple pour Excel
        if ($format === 'excel') {
            // Implémenter la génération d'Excel
            $nomFichier = storage_path('app/rapports/' . uniqid('rapport_') . '.xlsx');
            
            // Code pour générer l'Excel
            
            return $nomFichier;
        }
        
        // Par défaut, retourner un format JSON
        $nomFichier = storage_path('app/rapports/' . uniqid('rapport_') . '.json');
        file_put_contents($nomFichier, json_encode($donnees));
        
        return $nomFichier;
    }

    /**
     * Récupère le résumé mensuel pour un utilisateur.
     *
     * @param int $userId
     * @return array
     */
    public function getResumeMensuel(int $userId): array
    {
        $now = Carbon::now();
        $annee = $now->year;
        $mois = $now->month;
        
        return $this->genererRapportMensuel($userId, $mois, $annee);
    }

    /**
     * Récupère les statistiques système (pour l'admin).
     *
     * @return array
     */
    public function getStatistiquesSysteme(): array
    {
        // Statistiques des utilisateurs
        $totalUtilisateurs = $this->userRepository->all()->count();
        $utilisateursActifs = $this->userRepository->getUtilisateursActifs(30)->count();
        
        // Statistiques des transactions
        $totalDepenses = DB::table('depenses')->count();
        $totalRevenus = DB::table('revenus')->count();
        
        // Statistiques des budgets
        $totalBudgets = DB::table('budgets')->count();
        
        // Statistiques des objectifs
        $objectifsEnCours = DB::table('objectifs')->where('statut', 'en cours')->count();
        $objectifsAtteints = DB::table('objectifs')->where('statut', 'atteint')->count();
        
        return [
            'utilisateurs' => [
                'total' => $totalUtilisateurs,
                'actifs' => $utilisateursActifs,
                'taux_activite' => $totalUtilisateurs > 0 ? ($utilisateursActifs / $totalUtilisateurs) * 100 : 0
            ],
            'transactions' => [
                'depenses' => $totalDepenses,
                'revenus' => $totalRevenus,
                'total' => $totalDepenses + $totalRevenus
            ],
            'budgets' => [
                'total' => $totalBudgets
            ],
            'objectifs' => [
                'en_cours' => $objectifsEnCours,
                'atteints' => $objectifsAtteints,
                'taux_reussite' => ($objectifsEnCours + $objectifsAtteints) > 0 
                    ? ($objectifsAtteints / ($objectifsEnCours + $objectifsAtteints)) * 100 
                    : 0
            ]
        ];
    }

    /**
     * Méthodes utilitaires privées
     */

    /**
     * Calcule la variation entre deux valeurs.
     *
     * @param float $valeur1
     * @param float $valeur2
     * @return array
     */
    private function calculerVariation(float $valeur1, float $valeur2): array
    {
        $difference = $valeur2 - $valeur1;
        $pourcentage = $valeur1 != 0 ? ($difference / abs($valeur1)) * 100 : 0;
        
        return [
            'difference' => $difference,
            'pourcentage' => $pourcentage
        ];
    }

    /**
     * Compare les dépenses par catégorie entre deux périodes.
     *
     * @param array $depenses1
     * @param array $depenses2
     * @return array
     */
    private function comparerDepensesParCategorie(array $depenses1, array $depenses2): array
    {
        $resultat = [];
        $categoriesVues = [];
        
        // Parcourir les dépenses de la période 1
        foreach ($depenses1 as $depense) {
            $categoriesVues[$depense->id] = true;
            
            // Chercher la catégorie correspondante dans la période 2
            $categorieCorrespondante = null;
            foreach ($depenses2 as $dep2) {
                if ($dep2->id === $depense->id) {
                    $categorieCorrespondante = $dep2;
                    break;
                }
            }
            
            $valeur1 = $depense->total;
            $valeur2 = $categorieCorrespondante ? $categorieCorrespondante->total : 0;
            
            $resultat[] = [
                'categorie' => [
                    'id' => $depense->id,
                    'nom' => $depense->nom,
                    'icone' => $depense->icone
                ],
                'periode1' => $valeur1,
                'periode2' => $valeur2,
                'variation' => $this->calculerVariation($valeur1, $valeur2)
            ];
        }
        
        // Ajouter les catégories qui n'étaient pas dans la période 1
        foreach ($depenses2 as $depense) {
            if (!isset($categoriesVues[$depense->id])) {
                $resultat[] = [
                    'categorie' => [
                        'id' => $depense->id,
                        'nom' => $depense->nom,
                        'icone' => $depense->icone
                    ],
                    'periode1' => 0,
                    'periode2' => $depense->total,
                    'variation' => $this->calculerVariation(0, $depense->total)
                ];
            }
        }
        
        return $resultat;
    }

    /**
     * Récupère les dépenses pour un objectif.
     *
     * @param Objectif $objectif
     * @return float
     */
    private function getDepensesPourObjectif(Objectif $objectif): float
    {
        $dateDebut = $objectif->date_debut;
        $dateFin = min(Carbon::now()->format('Y-m-d'), $objectif->date_fin);
        
        // Récupérer les dépenses de la catégorie pour la période de l'objectif
        $depenses = DB::table('depenses')
            ->join('categorie_budget', 'depenses.categorie_budget_id', '=', 'categorie_budget.id')
            ->where('categorie_budget.categorie_id', $objectif->categorie_id)
            ->whereBetween('depenses.date_depense', [$dateDebut, $dateFin])
            ->sum('depenses.montant');
            
        return $depenses;
    }
}