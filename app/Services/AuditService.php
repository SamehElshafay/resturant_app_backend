<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService {
    public function log(
        string $tableName,
        int|string|null $recordId,
        string $action,
        array $oldData = null,
        array $newData = null
    ): void {
        AuditLog::create([
            'table_name'   => $tableName,
            'record_id'    => $recordId,
            'action'       => $action,
            'performed_by' => auth('user')->user()->id ,
            'performed_at' => now(),
            'old_data'     => $oldData,
            'new_data'     => $newData,
        ]);
    }
}
