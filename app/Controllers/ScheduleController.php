<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware};
use App\Models\{Schedule, Employee, VacationRequest, Store};

class ScheduleController
{
    /**
     * Beosztas naptar nezet
     */
    public function index(): void
    {
        Middleware::tabPermission('beosztas', 'view');

        $stores = Auth::isOwner() ? Store::all() : [];
        $storeId = Auth::isStore() ? Auth::storeId() : null;

        // Minden aktív dolgozó beosztható bármelyik boltba
        $employees = Employee::allActive();

        if (!$storeId && !empty($stores)) {
            $storeId = (int) $stores[0]['id'];
        }

        view('layouts/app', [
            'content' => 'schedule/index',
            'data' => [
                'pageTitle'      => 'Beosztas',
                'activeTab'      => 'beosztas',
                'stores'         => $stores,
                'employees'      => $employees,
                'currentStoreId' => $storeId,
            ]
        ]);
    }

    /**
     * API: Egy dolgozó összes beosztása (minden boltból) + szabadságok
     */
    public function apiEmployee(): void
    {
        Middleware::tabPermission('beosztas', 'view');

        $employeeId = (int)($_GET['employee_id'] ?? 0);
        $start = $_GET['start'] ?? '';
        $end = $_GET['end'] ?? '';

        if (!$employeeId || !$start || !$end) {
            $this->json(['schedules' => [], 'vacations' => []], 200);
            return;
        }

        $db = \App\Core\Database::getInstance();

        // Dolgozó beosztásai minden boltból
        $stmt = $db->prepare(
            'SELECT sc.*, s.name as store_name
             FROM schedules sc
             JOIN stores s ON sc.store_id = s.id
             WHERE sc.employee_id = :emp AND sc.work_date >= :start AND sc.work_date <= :end
             ORDER BY sc.work_date'
        );
        $stmt->execute(['emp' => $employeeId, 'start' => $start, 'end' => $end]);
        $schedules = $stmt->fetchAll();

        // Szabadságok
        $stmt = $db->prepare(
            "SELECT * FROM vacation_requests
             WHERE employee_id = :emp AND status = 'approved'
             AND date_from <= :end AND date_to >= :start"
        );
        $stmt->execute(['emp' => $employeeId, 'start' => $start, 'end' => $end]);
        $vacations = $stmt->fetchAll();

        // Ünnepnapok
        $stmt = $db->prepare('SELECT date, name FROM holidays WHERE date >= :start AND date <= :end');
        $stmt->execute(['start' => $start, 'end' => $end]);
        $holidays = $stmt->fetchAll();

        $this->json(['schedules' => $schedules, 'vacations' => $vacations, 'holidays' => $holidays]);
    }

