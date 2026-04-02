<?php

namespace App\Controllers;

class DocsController
{
    public function index(): void
    {
        require __DIR__ . '/../Views/docs/index.php';
    }
}
