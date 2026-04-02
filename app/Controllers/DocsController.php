<?php

namespace App\Controllers;

use App\Core\Middleware;

class DocsController
{
    public function index(): void
    {
        Middleware::auth();
        require __DIR__ . '/../Views/docs/index.php';
    }
}
