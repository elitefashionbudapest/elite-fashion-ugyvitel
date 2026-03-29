<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, AuditLog};
use App\Models\{Evaluation, Store, Employee};

class EvaluationController
{
    public function index(): void
    {
        Middleware::tabPermission('ertekeles', 'view');

        $storeId  = Auth::isStore() ? Auth::storeId() : ($_GET['store_id'] ?? null);
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo   = $_GET['date_to'] ?? null;
        $month    = (int)($_GET['month'] ?? date('n'));
        $year     = (int)($_GET['year'] ?? date('Y'));

        $evaluations = Evaluation::all(
            $storeId ? (int)$storeId : null,
            $dateFrom ?: null,
            $dateTo ?: null
        );

        $stores          = Auth::isOwner() ? Store::all() : [];
        $premiumStatuses = Evaluation::getAllPremiumStatuses($month, $year);

        view('layouts/app', [
            'content' => 'evaluations/index',
            'data' => [
                'pageTitle'       => 'Bolti Ertekeles',
                'activeTab'       => 'ertekeles',
                'evaluations'     => $evaluations,
                'stores'          => $stores,
                'premiumStatuses' => $premiumStatuses,
                'filters'         => [
                    'store_id'  => $storeId,
                    'date_from' => $dateFrom,
                    'date_to'   => $dateTo,
                    'month'     => $month,
                    'year'      => $year,
                ],
            ]
        ]);
    }

    public function create(): void
    {
        Middleware::tabPermission('ertekeles', 'create');

        $stores    = Auth::isOwner() ? Store::all() : [];
        $storeId   = Auth::isStore() ? Auth::storeId() : null;
        $employees = $storeId ? Employee::getByStore($storeId) : [];

        view('layouts/app', [
            'content' => 'evaluations/form',
            'data' => [
                'pageTitle'  => 'Uj ertekeles',
                'activeTab'  => 'ertekeles',
                'stores'     => $stores,
                'employees'  => $employees,
                'storeId'    => $storeId,
            ]
        ]);
    }

    public function store(): void
    {
        Middleware::tabPermission('ertekeles', 'create');
        Middleware::verifyCsrf();

        $storeId = Auth::isStore() ? Auth::storeId() : (int)($_POST['store_id'] ?? 0);

        if (!$storeId) {
            save_old_input();
            set_flash('error', 'Valasszon uzletet.');
            redirect('/evaluations/create');
        }

        $evaluationDate    = $_POST['record_date'] ?? date('Y-m-d');
        $customerCount     = (int)($_POST['customer_count'] ?? 0);
        $googleReviewCount = (int)($_POST['google_review_count'] ?? 0);
        $workerIds         = $_POST['worker_ids'] ?? [];

        if ($customerCount < 0 || $googleReviewCount < 0) {
            save_old_input();
            set_flash('error', 'A szamok nem lehetnek negativak.');
            redirect('/evaluations/create');
        }

        $id = Evaluation::create([
            'store_id'            => $storeId,
            'record_date'     => $evaluationDate,
            'customer_count'      => $customerCount,
            'google_review_count' => $googleReviewCount,
        ]);

        if (!empty($workerIds)) {
            Evaluation::addWorkers($id, $workerIds);
        }

        AuditLog::log('create', 'evaluations', $id, null, [
            'store_id'            => $storeId,
            'record_date'     => $evaluationDate,
            'customer_count'      => $customerCount,
            'google_review_count' => $googleReviewCount,
            'workers'             => $workerIds,
        ]);

        set_flash('success', 'Ertekeles sikeresen rogzitve.');
        redirect('/evaluations');
    }

    public function destroy(string $id): void
    {
        Middleware::tabPermission('ertekeles', 'edit');
        Middleware::verifyCsrf();

        $evaluation = Evaluation::find((int)$id);
        if ($evaluation) {
            if (Auth::isStore() && $evaluation['store_id'] !== Auth::storeId()) {
                redirect('/evaluations');
            }
            Evaluation::delete((int)$id);
            AuditLog::log('delete', 'evaluations', (int)$id, $evaluation, null);
            set_flash('success', 'Ertekeles torolve.');
        }
        redirect('/evaluations');
    }

    /**
     * AJAX vegpont: dolgozok lekerese bolt alapjan
     */
    public function employeesByStore(string $storeId): void
    {
        Middleware::tabPermission('ertekeles', 'edit');

        $employees = Employee::getByStore((int)$storeId);
        header('Content-Type: application/json');
        echo json_encode($employees);
        exit;
    }
}
