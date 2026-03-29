<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, AuditLog};
use App\Models\{SalaryPayment, OwnerPayment, Employee};

class SalaryController
{
    public function index(): void
    {
        Middleware::tabPermission('fizetes', 'view');

        $tab = $_GET['tab'] ?? 'dolgozoi';

        // Nem tulajdonos nem láthatja a tulajdonosi fizetéseket
        if ($tab === 'tulajdonosi' && !Auth::isOwner()) {
            $tab = 'dolgozoi';
        }

        $employeeId = !empty($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
        $ownerName = $_GET['owner_name'] ?? null;
        $year = !empty($_GET['year']) ? (int)$_GET['year'] : null;
        $month = !empty($_GET['month']) ? (int)$_GET['month'] : null;

        $records = SalaryPayment::all($employeeId, $year, $month);
        $ownerRecords = Auth::isOwner() ? OwnerPayment::all($ownerName ?: null, $year, $month) : [];
        $employees = Employee::allActive();

        view('layouts/app', [
            'content' => 'salary/index',
            'data' => [
                'pageTitle'    => 'Fizetések',
                'activeTab'    => 'fizetes',
                'tab'          => $tab,
                'records'      => $records,
                'ownerRecords' => $ownerRecords,
                'employees'    => $employees,
                'filters'      => [
                    'employee_id' => $employeeId,
                    'owner_name'  => $ownerName,
                    'year'        => $year,
                    'month'       => $month,
                ],
            ]
        ]);
    }

    public function create(): void
    {
        Middleware::tabPermission('fizetes', 'create');

        $type = $_GET['type'] ?? 'dolgozoi';
        if ($type === 'tulajdonosi' && !Auth::isOwner()) {
            redirect('/salary');
        }
        $employees = Employee::allActive();

        view('layouts/app', [
            'content' => 'salary/form',
            'data' => [
                'pageTitle'  => $type === 'tulajdonosi' ? 'Tulajdonosi fizetés rögzítés' : 'Dolgozói fizetés rögzítés',
                'activeTab'  => 'fizetes',
                'type'       => $type,
                'employees'  => $employees,
            ]
        ]);
    }

    public function store(): void
    {
        Middleware::tabPermission('fizetes', 'create');
        Middleware::verifyCsrf();

        $type = $_POST['type'] ?? 'dolgozoi';
        if ($type === 'tulajdonosi' && !Auth::isOwner()) {
            redirect('/salary');
        }

        if ($type === 'tulajdonosi') {
            $data = [
                'owner_name'  => $_POST['owner_name'] ?? '',
                'year'        => (int)($_POST['year'] ?? date('Y')),
                'month'       => (int)($_POST['month'] ?? date('n')),
                'source'      => $_POST['source'] ?? '',
                'amount'      => (float)($_POST['amount'] ?? 0),
                'recorded_by' => Auth::id(),
            ];

            if (empty($data['owner_name']) || !isset(OwnerPayment::OWNERS[$data['owner_name']])) {
                save_old_input();
                set_flash('error', 'Válasszon tulajdonost.');
                redirect('/salary/create?type=tulajdonosi');
            }
            if (!isset(OwnerPayment::SOURCES[$data['source']])) {
                save_old_input();
                set_flash('error', 'Válasszon fizetési forrást.');
                redirect('/salary/create?type=tulajdonosi');
            }
            if ($data['amount'] <= 0) {
                save_old_input();
                set_flash('error', 'Az összeg nem lehet nulla vagy negatív.');
                redirect('/salary/create?type=tulajdonosi');
            }

            $id = OwnerPayment::create($data);
            AuditLog::log('create', 'owner_payments', $id, null, $data);
            set_flash('success', 'Tulajdonosi fizetés sikeresen rögzítve.');
            redirect('/salary?tab=tulajdonosi');
        } else {
            $data = [
                'employee_id' => (int)($_POST['employee_id'] ?? 0),
                'issuer'      => '',
                'year'        => (int)($_POST['year'] ?? date('Y')),
                'month'       => (int)($_POST['month'] ?? date('n')),
                'source'      => $_POST['source'] ?? '',
                'amount'      => (float)($_POST['amount'] ?? 0),
                'created_by'  => Auth::id(),
            ];

            if (empty($data['employee_id'])) {
                save_old_input();
                set_flash('error', 'Válasszon dolgozót.');
                redirect('/salary/create');
            }
            if (!isset(SalaryPayment::SOURCES[$data['source']])) {
                save_old_input();
                set_flash('error', 'Válasszon fizetési forrást.');
                redirect('/salary/create');
            }
            if ($data['amount'] <= 0) {
                save_old_input();
                set_flash('error', 'Az összeg nem lehet nulla vagy negatív.');
                redirect('/salary/create');
            }

            $id = SalaryPayment::create($data);
            AuditLog::log('create', 'salary_payments', $id, null, $data);
            set_flash('success', 'Dolgozói fizetés sikeresen rögzítve.');
            redirect('/salary?tab=dolgozoi');
        }
    }

    public function destroy(string $id): void
    {
        Middleware::tabPermission('fizetes', 'edit');
        Middleware::verifyCsrf();

        $type = $_POST['type'] ?? 'dolgozoi';

        if ($type === 'tulajdonosi') {
            $record = OwnerPayment::find((int)$id);
            if ($record) {
                OwnerPayment::delete((int)$id);
                AuditLog::log('delete', 'owner_payments', (int)$id, $record, null);
                set_flash('success', 'Tulajdonosi fizetés törölve.');
            }
            redirect('/salary?tab=tulajdonosi');
        } else {
            $record = SalaryPayment::find((int)$id);
            if ($record) {
                SalaryPayment::delete((int)$id);
                AuditLog::log('delete', 'salary_payments', (int)$id, $record, null);
                set_flash('success', 'Dolgozói fizetés törölve.');
            }
            redirect('/salary?tab=dolgozoi');
        }
    }
}
