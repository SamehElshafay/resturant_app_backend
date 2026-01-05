<?php

namespace App\Http\Controllers;

use App\Models\CommercialPlaceModels\CommercialPlaceCommission;
use Illuminate\Http\Request;

class CommercialPlaceCommissionController extends Controller
{
    public function index() {
        try {
            return ['success' => true, 'data' => CommercialPlaceCommission::all()];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => $e->getMessage()];
        }
    }

    public function show($id) {
        try {
            $item = CommercialPlaceCommission::findOrFail($id);
            return ['success' => true, 'data' => $item];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => $e->getMessage()];
        }
    }

    public function store(Request $request) {
        try {
            $validated = $request->validate([
                'commercial_place_id' => 'required|integer|exists:commercial_place,id',
                'commission_id'       => 'required|integer|exists:commission_type,id',
                'value'               => 'required|numeric',
            ]);

            $item = CommercialPlaceCommission::create($validated);

            return ['success' => true, 'data' => $item];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => $e->getMessage()];
        }
    }

    public function update(Request $request, $id) {
        try {
            $validated = $request->validate([
                'commercial_place_id' => 'sometimes|integer|exists:commercial_place,id',
                'commission_id'       => 'sometimes|integer|exists:commission_type,id',
                'value'               => 'sometimes|numeric',
            ]);

            $item = CommercialPlaceCommission::findOrFail($id);
            $item->update($validated);

            return ['success' => true, 'data' => $item];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => $e->getMessage()];
        }
    }

    public function destroy($id) {
        try {
            CommercialPlaceCommission::findOrFail($id)->delete();
            return ['success' => true, 'message' => 'Deleted successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => $e->getMessage()];
        }
    }
}