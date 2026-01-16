<?php

namespace App\Http\Controllers;

use App\Models\DriverModels\DriverService;
use App\Models\DriverModels\DriverServiceState;
use App\Models\MerchantModels\MerchantServState;
use App\Traits\TransactionResponse;
use Illuminate\Http\Request;

class DriverServiceController extends Controller {
    use TransactionResponse;

    public function store(Request $request) {
        return $this->transactionResponseWithoutReturn(
            function () use ($request) {
                $data = $request->validate([
                    'phone_number' => 'required|string|max:100',
                    'country_code' => 'required|string|max:5',
                ]);

                if (DriverService::where('phone_number', $data['phone_number'])->exists()) {
                    throw new \Exception('your request is already submitted and under review' , 500 );
                }

                $service = DriverService::create([
                    'phone_number' => $data['phone_number'],
                    'country_code' => $data['country_code'],
                ]);

                DriverServiceState::create([
                    'driver_services_id' => $service->id,
                    'state_id'             => 1 ,
                    'note'                 => $data['note'] ?? 'Initial state',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'your request has been sent successfully',
                ], 200);
            }
        );
    }

    public function show(int $id) {
        return$this->transactionResponseWithoutReturn(
            function () use ($id) {
                $service = DriverService::with([
                    'latestState.state',
                    'states.state'
                ])->findOrFail($id);

                if($service->latestState->id != 4){
                    $service->nextState = MerchantServState::find($service->latestState->id + 1);
                }

                return [
                    'success' => true,
                    'data' => $service
                ];
            }
        );
    }

    public function index(Request $request) {
        return $this->transactionResponseWithoutReturn(
            function () use ($request) {
                $validated = $request->validate([
                    //'page' => 'nullable|integer|min:1',
                    'phone_number' => 'nullable|string|max:100',
                ]);

                $service = DriverService::with([
                    'latestState.state',
                ])->where(function($query) use ($validated) {
                    if (isset($validated['phone_number'])) {
                        $query->where('phone_number', 'like', '%' . $validated['phone_number'] . '%');
                    }
                })->paginate(10);

                return [
                    'success' => true,
                    'data' => $service->getCollection() ,
                    'meta' => [
                        'current_page' => $service->currentPage(),
                        'last_page'    => $service->lastPage(),
                        'per_page'     => $service->perPage(),
                        'total'        => $service->total(),
                    ],
                ];
            }
        );
    }

    public function changeState(Request $request) {
        return$this->transactionResponseWithoutReturn(
            function () use ($request) {
                $data = $request->validate([
                    'id'       => 'required|exists:merchant_services,id',
                    'note'     => 'nullable|string',
                    'state'    => 'nullable|string|in:cancele' ,
                ]);

                $service = DriverService::findOrFail($data['id']);

                $status_id = $request->state == 'cancele' ? 5 : $service->latestState->state_id + 1;

                $nextState = MerchantServState::find($status_id);

                if(!$nextState || $service->latestState->state_id == 4) {
                    throw new \Exception('No further state available for this service', 400);
                }

                DriverServiceState::create([
                    'driver_services_id' => $service->id,
                    'state_id'             => $nextState->id,
                    'note'                 => $data['note'] ?? null,
                ]);

                return response()->json([
                    'message' => 'State updated successfully',
                    'new_state' => [
                        'name' => $nextState->name,
                        'id'   => $status_id,
                    ],
                ]);
            }
        );
    }

    public function destroy(int $id) {
        $service = DriverService::findOrFail($id);
        // حذف الهيستوري الأول
        $service->states()->delete();
        $service->delete();

        return response()->json(['message' => 'Driver service deleted']);
    }
}