    /**
     * API: Havi összesítő minden dolgozóra
     */
    public function apiSummary(): void
    {
        Middleware::auth();
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        $firstDay = sprintf('%04d-%02d-01', $year, $month);
        $lastDay = date('Y-m-t', strtotime($firstDay));

        $db = \App\Core\Database::getInstance();

        // Dolgozók
        $employees = $db->query('SELECT id, name, vacation_days_total FROM employees WHERE is_active = 1 ORDER BY name')->fetchAll();

        // Ünnepnapok a hónapban
        $hStmt = $db->prepare('SELECT date FROM holidays WHERE date >= :s AND date <= :e');
        $hStmt->execute(['s' => $firstDay, 'e' => $lastDay]);
        $holidayDates = array_column($hStmt->fetchAll(), 'date');

        // Hónap napjainak száma (ünnep nélkül)
        $daysInMonth = (int)date('t', strtotime($firstDay));

        $result = [];
        foreach ($employees as $emp) {
            $eid = $emp['id'];

            // Munkanapok (beosztások száma - egyedi napok)
            $stmt = $db->prepare('SELECT COUNT(DISTINCT work_date) FROM schedules WHERE employee_id = :e AND work_date >= :s AND work_date <= :end');
            $stmt->execute(['e' => $eid, 's' => $firstDay, 'end' => $lastDay]);
            $workDays = (int)$stmt->fetchColumn();

            // Szabadság napok ebben a hónapban
            $stmt = $db->prepare(
                "SELECT date_from, date_to FROM vacation_requests WHERE employee_id = :e AND status = 'approved' AND date_from <= :end AND date_to >= :s"
            );
            $stmt->execute(['e' => $eid, 's' => $firstDay, 'end' => $lastDay]);
            $vacDays = 0;
            foreach ($stmt->fetchAll() as $v) {
                $from = max(strtotime($v['date_from']), strtotime($firstDay));
                $to = min(strtotime($v['date_to']), strtotime($lastDay));
                $vacDays += (int)(($to - $from) / 86400) + 1;
            }

            // Éves összesített kivett szabadság
            $yearStart = $year . '-01-01';
            $yearEnd = $year . '-12-31';
            $stmt = $db->prepare(
                "SELECT date_from, date_to FROM vacation_requests WHERE employee_id = :e AND status = 'approved' AND date_from <= :end AND date_to >= :s"
            );
            $stmt->execute(['e' => $eid, 's' => $yearStart, 'end' => $yearEnd]);
            $yearlyVacUsed = 0;
            foreach ($stmt->fetchAll() as $v) {
                $from = max(strtotime($v['date_from']), strtotime($yearStart));
                $to = min(strtotime($v['date_to']), strtotime($yearEnd));
                $yearlyVacUsed += (int)(($to - $from) / 86400) + 1;
            }

            // Szabadnapok = napok - munkanapok - szabadság - ünnepnapok
            $holidaysInMonth = 0;
            foreach ($holidayDates as $hd) { $holidaysInMonth++; }
            $freeDays = $daysInMonth - $workDays - $vacDays - $holidaysInMonth;
            if ($freeDays < 0) $freeDays = 0;

            // Bérpapír elérhetőség
            $stmt = $db->prepare('SELECT file_path FROM payslips WHERE employee_id = :e AND year = :y AND month = :m');
            $stmt->execute(['e' => $eid, 'y' => $year, 'm' => $month]);
            $payslip = $stmt->fetchColumn();

            $result[] = [
                'id'               => $eid,
                'name'             => $emp['name'],
                'work_days'        => $workDays,
                'vacation_days'    => $vacDays,
                'free_days'        => $freeDays,
                'yearly_total'     => (int)$emp['vacation_days_total'],
                'yearly_used'      => $yearlyVacUsed,
                'yearly_remaining' => (int)$emp['vacation_days_total'] - $yearlyVacUsed,
                'payslip_url'      => $payslip ? base_url($payslip) : null,
            ];
        }

        $this->json(['employees' => $result, 'days_in_month' => $daysInMonth, 'holidays_count' => count($holidayDates)]);
    }

    /**
     * API: Esemenyek lekerese FullCalendar-hoz (GET)
     */
    public function apiEvents(): void
    {
        Middleware::tabPermission('beosztas', 'view');

        $storeId = (int) ($_GET['store_id'] ?? 0);
        $start   = $_GET['start'] ?? '';
        $end     = $_GET['end'] ?? '';

        if (!$storeId || !$start || !$end) {
            $this->json(['error' => 'Hianyzo parameterek.'], 400);
            return;
        }

        // Beosztas esemenyek
        $schedules = Schedule::getByDateRange($storeId, $start, $end);

        // Szinkodok generalasa dolgozokhoz
        $employeeColors = [];
        $colorPalette = [
            '#3B82F6', '#10B981', '#8B5CF6', '#F59E0B', '#EC4899',
            '#06B6D4', '#84CC16', '#F97316', '#6366F1', '#14B8A6',
            '#E11D48', '#7C3AED', '#0EA5E9', '#65A30D', '#D946EF',
        ];
        $colorIndex = 0;

        $events = [];
        foreach ($schedules as $s) {
            $empId = (int) $s['employee_id'];
            if (!isset($employeeColors[$empId])) {
                $employeeColors[$empId] = $colorPalette[$colorIndex % count($colorPalette)];
                $colorIndex++;
            }

            $title = $s['employee_name'];
            if (!empty($s['shift_start']) && !empty($s['shift_end'])) {
                $title .= ' (' . substr($s['shift_start'], 0, 5) . '-' . substr($s['shift_end'], 0, 5) . ')';
            }

            $events[] = [
                'id'              => (int) $s['id'],
                'title'           => $title,
                'start'           => $s['work_date'],
                'backgroundColor' => $employeeColors[$empId],
                'borderColor'     => $employeeColors[$empId],
                'editable'        => true,
                'extendedProps'   => [
                    'type'        => 'schedule',
                    'employee_id' => $empId,
                    'store_id'    => (int) $s['store_id'],
                    'shift_start' => $s['shift_start'],
                    'shift_end'   => $s['shift_end'],
                ],
            ];
        }

        // Jovahagyott szabadsagok mint esemenyek (piros/narancs, nem szerkesztheto)
        $vacations = VacationRequest::getApprovedForDateRange($start, $end);
        foreach ($vacations as $v) {
            // Szabadsag tobb napos lehet, minden napra kulon esemeny
            $dateFrom = new \DateTime($v['date_from']);
            $dateTo = new \DateTime($v['date_to']);
            $dateTo->modify('+1 day'); // inkluziv

            $period = new \DatePeriod($dateFrom, new \DateInterval('P1D'), $dateTo);
            foreach ($period as $day) {
                $dayStr = $day->format('Y-m-d');
                if ($dayStr >= $start && $dayStr <= $end) {
                    $events[] = [
                        'id'              => 'vacation_' . $v['id'] . '_' . $dayStr,
                        'title'           => $v['employee_name'] . ' - Szabadsag',
                        'start'           => $dayStr,
                        'backgroundColor' => '#EF4444',
                        'borderColor'     => '#DC2626',
                        'editable'        => false,
                        'extendedProps'   => [
                            'type'        => 'vacation',
                            'employee_id' => (int) $v['employee_id'],
                            'vacation_id' => (int) $v['id'],
                        ],
                    ];
                }
            }
        }

        $this->json($events);
    }

