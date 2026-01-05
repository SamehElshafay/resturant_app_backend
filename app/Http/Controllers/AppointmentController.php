<?php

namespace App\Http\Controllers;

use App\Models\CommercialPlaceModels\Appointment;
use Illuminate\Http\Request;
use App\Traits\TransactionResponse;

class AppointmentController extends Controller
{
    use TransactionResponse;

    public function index(Request $request){
        return $this->transactionResponse(function () use ($request) {
            $query = Appointment::query();

            if ($request->filled('commercial_place')) {
                $query->where('commercial_place', $request->commercial_place);
            }

            return $query->orderBy('day_name')->get();
        });
    }

    public function show($id){
        return $this->tryCatchBody(function () use ($id) {
            return Appointment::findOrFail($id);
        });
    }

    public function store(Request $request){
        return $this->transactionResponse(function () use ($request) {
            $data = $request->validate([
                '*.commercial_place' => 'required|exists:commercial_place,id',
                '*.day_name' => 'required|string',
                '*.open_time' => 'required|date_format:H:i:s',
                '*.close_time' => 'required|date_format:H:i:s|after:*.open_time',
            ]);

            $appointments = [];
            foreach ($data as $item) {
                $appointments[] = Appointment::create($item);
            }

            return $appointments;
        });
    }


    public function update(Request $request, $id){
        return $this->transactionResponse(function () use ($request, $id) {
            $appointment = Appointment::findOrFail($id);

            $data = $request->validate([
                'day_name' => 'sometimes|string',
                'open_time' => 'sometimes|date_format:H:i:s',
                'close_time' => 'sometimes|date_format:H:i:s|after:open_time',
            ]);

            $appointment->update($data);

            return $appointment->fresh();
        });
    }

    public function destroy($id){
        return $this->transactionResponse(function () use ($id) {
            $appointment = Appointment::findOrFail($id);
            $appointment->delete();
            return true;
        });
    }
}