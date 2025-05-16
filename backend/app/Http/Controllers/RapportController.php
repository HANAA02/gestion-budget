<?php

namespace App\Http\Controllers;

use App\Services\StatistiqueService;
use Illuminate\Http\Request;

class RapportController extends Controller
{
    protected $statistiqueService;

    public function __construct(StatistiqueService $statistiqueService)
    {
        $this->statistiqueService = $statistiqueService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Génère un rapport mensuel.
     */
    public function mensuel(Request $request)
    {
        $userId = $request->user()->id;
        $mois = $request->input('mois', date('m'));
        $annee = $request->input('annee', date('Y'));
        
        $rapport = $this->statistiqueService->genererRapportMensuel($userId, $mois, $annee);
        
        return response()->json($rapport);
    }
    
    /**
     * Génère un rapport annuel.
     */
    public function annuel(Request $request)
    {
        $userId = $request->user()->id;
        $annee = $request->input('annee', date('Y'));
        
        $rapport = $this->statistiqueService->genererRapportAnnuel($userId, $annee);
        
        return response()->json($rapport);
    }
    
    /**
     * Génère un rapport comparatif entre deux périodes.
     */
    public function comparatif(Request $request)
    {
        $userId = $request->user()->id;
        $periode1Debut = $request->input('periode1_debut');
        $periode1Fin = $request->input('periode1_fin');
        $periode2Debut = $request->input('periode2_debut');
        $periode2Fin = $request->input('periode2_fin');
        
        $rapport = $this->statistiqueService->genererRapportComparatif(
            $userId,
            $periode1Debut,
            $periode1Fin,
            $periode2Debut,
            $periode2Fin
        );
        
        return response()->json($rapport);
    }
    
    /**
     * Exporte un rapport au format spécifié.
     */
    public function exporter(Request $request)
    {
        $userId = $request->user()->id;
        $type = $request->input('type', 'mensuel');
        $format = $request->input('format', 'pdf');
        $params = $request->except(['type', 'format']);
        
        $fichier = $this->statistiqueService->exporterRapport($userId, $type, $format, $params);
        
        return response()->download($fichier);
    }
}