    /**
     * API: Uj beosztas letrehozasa (POST)
     */
    public function apiStore(): void
    {
        Middleware::tabPermission('beosztas', 'edit');
        Middleware::verifyCsrf();

        $input = json_decode(file_get_contents('php://input'), true);

        $employeeId = (int) ($input['employee_id'] ?? 0);
        $storeId    = (int) ($input['store_id'] ?? 0);
        $workDate   = $input['work_date'] ?? '';
        $shiftStart = $input['shift_start'] ?? '09:00';
        $shiftEnd   = $input['shift_end'] ?? '17:00';

        if (!$employeeId || !$storeId || !$workDate) {
            $this->json(['success' => false, 'error' => 'Hianyzo parameterek.'], 400);
            return;
        }

        // Ünnepnap ellenőrzés
        $db = \App\Core\Database::getInstance();
        $hStmt = $db->prepare('SELECT name FROM holidays WHERE date = :d');
        $hStmt->execute(['d' => $workDate]);
        $holiday = $hStmt->fetchColumn();
        if ($holiday) {
            $this->json(['success' => false, 'error' => 'Ünnepnap: ' . $holiday . '! Nem osztható be senki.'], 409);
            return;
        }

        // Ellenorzes: a dolgozo szabadsagon van-e aznap?
        if (VacationRequest::isEmployeeOnVacation($employeeId, $workDate)) {
            $this->json([
                'success' => false,
                'error'   => 'A dolgozo szabadsagon van ezen a napon! Nem oszthato be.',
            ], 409);
            return;
        }

        $id = Schedule::create([
            'store_id'    => $storeId,
            'employee_id' => $employeeId,
            'work_date'   => $workDate,
            'shift_start' => $shiftStart,
            'shift_end'   => $shiftEnd,
            'created_by'  => Auth::id(),
        ]);

        // Ha jóváhagyott hónap -> modified státusz
        $this->markMonthModifiedIfApproved($storeId, $workDate);

        $this->json(['success' => true]);
    }

    /**
     * API: Beosztas torlese (POST)
     */
    public function apiDelete(string $id): void
    {
        Middleware::tabPermission('beosztas', 'edit');
        Middleware::verifyCsrf();

        $schedule = Schedule::find((int) $id);
        if (!$schedule) {
            $this->json(['success' => false, 'error' => 'Beosztas nem talalhato.'], 404);
            return;
        }

        $this->markMonthModifiedIfApproved((int)$schedule['store_id'], $schedule['work_date']);
        Schedule::delete((int) $id);

        $this->json(['success' => true]);
    }

