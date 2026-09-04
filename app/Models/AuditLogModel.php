<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'module', 'action', 'record_id',
        'data_before', 'data_after', 'impact',
        'user_note', 'created_at'
    ];
    protected $useTimestamps = false;

    /**
     * Helper to log an audit event safely.
     * Wrapped in try/catch so it NEVER breaks existing functionality.
     */
    public static function log(string $module, string $action, ?int $recordId = null, $dataBefore = null, $dataAfter = null, $impact = null, string $note = '')
    {
        try {
            $model = new self();
            $model->insert([
                'module'      => $module,
                'action'      => $action,
                'record_id'   => $recordId,
                'data_before' => $dataBefore ? json_encode($dataBefore) : null,
                'data_after'  => $dataAfter ? json_encode($dataAfter) : null,
                'impact'      => $impact ? json_encode($impact) : null,
                'user_note'   => $note,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // NEVER let audit logging break the system
            log_message('error', 'AuditLog failed: ' . $e->getMessage());
        }
    }

    /**
     * Get filtered audit logs for the frontend
     */
    public function getFiltered($filters = [])
    {
        $builder = $this->builder();
        $builder->orderBy('created_at', 'DESC');

        if (!empty($filters['module'])) {
            $builder->where('module', $filters['module']);
        }
        if (!empty($filters['action'])) {
            $builder->where('action', $filters['action']);
        }
        if (!empty($filters['date_start'])) {
            $builder->where('created_at >=', $filters['date_start'] . ' 00:00:00');
        }
        if (!empty($filters['date_end'])) {
            $builder->where('created_at <=', $filters['date_end'] . ' 23:59:59');
        }
        if (!empty($filters['search'])) {
            $builder->like('user_note', $filters['search']);
        }

        $builder->limit(200);
        return $builder->get()->getResultArray();
    }
}
