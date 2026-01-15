<?php

namespace App\Http\Controllers;

use App\Models\CommercialPlaceModels\Appointment;
use Illuminate\Http\Request;
use App\Traits\TransactionResponse;
use Illuminate\Support\Facades\App;

class AppointmentController extends Controller
{
    use TransactionResponse;

    public function indexAll(){
        return $this->transactionResponse(function () {
            $merchant = auth('merchant')->user();
            $query = Appointment::where('commercial_place', $merchant->commercial_place_id) ;
            return $query->orderBy('day_name')->get();
        });
    }

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
                '*.day_name' => 'required|string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',
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

    public function addAppointment(Request $request){
        return $this->transactionResponse(function () use ($request) {
            $data = $request->validate([
                //'*.commercial_place' => 'required|exists:commercial_place,id',
                '*.day_name' => 'required|string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',
                '*.open_time' => 'required|date_format:H:i:s',
                '*.close_time' => 'required|date_format:H:i:s|after:*.open_time',
            ]);

            $merchant = auth('merchant')->user();
            $commercial_place_id = $merchant->commercial_place_id ;
            $appointments = [];
            
            foreach ($data as $item) {
                $item['commercial_place'] = $commercial_place_id ;
                if(Appointment::where('commercial_place', $commercial_place_id)->where('day_name', $item['day_name'])->exists()){
                    Throw new \Exception("Appointment for ".$item['day_name']." already exists.") ;
                }
                $appointments[] = Appointment::create($item);
            }

            return $appointments;
        });
    }


    public function update(Request $request, $id){
        return $this->transactionResponse(function () use ($request, $id) {
            $appointment = Appointment::findOrFail($id);

            $data = $request->validate([
                'day_name' => 'sometimes|string|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday',
                'open_time' => 'sometimes|date_format:H:i:s',
                'close_time' => 'sometimes|date_format:H:i:s|after:open_time',
            ]);

            if(Appointment::where('commercial_place', $appointment->commercial_place)->where('day_name', $data['day_name'])->exists()){
                Throw new \Exception("Appointment for ".$data['day_name']." already exists.") ;
            }

            $appointment->update($data);

            return $appointment->fresh();
        });
    }

    public function destroy($id){
        return $this->transactionResponse(function () use ($id) {
            $appointment = Appointment::findOrFail($id);
            $appointment->delete() ;
            return true;
        });
    }
}