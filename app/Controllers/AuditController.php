<?php

namespace App\Controllers;

use App\Core\{Middleware, AuditLog};

class AuditController
{
    public function index(): void
    {
        Middleware::owner();

        $tableName = $_GET['table'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $logs = AuditLog::getAll($perPage, $offset, $tableName ?: null);

        view('layouts/app', [
            'content' => 'audit/index',
            'data' => [
                'pageTitle'  => 'Audit napló',
                'activeTab'  => 'audit',
                'logs'       => $logs,
                'tableName'  => $tableName,
                'page'       => $page,
            ]
        ]);
    }
}
