<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait TransactionResponse
{
    public function transactionResponse(callable $callback) {
        DB::beginTransaction();
        try {
            $data = $callback();
            DB::commit();
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function transactionResponseWithoutReturn(callable $callback) {
        DB::beginTransaction();
        try {
            $data = $callback();
            DB::commit();
            return $data ;
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function tryCatchBody(callable $callback) {
        try {
            $data = $callback();
            return $data ;
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}