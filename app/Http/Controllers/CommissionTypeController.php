<?php

namespace App\Http\Controllers;

use App\Models\CommercialPlaceModels\CommissionType;
use Illuminate\Http\Request;

class CommissionTypeController extends Controller
{
    public function index() {
        try {
            return ['success' => true, 'data' => CommissionType::all()];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => $e->getMessage()];
        }
    }

    public function show($id) {
        try {
            $item = CommissionType::findOrFail($id);
            return ['success' => true, 'data' => $item];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => $e->getMessage()];
        }
    }

    public function store(Request $request) {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $item = CommissionType::create($validated);

            return ['success' => true, 'data' => $item];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => $e->getMessage()];
        }
    }

    public function update(Request $request, $id) {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
            ]);

            $item = CommissionType::findOrFail($id);
            $item->update($validated);

            return ['success' => true, 'data' => $item];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => $e->getMessage()];
        }
    }

    public function destroy($id) {
        try {
            CommissionType::findOrFail($id)->delete();
            return ['success' => true, 'data' => 'Deleted successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => $e->getMessage()];
        }
    }
}