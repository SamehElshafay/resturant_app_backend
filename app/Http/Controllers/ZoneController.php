<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function index(){
        return [
            'success' => true ,
            'zones' => Zone::all()
        ];
    }
}