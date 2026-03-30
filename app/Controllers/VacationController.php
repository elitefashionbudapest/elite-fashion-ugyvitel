<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, AuditLog};
use App\Models\{VacationRequest, Employee};

class VacationController
{
    public function index(): void
    {
        Middleware::tabPermission('szabadsag', 'view');

        // Szabadságok céges szintűek (max 1 fő) — mindenki az összeset látja
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
        $employeeId = isset($_GET['employee_id']) && $_GET['employee_id'] !== '' ? (int)$_GET['employee_id'] : null;
        $dateFrom = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? $_GET['date_to'] : null;

        $requests = VacationRequest::all(null, $status, $employeeId, $dateFrom, $dateTo);

        // Dolgozók listája a szűrőhöz — mindenki az összeset látja
        $employees = Employee::allActive();

        view('layouts/app', [
            'content' => 'vacation/index',
            'data' => [
                'pageTitle'  => 'Szabadság kérvényező',
                'activeTab'  => 'szabadsag',
                'requests'   => $requests,
                'employees'  => $employees,
                'filters'    => ['status' => $status, 'employee_id' => $employeeId, 'date_from' => $dateFrom, 'date_to' => $dateTo],
            ]
        ]);
    }

    public function create(): void
    {
        Middleware::tabPermission('szabadsag', 'create');

        // Szabadság céges szintű — mindenki az összes dolgozót látja
        $employees = Employee::allActive();

        view('layouts/app', [
            'content' => 'vacation/form',
            'data' => [
                'pageTitle'  => 'Szabadság kérvény',
                'activeTab'  => 'szabadsag',
                'employees'  => $employees,
            ]
        ]);
    }

    public function store(): void
    {
        Middleware::tabPermission('szabadsag', 'create');
        Middleware::verifyCsrf();

        $employeeId = (int) ($_POST['employee_id'] ?? 0);
        $dateFrom   = $_POST['date_from'] ?? '';
        $dateTo     = $_POST['date_to'] ?? '';
        $confirmed  = isset($_POST['confirmed_no_overlap']) ? 1 : 0;

        if (empty($employeeId) || empty($dateFrom) || empty($dateTo)) {
            save_old_input();
            set_flash('error', 'Minden mező kitöltése kötelező.');
            redirect('/vacation/create');
        }

        if ($dateTo < $dateFrom) {
            save_old_input();
            set_flash('error', 'A végdátum nem lehet korábbi a kezdődátumnál.');
            redirect('/vacation/create');
        }

        if (!$confirmed) {
            save_old_input();
            set_flash('error', 'Kérlek erősítsd meg, hogy ellenőrizted: nincs átfedés más jóváhagyott szabadsággal.');
            redirect('/vacation/create');
        }

        if (VacationRequest::hasOverlap($dateFrom, $dateTo)) {
            save_old_input();
            set_flash('error', 'Ebben az időszakban már van jóváhagyott szabadság! Egyszerre csak 1 fő lehet szabadságon az egész cégnél.');
            redirect('/vacation/create');
        }

        $data = [
            'employee_id'          => $employeeId,
            'date_from'            => $dateFrom,
            'date_to'              => $dateTo,
            'confirmed_no_overlap' => $confirmed,
        ];

        try {
            $id = VacationRequest::create($data);
            AuditLog::log('create', 'vacation_requests', $id, null, $data);
            set_flash('success', 'Szabadság kérvény sikeresen beadva.');
        } catch (\RuntimeException $e) {
            save_old_input();
            set_flash('error', $e->getMessage());
            redirect('/vacation/create');
        }

        redirect('/vacation');
    }

    public function approve(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $request = VacationRequest::find((int) $id);
        if (!$request) {
            $this->redirectBack();
        }

        try {
            $old = $request;
            VacationRequest::approve((int) $id, Auth::id());
            AuditLog::log('approve', 'vacation_requests', (int) $id, $old, ['status' => 'approved']);
            set_flash('success', 'Szabadság jóváhagyva.');
        } catch (\RuntimeException $e) {
            set_flash('error', $e->getMessage());
        }

        $this->redirectBack();
    }

    public function reject(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $request = VacationRequest::find((int) $id);
        if (!$request) {
            $this->redirectBack();
        }

        $old = $request;
        VacationRequest::reject((int) $id, Auth::id());
        AuditLog::log('reject', 'vacation_requests', (int) $id, $old, ['status' => 'rejected']);
        set_flash('success', 'Szabadság elutasítva.');
        $this->redirectBack();
    }

    public function destroy(string $id): void
    {
        Middleware::tabPermission('szabadsag', 'edit');
        Middleware::verifyCsrf();

        $request = VacationRequest::find((int) $id);
        if ($request) {
            VacationRequest::delete((int) $id);
            AuditLog::log('delete', 'vacation_requests', (int) $id, $request, null);
            set_flash('success', 'Szabadság kérvény törölve.');
        }
        $this->redirectBack();
    }

    private function redirectBack(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $basePath = getenv('APP_BASE_PATH') ?: '';
        if ($referer && str_contains($referer, '/vacation')) {
            $parsed = parse_url($referer);
            $path = $parsed['path'] ?? '/vacation';
            if ($basePath && str_starts_with($path, $basePath)) {
                $path = substr($path, strlen($basePath));
            }
            $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
            redirect($path . $query);
        }
        redirect('/vacation');
    }
}
