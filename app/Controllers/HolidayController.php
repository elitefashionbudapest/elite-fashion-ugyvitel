<?php

namespace App\Controllers;

use App\Core\{Middleware, AuditLog, Database};

class HolidayController
{
    public function index(): void
    {
        Middleware::owner();

        $db = Database::getInstance();
        $year = (int)($_GET['year'] ?? date('Y'));

        $holidays = $db->prepare('SELECT * FROM holidays WHERE YEAR(date) = :y ORDER BY date');
        $holidays->execute(['y' => $year]);

        $years = $db->query('SELECT DISTINCT YEAR(date) as y FROM holidays UNION SELECT YEAR(CURDATE()) UNION SELECT YEAR(CURDATE())+1 ORDER BY y DESC')->fetchAll();

        view('layouts/app', [
            'content' => 'holidays/index',
            'data' => [
                'pageTitle' => 'Ünnepnapok',
                'activeTab' => 'settings',
                'holidays'  => $holidays->fetchAll(),
                'year'      => $year,
                'years'     => array_column($years, 'y'),
            ]
        ]);
    }

    public function store(): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $date = $_POST['date'] ?? '';
        $name = trim($_POST['name'] ?? '');

        if (empty($date) || empty($name)) {
            save_old_input();
            set_flash('error', 'A dátum és a név kötelező.');
            redirect('/holidays');
        }

        $db = Database::getInstance();
        try {
            $db->prepare('INSERT INTO holidays (date, name) VALUES (:d, :n)')->execute(['d' => $date, 'n' => $name]);
            AuditLog::log('create', 'holidays', null, null, ['date' => $date, 'name' => $name]);
            set_flash('success', 'Ünnepnap hozzáadva: ' . $name);
        } catch (\Exception $e) {
            set_flash('error', 'Ez a dátum már szerepel az ünnepnapok között.');
        }

        redirect('/holidays?year=' . date('Y', strtotime($date)));
    }

    public function destroy(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $db = Database::getInstance();
        $holiday = $db->prepare('SELECT * FROM holidays WHERE id = :id');
        $holiday->execute(['id' => (int)$id]);
        $holiday = $holiday->fetch();

        if ($holiday) {
            $db->prepare('DELETE FROM holidays WHERE id = :id')->execute(['id' => (int)$id]);
            AuditLog::log('delete', 'holidays', (int)$id, $holiday, null);
            set_flash('success', 'Ünnepnap törölve.');
        }

        redirect('/holidays');
    }
}
