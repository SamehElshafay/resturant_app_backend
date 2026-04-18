<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    public function index()
    {
        $ingredients = Ingredient::all();
        return view('ingredients.index', compact('ingredients'));
    }

    public function create()
    {
        return view('ingredients.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_ar' => 'required|string',
            'name_en' => 'nullable|string',
            'unit' => 'required|in:kg,g,ltr,ml,piece',
            'cost_price' => 'nullable|numeric|min:0',
        ]);

        Ingredient::create($request->all());

        return redirect()->route('ingredients.index')->with('success', 'Ingredient added successfully');
    }

    public function edit(Ingredient $ingredient)
    {
        return view('ingredients.edit', compact('ingredient'));
    }

    public function update(Request $request, Ingredient $ingredient)
    {
        $request->validate([
            'name_ar' => 'required|string',
            'name_en' => 'nullable|string',
            'unit' => 'required|in:kg,g,ltr,ml,piece',
            'cost_price' => 'nullable|numeric|min:0',
        ]);

        $ingredient->update($request->all());

        return redirect()->route('ingredients.index')->with('success', 'Ingredient updated successfully');
    }

    public function destroy(Ingredient $ingredient)
    {
        $ingredient->delete();
        return back()->with('success', 'Ingredient deleted successfully');
    }

    public function search(Request $request)
    {
        $term = $request->q;
        $query = Ingredient::query();

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('name_ar', 'LIKE', "%{$term}%")
                    ->orWhere('name_en', 'LIKE', "%{$term}%")
                    ->orWhere('id', 'LIKE', "%{$term}%");
            });
        }

        $ingredients = $query->limit(10)->get();

        $formatted = $ingredients->map(function ($i) {
            return [
                'id' => $i->id,
                'text' => $i->id . ' - ' . ($i->name_ar ?? $i->name_en ?? $i->name),
                'cost' => $i->cost_price ?? 0,
                'unit' => $i->unit
            ];
        });

        return response()->json($formatted);
    }
}
