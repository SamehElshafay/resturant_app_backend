<?php

namespace App\Http\Controllers;

use App\Models\Method;
use App\Traits\TransactionResponse;
use Illuminate\Http\Request;

class MethodController extends Controller
{
    use TransactionResponse;

    public function index(){
        return response()->json([
            'methods' => Method::all(),
            'success' => true
        ]);
    }

    // GET /methods/{id}
    public function show($id){   
        $this->tryCatchBody(function () use ($id) { 
            $method = Method::findOrFail($id);
            return response()->json([
                'method' => $method ,
                'success' => true
            ]);
        });
    }

    // POST /methods
    public function store(Request $request)
    {
        $this->transactionResponse(function () use ($request) { 
            $data = $request->validate([
                'name' => 'required|string|max:255|unique:method,name',
            ]);

            $method = Method::create($data);

            return response()->json([
                'success' => true,
                'method' => $method,
            ], 201);
        });
    }

    // PUT /methods/{id}
    public function update(Request $request, $id)
    {
        $this->transactionResponse(function () use ($request, $id) {
        });
            $method = Method::findOrFail($id);

            $data = $request->validate([
                'name' => 'required|string|max:255|unique:method,name,' . $method->id,
            ]);

            $method->update($data);

            return response()->json([
                'success' => true,
                'method' => $method,
            ]);
        }

    // DELETE /methods/{id}
    public function destroy($id){
        $this->transactionResponse(function () use ($id) {
            Method::findOrFail($id)->delete();
            return response()->json([
                'success' => true,
                'message' => 'Method deleted successfully',
            ]);
        });
    }
}
