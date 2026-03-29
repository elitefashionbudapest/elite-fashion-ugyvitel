<?php

/**
 * Elite Fashion Ügyviteli Rendszer - Front Controller
 */

// Hibakezelés (mindig engedélyezve induláskor, .env után finomítva)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Karakter kódolás + időzóna
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Budapest');

// Biztonsági fejlécek
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(self), microphone=()');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Composer autoloader
$autoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloader)) {
    require $autoloader;
} else {
    // Fallback autoloader ha nincs Composer
    spl_autoload_register(function (string $class) {
        $prefix = 'App\\';
        $baseDir = __DIR__ . '/../app/';

        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    });

    // Helpers betöltése
    require __DIR__ . '/../app/Core/Helpers.php';
}

// .env fájl betöltése (egyszerű megoldás)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!getenv($key)) {
            putenv("{$key}={$value}");
        }
    }
}

// Config betöltés
$appConfig = require __DIR__ . '/../config/app.php';

// Hibajelzés
if ($appConfig['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Session indítás
App\Core\Session::start();

// Remember me cookie-ból bejelentkezés
App\Core\Auth::loginFromCookie();

// Router
$router = new App\Core\Router();

// ==========================================
// ROUTE DEFINÍCIÓK
// ==========================================

// Auth
$router->get('/login', [App\Controllers\AuthController::class, 'loginForm']);
$router->post('/login', [App\Controllers\AuthController::class, 'login']);
$router->post('/logout', [App\Controllers\AuthController::class, 'logout']);

// Dashboard
$router->get('/', [App\Controllers\DashboardController::class, 'index']);

// Boltok
$router->get('/stores', [App\Controllers\StoreController::class, 'index']);
$router->get('/stores/create', [App\Controllers\StoreController::class, 'create']);
$router->post('/stores', [App\Controllers\StoreController::class, 'store']);
$router->get('/stores/{id}/edit', [App\Controllers\StoreController::class, 'edit']);
$router->post('/stores/{id}', [App\Controllers\StoreController::class, 'update']);
$router->post('/stores/{id}/delete', [App\Controllers\StoreController::class, 'destroy']);

// Felhasználók (login fiókok)
$router->get('/users', [App\Controllers\UserController::class, 'index']);
$router->get('/users/create', [App\Controllers\UserController::class, 'create']);
$router->post('/users', [App\Controllers\UserController::class, 'store']);
$router->get('/users/{id}/edit', [App\Controllers\UserController::class, 'edit']);
$router->post('/users/{id}', [App\Controllers\UserController::class, 'update']);
$router->post('/users/{id}/delete', [App\Controllers\UserController::class, 'destroy']);

// Dolgozók
$router->get('/employees', [App\Controllers\EmployeeController::class, 'index']);
$router->get('/employees/create', [App\Controllers\EmployeeController::class, 'create']);
$router->post('/employees', [App\Controllers\EmployeeController::class, 'store']);
$router->get('/employees/{id}/edit', [App\Controllers\EmployeeController::class, 'edit']);
$router->post('/employees/{id}', [App\Controllers\EmployeeController::class, 'update']);
$router->post('/employees/{id}/delete', [App\Controllers\EmployeeController::class, 'destroy']);

// Beállítások (tab jogosultságok)
$router->get('/settings/permissions', [App\Controllers\SettingsController::class, 'permissions']);
$router->post('/settings/permissions', [App\Controllers\SettingsController::class, 'savePermissions']);
$router->get('/settings/company', [App\Controllers\CompanySettingsController::class, 'index']);
$router->post('/settings/company', [App\Controllers\CompanySettingsController::class, 'save']);
$router->get('/settings/company/test-imap', [App\Controllers\CompanySettingsController::class, 'testImap']);
$router->get('/settings/company/test-api', [App\Controllers\CompanySettingsController::class, 'testApi']);

// Könyvelés
$router->get('/finance', [App\Controllers\FinanceController::class, 'index']);
$router->get('/finance/create', [App\Controllers\FinanceController::class, 'create']);
$router->post('/finance', [App\Controllers\FinanceController::class, 'store']);
$router->get('/finance/{id}/edit', [App\Controllers\FinanceController::class, 'edit']);
$router->post('/finance/{id}', [App\Controllers\FinanceController::class, 'update']);
$router->post('/finance/{id}/delete', [App\Controllers\FinanceController::class, 'destroy']);
$router->get('/finance/summary', [App\Controllers\FinanceController::class, 'summary']);
$router->get('/finance/check-duplicate', [App\Controllers\FinanceController::class, 'checkDuplicate']);

// Saját Fizetés
$router->get('/salary', [App\Controllers\SalaryController::class, 'index']);
$router->get('/salary/create', [App\Controllers\SalaryController::class, 'create']);
$router->post('/salary', [App\Controllers\SalaryController::class, 'store']);
$router->post('/salary/{id}/delete', [App\Controllers\SalaryController::class, 'destroy']);

// Értékelések
$router->get('/evaluations', [App\Controllers\EvaluationController::class, 'index']);
$router->get('/evaluations/create', [App\Controllers\EvaluationController::class, 'create']);
$router->post('/evaluations', [App\Controllers\EvaluationController::class, 'store']);
$router->post('/evaluations/{id}/delete', [App\Controllers\EvaluationController::class, 'destroy']);

// Szabadság
$router->get('/vacation', [App\Controllers\VacationController::class, 'index']);
$router->get('/vacation/create', [App\Controllers\VacationController::class, 'create']);
$router->post('/vacation', [App\Controllers\VacationController::class, 'store']);
$router->post('/vacation/{id}/approve', [App\Controllers\VacationController::class, 'approve']);
$router->post('/vacation/{id}/reject', [App\Controllers\VacationController::class, 'reject']);
$router->post('/vacation/{id}/delete', [App\Controllers\VacationController::class, 'destroy']);

// Beosztás
$router->get('/schedule', [App\Controllers\ScheduleController::class, 'index']);
$router->get('/schedule/api/employee', [App\Controllers\ScheduleController::class, 'apiEmployee']);
$router->get('/schedule/api/summary', [App\Controllers\ScheduleController::class, 'apiSummary']);
$router->get('/schedule/api', [App\Controllers\ScheduleController::class, 'apiEvents']);
$router->post('/schedule/api', [App\Controllers\ScheduleController::class, 'apiStore']);
$router->post('/schedule/api/{id}/delete', [App\Controllers\ScheduleController::class, 'apiDelete']);
$router->post('/schedule/api/{id}/move', [App\Controllers\ScheduleController::class, 'apiMove']);
$router->get('/schedule/api/status', [App\Controllers\ScheduleController::class, 'apiStatus']);
$router->post('/schedule/api/approve', [App\Controllers\ScheduleController::class, 'apiApprove']);
$router->post('/schedule/api/request-modify', [App\Controllers\ScheduleController::class, 'apiRequestModify']);

// Selejt
$router->get('/defects', [App\Controllers\DefectController::class, 'index']);
$router->post('/defects/scan', [App\Controllers\DefectController::class, 'scan']);
$router->post('/defects/{id}/delete', [App\Controllers\DefectController::class, 'destroy']);
$router->get('/defects/export', [App\Controllers\DefectController::class, 'export']);
$router->post('/defects/daily-value', [App\Controllers\DefectController::class, 'saveDailyValue']);
$router->get('/defects/check-daily-value', [App\Controllers\DefectController::class, 'checkDailyValue']);

// Chat
$router->get('/chat', [App\Controllers\ChatController::class, 'index']);
$router->get('/chat/messages', [App\Controllers\ChatController::class, 'getMessages']);
$router->post('/chat/send', [App\Controllers\ChatController::class, 'send']);
$router->post('/chat/mark-read', [App\Controllers\ChatController::class, 'markRead']);

// Értesítések
$router->get('/notifications', [App\Controllers\NotificationController::class, 'index']);
$router->get('/notifications/api', [App\Controllers\NotificationController::class, 'apiUnread']);
$router->post('/notifications/{id}/read', [App\Controllers\NotificationController::class, 'markRead']);

// Bankszámlák
$router->get('/banks', [App\Controllers\BankController::class, 'index']);
$router->get('/banks/create', [App\Controllers\BankController::class, 'create']);
$router->post('/banks', [App\Controllers\BankController::class, 'store']);
$router->get('/banks/{id}/edit', [App\Controllers\BankController::class, 'edit']);
$router->post('/banks/{id}', [App\Controllers\BankController::class, 'update']);
$router->post('/banks/{id}/delete', [App\Controllers\BankController::class, 'destroy']);

// Bank tranzakciók
$router->get('/bank-transactions', [App\Controllers\BankTransactionController::class, 'index']);
$router->get('/bank-transactions/api/gross', [App\Controllers\BankTransactionController::class, 'apiGross']);
$router->get('/bank-transactions/card/create', [App\Controllers\BankTransactionController::class, 'createCard']);
$router->post('/bank-transactions/card', [App\Controllers\BankTransactionController::class, 'storeCard']);
$router->get('/bank-transactions/provider/create', [App\Controllers\BankTransactionController::class, 'createProvider']);
$router->post('/bank-transactions/provider', [App\Controllers\BankTransactionController::class, 'storeProvider']);
$router->get('/bank-transactions/loan/create', [App\Controllers\BankTransactionController::class, 'createLoan']);
$router->post('/bank-transactions/loan', [App\Controllers\BankTransactionController::class, 'storeLoan']);
$router->get('/bank-transactions/transfer/create', [App\Controllers\BankTransactionController::class, 'createTransfer']);
$router->post('/bank-transactions/transfer', [App\Controllers\BankTransactionController::class, 'storeTransfer']);
$router->post('/bank-transactions/{id}/link-invoice', [App\Controllers\BankTransactionController::class, 'linkInvoice']);
$router->post('/bank-transactions/{id}/delete', [App\Controllers\BankTransactionController::class, 'destroy']);

// Könyvelői dokumentumok
$router->get('/accounting', [App\Controllers\AccountingController::class, 'index']);
$router->post('/accounting/statement', [App\Controllers\AccountingController::class, 'uploadStatement']);
$router->post('/accounting/payslip', [App\Controllers\AccountingController::class, 'uploadPayslip']);
$router->get('/accounting/invoices/download', [App\Controllers\AccountingController::class, 'downloadInvoices']);

// Bejövő számlák
$router->get('/invoices', [App\Controllers\InvoiceController::class, 'index']);
$router->get('/invoices/create', [App\Controllers\InvoiceController::class, 'create']);
$router->post('/invoices', [App\Controllers\InvoiceController::class, 'store']);
$router->post('/invoices/{id}/paid', [App\Controllers\InvoiceController::class, 'markPaid']);
$router->post('/invoices/{id}/unpaid', [App\Controllers\InvoiceController::class, 'markUnpaid']);
$router->post('/invoices/{id}/delete', [App\Controllers\InvoiceController::class, 'destroy']);
$router->get('/invoices/suppliers', [App\Controllers\InvoiceController::class, 'searchSuppliers']);

// Ünnepnapok
$router->get('/holidays', [App\Controllers\HolidayController::class, 'index']);
$router->post('/holidays', [App\Controllers\HolidayController::class, 'store']);
$router->post('/holidays/{id}/delete', [App\Controllers\HolidayController::class, 'destroy']);

// Feladat jelző
$router->get('/tasks/api', [App\Controllers\TaskController::class, 'apiCheck']);

// Audit log
$router->get('/audit', [App\Controllers\AuditController::class, 'index']);

// Adatmentés
$router->get('/backup', [App\Controllers\BackupController::class, 'index']);
$router->post('/backup/create', [App\Controllers\BackupController::class, 'create']);
$router->get('/backup/download/{filename}', [App\Controllers\BackupController::class, 'download']);
$router->post('/backup/delete/{filename}', [App\Controllers\BackupController::class, 'destroy']);

// ==========================================
// DISPATCH
// ==========================================
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
