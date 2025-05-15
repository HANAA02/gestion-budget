<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::orderBy('nom')->get();
        
        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100|unique:categories',
            'description' => 'nullable|string',
            'icone' => 'nullable|string|max:50',
            'pourcentage_defaut' => 'required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = Category::create([
            'nom' => $request->nom,
            'description' => $request->description,
            'icone' => $request->icone,
            'pourcentage_defaut' => $request->pourcentage_defaut,
        ]);

        return response()->json([
            'message' => 'Catégorie créée avec succès',
            'category' => $category
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::findOrFail($id);
        
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:100|unique:categories,nom,' . $id,
            'description' => 'nullable|string',
            'icone' => 'nullable|string|max:50',
            'pourcentage_defaut' => 'sometimes|required|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('nom')) {
            $category->nom = $request->nom;
        }
        
        if ($request->has('description')) {
            $category->description = $request->description;
        }
        
        if ($request->has('icone')) {
            $category->icone = $request->icone;
        }
        
        if ($request->has('pourcentage_defaut')) {
            $category->pourcentage_defaut = $request->pourcentage_defaut;
        }

        $category->save();

        return response()->json([
            'message' => 'Catégorie mise à jour avec succès',
            'category' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        
        // Vérifier si la catégorie est utilisée dans des budgets
        if ($category->categoryBudgets()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette catégorie car elle est utilisée dans des budgets',
            ], 422);
        }
        
        $category->delete();

        return response()->json([
            'message' => 'Catégorie supprimée avec succès'
        ]);
    }
}