    /**
     * API: Beosztas athelyezese masik napra (POST, drag & drop)
     */
    public function apiMove(string $id): void
    {
        Middleware::tabPermission('beosztas', 'edit');
        Middleware::verifyCsrf();

        $input = json_decode(file_get_contents('php://input'), true);
        $newDate = $input['new_date'] ?? '';

        if (!$newDate) {
            $this->json(['success' => false, 'error' => 'Hianyzo uj datum.'], 400);
            return;
        }

        $schedule = Schedule::find((int) $id);
        if (!$schedule) {
            $this->json(['success' => false, 'error' => 'Beosztas nem talalhato.'], 404);
            return;
        }

        // Ellenorzes: szabadsag az uj napon
        if (VacationRequest::isEmployeeOnVacation((int) $schedule['employee_id'], $newDate)) {
            $this->json([
                'success' => false,
                'error'   => 'A dolgozo szabadsagon van az uj napon! Nem athelyezheto.',
            ], 409);
            return;
        }

        Schedule::move((int) $id, $newDate);

        $this->json(['success' => true]);
    }

    /**
     * API: Havi beosztás státusz lekérdezése
     */
    public function apiStatus(): void
    {
        Middleware::auth();
        $storeId = (int)($_GET['store_id'] ?? 0);
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM schedule_approvals WHERE store_id = :s AND year = :y AND month = :m');
        $stmt->execute(['s' => $storeId, 'y' => $year, 'm' => $month]);
        $approval = $stmt->fetch();

        $this->json([
            'status' => $approval ? $approval['status'] : 'draft',
            'approved_by' => $approval['approved_by'] ?? null,
            'approved_at' => $approval['approved_at'] ?? null,
            'modified_by' => $approval['modified_by'] ?? null,
            'modify_reason' => $approval['modify_reason'] ?? null,
        ]);
    }

    /**
     * API: Havi beosztás jóváhagyása (tulajdonos)
     */
    public function apiApprove(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $input = json_decode(file_get_contents('php://input'), true);
        $storeId = (int)($input['store_id'] ?? 0);
        $year = (int)($input['year'] ?? 0);
        $month = (int)($input['month'] ?? 0);

        if (!$storeId || !$year || !$month) {
            $this->json(['success' => false, 'error' => 'Hiányzó paraméterek.'], 400);
            return;
        }

        $db = \App\Core\Database::getInstance();
        $db->prepare(
            "INSERT INTO schedule_approvals (store_id, year, month, status, approved_by, approved_at)
             VALUES (:s, :y, :m, 'approved', :u, NOW())
             ON DUPLICATE KEY UPDATE status = 'approved', approved_by = :u2, approved_at = NOW(), modified_by = NULL, modify_reason = NULL"
        )->execute(['s' => $storeId, 'y' => $year, 'm' => $month, 'u' => Auth::id(), 'u2' => Auth::id()]);

        $this->json(['success' => true]);
    }

    /**
     * API: Módosítás kérelem jóváhagyott beosztáshoz
     */
    public function apiRequestModify(): void
    {
        Middleware::auth();
        Middleware::verifyCsrf();

        $input = json_decode(file_get_contents('php://input'), true);
        $storeId = (int)($input['store_id'] ?? 0);
        $year = (int)($input['year'] ?? 0);
        $month = (int)($input['month'] ?? 0);
        $reason = trim($input['reason'] ?? '');

        $db = \App\Core\Database::getInstance();
        $db->prepare(
            "UPDATE schedule_approvals SET status = 'modified', modified_by = :u, modified_at = NOW(), modify_reason = :r
             WHERE store_id = :s AND year = :y AND month = :m AND status = 'approved'"
        )->execute(['u' => Auth::id(), 'r' => $reason, 's' => $storeId, 'y' => $year, 'm' => $month]);

        $this->json(['success' => true]);
    }

    private function markMonthModifiedIfApproved(int $storeId, string $date): void
    {
        $y = (int)date('Y', strtotime($date));
        $m = (int)date('n', strtotime($date));
        $db = \App\Core\Database::getInstance();
        $db->prepare(
            "UPDATE schedule_approvals SET status = 'modified', modified_by = :u, modified_at = NOW()
             WHERE store_id = :s AND year = :y AND month = :m AND status = 'approved'"
        )->execute(['u' => Auth::id(), 's' => $storeId, 'y' => $y, 'm' => $m]);
    }

    /**
     * JSON valasz kuldese
     */
    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
