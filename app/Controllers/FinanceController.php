<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, AuditLog};
use App\Models\{FinancialRecord, Store, Employee, Bank};

class FinanceController
{
    public function index(): void
    {
        Middleware::tabPermission('konyveles', 'view');

        $storeIds = Auth::isStore() ? [Auth::storeId()] : ($_GET['store_ids'] ?? []);
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        $purpose = $_GET['purpose'] ?? null;

        // Kompatibilitás régi store_id paraméterrel
        if (empty($storeIds) && !empty($_GET['store_id'])) {
            $storeIds = [(int)$_GET['store_id']];
        }

        $records = FinancialRecord::allMultiStore(
            $storeIds ?: null,
            $dateFrom ?: null,
            $dateTo ?: null,
            $purpose ?: null
        );

        $stores = Auth::isOwner() ? Store::all() : [];

        view('layouts/app', [
            'content' => 'finance/index',
            'data' => [
                'pageTitle' => 'Könyvelés',
                'activeTab' => 'konyveles',
                'records'   => $records,
                'stores'    => $stores,
                'filters'   => ['store_ids' => array_map('strval', $storeIds), 'date_from' => $dateFrom, 'date_to' => $dateTo, 'purpose' => $purpose],
            ]
        ]);
    }

    public function create(): void
    {
        Middleware::tabPermission('konyveles', 'create');

        $stores = Auth::isOwner() ? Store::all() : [];
        $employees = Employee::allActive();
        $banks = Bank::all();

        view('layouts/app', [
            'content' => 'finance/form',
            'data' => [
                'pageTitle'  => 'Pénzmozgás rögzítés',
                'activeTab'  => 'konyveles',
                'record'     => null,
                'stores'     => $stores,
                'employees'  => $employees,
                'banks'      => $banks,
            ]
        ]);
    }

    public function store(): void
    {
        Middleware::tabPermission('konyveles', 'create');
        Middleware::verifyCsrf();

        $storeId = Auth::isStore() ? Auth::storeId() : (int)($_POST['store_id'] ?? 0);
        $data = [
            'store_id'           => $storeId,
            'recorded_by'        => Auth::id(),
            'record_date'        => $_POST['record_date'] ?? date('Y-m-d'),
            'purpose'            => $_POST['purpose'] ?? '',
            'amount'             => (float)($_POST['amount'] ?? 0),
            'description'        => trim($_POST['description'] ?? '') ?: null,
            'paid_to_employee_id'=> !empty($_POST['paid_to_employee_id']) ? (int)$_POST['paid_to_employee_id'] : null,
            'bank_id'            => !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null,
        ];

        if (empty($data['purpose']) || !isset(FinancialRecord::PURPOSES[$data['purpose']])) {
            save_old_input();
            set_flash('error', 'Válasszon pénzmozgás típust.');
            redirect('/finance/create');
        }

        // Munkabérnél kötelező a dolgozó kiválasztása
        if ($data['purpose'] === 'munkaber' && empty($data['paid_to_employee_id'])) {
            save_old_input();
            set_flash('error', 'Munkabér kifizetésnél válasszon dolgozót!');
            redirect('/finance/create');
        }

        // Bank-os típusoknál kötelező a bank kiválasztása
        if (in_array($data['purpose'], ['befizetes_bankbol', 'bank_kifizetes']) && empty($data['bank_id'])) {
            save_old_input();
            set_flash('error', 'Válasszon bankszámlát!');
            redirect('/finance/create');
        }

        $id = FinancialRecord::create($data);
        AuditLog::log('create', 'financial_records', $id, null, $data);

        if ($data['purpose'] === 'munkaber') {
            set_flash('success', 'Pénzmozgás sikeresen rögzítve. Emlékeztető: írd be a Fizetések menüpontba is!');
            set_flash('success_link', ['url' => base_url('/salary/create'), 'text' => 'Ugrás a Fizetésekhez →']);
        } elseif ($data['purpose'] === 'szamla_kifizetes') {
            set_flash('success', 'Pénzmozgás sikeresen rögzítve. Emlékeztető: jelöld kifizetettnek a Számláknál is!');
            set_flash('success_link', ['url' => base_url('/invoices'), 'text' => 'Ugrás a Számlákhoz →']);
        } elseif ($data['purpose'] === 'selejt_befizetes') {
            // Selejt érték egyezés ellenőrzés
            $sDb = \App\Core\Database::getInstance();
            $stmt = $sDb->prepare("SELECT COALESCE(SUM(total_value), 0) FROM defect_daily_values WHERE store_id = :s AND value_date = :d");
            $stmt->execute(['s' => $storeId, 'd' => $data['record_date']]);
            $selejtErtek = (float)$stmt->fetchColumn();

            if ($selejtErtek > 0 && abs($selejtErtek - $data['amount']) > 1) {
                set_flash('warning', 'Selejt befizetés rögzítve, de az összeg (' . format_money($data['amount']) . ') eltér a napi selejt értéktől (' . format_money($selejtErtek) . ')!');
            } else {
                set_flash('success', 'Selejt befizetés sikeresen rögzítve.');
            }
        } else {
            set_flash('success', 'Pénzmozgás sikeresen rögzítve.');
        }

        redirect('/finance');
    }

    public function edit(string $id): void
    {
        Middleware::tabPermission('konyveles', 'edit');

        $record = FinancialRecord::find((int)$id);
        if (!$record) { redirect('/finance'); }

        if (Auth::isStore() && $record['store_id'] !== Auth::storeId()) { redirect('/finance'); }

        $stores = Auth::isOwner() ? Store::all() : [];
        $employees = Employee::allActive();
        $banks = Bank::all();

        view('layouts/app', [
            'content' => 'finance/form',
            'data' => [
                'pageTitle'  => 'Pénzmozgás szerkesztése',
                'activeTab'  => 'konyveles',
                'record'     => $record,
                'stores'     => $stores,
                'employees'  => $employees,
                'banks'      => $banks,
            ]
        ]);
    }

    public function update(string $id): void
    {
        Middleware::tabPermission('konyveles', 'edit');
        Middleware::verifyCsrf();

        $old = FinancialRecord::find((int)$id);
        if (!$old) { redirect('/finance'); }

        $storeId = Auth::isStore() ? Auth::storeId() : (int)($_POST['store_id'] ?? 0);
        $data = [
            'store_id'           => $storeId,
            'record_date'        => $_POST['record_date'] ?? $old['record_date'],
            'purpose'            => $_POST['purpose'] ?? $old['purpose'],
            'amount'             => (float)($_POST['amount'] ?? 0),
            'description'        => trim($_POST['description'] ?? '') ?: null,
            'paid_to_employee_id'=> !empty($_POST['paid_to_employee_id']) ? (int)$_POST['paid_to_employee_id'] : null,
            'bank_id'            => !empty($_POST['bank_id']) ? (int)$_POST['bank_id'] : null,
        ];

        FinancialRecord::update((int)$id, $data);
        AuditLog::log('update', 'financial_records', (int)$id, $old, $data);
        set_flash('success', 'Pénzmozgás frissítve.');
        redirect('/finance');
    }

    public function destroy(string $id): void
    {
        Middleware::owner();
        Middleware::verifyCsrf();

        $record = FinancialRecord::find((int)$id);
        if ($record) {
            FinancialRecord::delete((int)$id);
            AuditLog::log('delete', 'financial_records', (int)$id, $record, null);
            set_flash('success', 'Pénzmozgás törölve.');
        }
        redirect('/finance');
    }

    public function checkDuplicate(): void
    {
        Middleware::auth();

        $storeId = Auth::isStore() ? Auth::storeId() : (int)($_GET['store_id'] ?? 0);
        $date = $_GET['date'] ?? '';
        $purpose = $_GET['purpose'] ?? '';

        if (!$storeId || !$date || !$purpose) {
            header('Content-Type: application/json');
            echo json_encode(['exists' => false]);
            return;
        }

        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare(
            'SELECT id FROM financial_records WHERE store_id = :store_id AND record_date = :date AND purpose = :purpose LIMIT 1'
        );
        $stmt->execute(['store_id' => $storeId, 'date' => $date, 'purpose' => $purpose]);
        $exists = $stmt->fetch() !== false;

        header('Content-Type: application/json');
        echo json_encode(['exists' => $exists]);
    }

    public function summary(): void
    {
        Middleware::tabPermission('kimutat', 'view');

        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-t');

        $summary = FinancialRecord::summaryByStore($dateFrom, $dateTo);

        $db = \App\Core\Database::getInstance();

        // Össz pénzügyi helyzet: kasszák + bankszámlák + hitelek
        $cashPosition = [];

        // Kasszák boltonként = induló + bevételek - kiadások
        $kasszak = $db->query(
            "SELECT s.name,
                COALESCE(s.opening_cash, 0)
                + COALESCE(SUM(CASE WHEN f.purpose IN ('befizetes_bankbol', 'befizetes_boltbol', 'napi_keszpenz', 'selejt_befizetes') THEN f.amount ELSE 0 END), 0)
                - COALESCE(SUM(CASE WHEN f.purpose IN ('meretre_igazitas', 'tankolas', 'munkaber', 'egyeb_kifizetes', 'szamla_kifizetes', 'bank_kifizetes') THEN f.amount ELSE 0 END), 0)
                as egyenleg
             FROM stores s LEFT JOIN financial_records f ON s.id = f.store_id
             GROUP BY s.id, s.name, s.opening_cash ORDER BY s.name"
        )->fetchAll();

        // Bankszámlák + hitelek egyenleggel
        $banksWithBalance = \App\Models\Bank::allWithBalance();
        $bankAccounts = array_filter($banksWithBalance, fn($b) => !$b['is_loan'] && $b['is_active']);
        $loanAccounts = array_filter($banksWithBalance, fn($b) => $b['is_loan'] && $b['is_active']);

        $totalKassza = array_sum(array_column($kasszak, 'egyenleg'));
        // Bankoknál HUF egyenértéket használjuk a devizáknál
        $totalBank = array_sum(array_column($bankAccounts, 'balance_huf'));
        $totalLoan = array_sum(array_column($loanAccounts, 'balance_huf'));

        // Készpénz összesen = kasszák + bankszámlák (hitelkártya és hitelek NÉLKÜL)
        // Hitelkártya = min_balance < 0 beállítású bankszámla
        $cashBanks = array_filter($bankAccounts, fn($b) => !($b['min_balance'] !== null && $b['min_balance'] < 0));
        $totalCash = $totalKassza + array_sum(array_column($cashBanks, 'balance_huf'));

        // ÁFA számítás (előző hónap)
        $prevMonthFrom = date('Y-m-01', strtotime('-1 month'));
        $prevMonthTo = date('Y-m-t', strtotime('-1 month'));
        $hunMonths = ['','január','február','március','április','május','június','július','augusztus','szeptember','október','november','december'];
        $prevMonthName = date('Y', strtotime('-1 month')) . '. ' . $hunMonths[(int)date('m', strtotime('-1 month'))];

        // Fizetendő ÁFA: előző havi bolti bruttó forgalom ÁFA-ja
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(amount), 0) FROM financial_records
             WHERE purpose IN ('napi_keszpenz', 'napi_bankkartya')
             AND record_date BETWEEN :df AND :dt"
        );
        $stmt->execute(['df' => $prevMonthFrom, 'dt' => $prevMonthTo]);
        $prevRevenueBrutto = (float)$stmt->fetchColumn();
        $prevRevenueNetto = round($prevRevenueBrutto / 1.27);
        $vatPayable = $prevRevenueBrutto - $prevRevenueNetto;

        // Levonható ÁFA: előző havi bejövő számlák ÁFA tartalma (bruttó - nettó)
        $stmt = $db->prepare(
            "SELECT COALESCE(SUM(amount - net_amount), 0) FROM invoices
             WHERE invoice_date BETWEEN :df AND :dt AND currency = 'HUF'"
        );
        $stmt->execute(['df' => $prevMonthFrom, 'dt' => $prevMonthTo]);
        $vatDeductible = (float)$stmt->fetchColumn();

        $vatNet = $vatPayable - $vatDeductible;

        // Fizetések összesítő (szűrt időszak hónapjaira)
        $salaryFrom = date('Y-m', strtotime($dateFrom));
        $salaryTo = date('Y-m', strtotime($dateTo));
        $sfYear = (int)date('Y', strtotime($dateFrom));
        $sfMonth = (int)date('m', strtotime($dateFrom));
        $stYear = (int)date('Y', strtotime($dateTo));
        $stMonth = (int)date('m', strtotime($dateTo));

        // Dolgozói fizetések
        $salaryByEmployee = $db->prepare(
            "SELECT e.name, SUM(sp.amount) as total
             FROM salary_payments sp
             JOIN employees e ON sp.employee_id = e.id
             WHERE (sp.year * 100 + sp.month) BETWEEN :from AND :to
             GROUP BY sp.employee_id, e.name
             ORDER BY total DESC"
        );
        $salaryByEmployee->execute(['from' => $sfYear * 100 + $sfMonth, 'to' => $stYear * 100 + $stMonth]);
        $salaryByEmployee = $salaryByEmployee->fetchAll();

        $salaryTotal = array_sum(array_column($salaryByEmployee, 'total'));

        // Tulajdonosi kifizetések
        $ownerPayments = $db->prepare(
            "SELECT owner_name, SUM(amount) as total
             FROM owner_payments
             WHERE (year * 100 + month) BETWEEN :from AND :to
             GROUP BY owner_name
             ORDER BY total DESC"
        );
        $ownerPayments->execute(['from' => $sfYear * 100 + $sfMonth, 'to' => $stYear * 100 + $stMonth]);
        $ownerPayments = $ownerPayments->fetchAll();

        $ownerTotal = array_sum(array_column($ownerPayments, 'total'));

        // === P&L: Havi eredménykimutatás (aktuális szűrt időszak + előző hónap összehasonlítás) ===
        $plHelper = function(string $from, string $to) use ($db) {
            // Bevétel (nettó = bruttó / 1.27)
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_records WHERE purpose IN ('napi_keszpenz','napi_bankkartya') AND record_date BETWEEN :df AND :dt");
            $stmt->execute(['df' => $from, 'dt' => $to]);
            $revenueBrutto = (float)$stmt->fetchColumn();
            $revenueNetto = round($revenueBrutto / 1.27);

            // Költségek a könyvelésből
            $stmt = $db->prepare("SELECT
                COALESCE(SUM(CASE WHEN purpose = 'munkaber' THEN amount ELSE 0 END), 0) as munkaber,
                COALESCE(SUM(CASE WHEN purpose = 'meretre_igazitas' THEN amount ELSE 0 END), 0) as meretre,
                COALESCE(SUM(CASE WHEN purpose = 'tankolas' THEN amount ELSE 0 END), 0) as tankolas,
                COALESCE(SUM(CASE WHEN purpose = 'egyeb_kifizetes' THEN amount ELSE 0 END), 0) as egyeb,
                COALESCE(SUM(CASE WHEN purpose = 'szamla_kifizetes' THEN amount ELSE 0 END), 0) as szamla
                FROM financial_records WHERE record_date BETWEEN :df AND :dt");
            $stmt->execute(['df' => $from, 'dt' => $to]);
            $costs = $stmt->fetch();

            // Bank jutalék (bruttó - nettó a kártyás beérkezéseknél)
            // Csak akkor számolunk jutalékot ha van rögzített kártyás beérkezés
            $stmt = $db->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total
                FROM bank_transactions WHERE type = 'kartya_beerkezes'
                AND transaction_date BETWEEN :df AND :dt");
            $stmt->execute(['df' => $from, 'dt' => $to]);
            $cardRow = $stmt->fetch();
            $hasCardIncome = (int)($cardRow['cnt'] ?? 0) > 0;
            $netCard = (float)($cardRow['total'] ?? 0);

            // Jutalék = a kártyás beérkezéseknél a bruttó összeg - nettó beérkezett összeg
            // A bruttó összeget a bank_transactions-ból számoljuk (calculateGross)
            $bankJutalek = 0;
            if ($hasCardIncome) {
                $stmt = $db->prepare(
                    "SELECT bt.id FROM bank_transactions bt
                     WHERE bt.type = 'kartya_beerkezes' AND bt.transaction_date BETWEEN :df AND :dt"
                );
                $stmt->execute(['df' => $from, 'dt' => $to]);
                $cardIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                $grossCard = 0;
                foreach ($cardIds as $cid) {
                    $grossCard += \App\Models\BankTransaction::calculateGross((int)$cid);
                }
                $bankJutalek = $grossCard - $netCard;
                if ($bankJutalek < 0) $bankJutalek = 0;
            }

            // Szolgáltatói levonások
            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM bank_transactions WHERE type = 'szolgaltato_levon' AND transaction_date BETWEEN :df AND :dt");
            $stmt->execute(['df' => $from, 'dt' => $to]);
            $szolgaltatok = (float)$stmt->fetchColumn();

            $totalCosts = (float)$costs['munkaber'] + (float)$costs['meretre'] + (float)$costs['tankolas']
                        + (float)$costs['egyeb'] + (float)$costs['szamla'] + $bankJutalek + $szolgaltatok;

            return [
                'revenue_brutto' => $revenueBrutto,
                'revenue_netto'  => $revenueNetto,
                'munkaber'       => (float)$costs['munkaber'],
                'meretre'        => (float)$costs['meretre'],
                'tankolas'       => (float)$costs['tankolas'],
                'egyeb'          => (float)$costs['egyeb'],
                'szamla'         => (float)$costs['szamla'],
                'bank_jutalek'   => $bankJutalek,
                'szolgaltatok'   => $szolgaltatok,
                'costs_total'    => $totalCosts,
                'profit'         => $revenueNetto - $totalCosts,
            ];
        };

        $plCurrent = $plHelper($dateFrom, $dateTo);

        // Előző hónap P&L összehasonlításhoz
        $prevFrom = date('Y-m-d', strtotime($dateFrom . ' -1 month'));
        $prevTo = date('Y-m-d', strtotime($dateTo . ' -1 month'));
        $plPrev = $plHelper($prevFrom, $prevTo);

        // Változás %
        $plChange = [];
        foreach (['revenue_netto', 'costs_total', 'profit'] as $k) {
            $plChange[$k] = $plPrev[$k] != 0 ? round(($plCurrent[$k] - $plPrev[$k]) / abs($plPrev[$k]) * 100, 1) : null;
        }

        // === Pénzforgalmi előrejelzés ===
        // Átlagos havi fix költségek (utolsó 3 hónap)
        $avg3from = date('Y-m-01', strtotime('-3 months'));
        $avg3to = date('Y-m-t', strtotime('-1 month'));
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) / 3 FROM financial_records WHERE purpose IN ('munkaber','meretre_igazitas','tankolas','egyeb_kifizetes','szamla_kifizetes') AND record_date BETWEEN :df AND :dt");
        $stmt->execute(['df' => $avg3from, 'dt' => $avg3to]);
        $avgMonthlyCosts = (float)$stmt->fetchColumn();

        // Szolgáltatói levonások átlag
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) / 3 FROM bank_transactions WHERE type = 'szolgaltato_levon' AND transaction_date BETWEEN :df AND :dt");
        $stmt->execute(['df' => $avg3from, 'dt' => $avg3to]);
        $avgMonthlyProviders = (float)$stmt->fetchColumn();

        // Havi hiteltörlesztés átlag
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) / 3 FROM bank_transactions WHERE type = 'hitel_torlesztes' AND transaction_date BETWEEN :df AND :dt");
        $stmt->execute(['df' => $avg3from, 'dt' => $avg3to]);
        $avgMonthlyLoan = (float)$stmt->fetchColumn();

        // Várható ÁFA (aktuális hónap forgalma alapján)
        $curMonthFrom = date('Y-m-01');
        $curMonthTo = date('Y-m-t');
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_records WHERE purpose IN ('napi_keszpenz','napi_bankkartya') AND record_date BETWEEN :df AND :dt");
        $stmt->execute(['df' => $curMonthFrom, 'dt' => $curMonthTo]);
        $curMonthRevenue = (float)$stmt->fetchColumn();
        $daysPassed = (int)date('j');
        $daysInMonth = (int)date('t');
        $projectedRevenue = $daysPassed > 0 ? round($curMonthRevenue / $daysPassed * $daysInMonth) : 0;
        $projectedVat = round($projectedRevenue / 1.27 * 0.27);

        // Jelenlegi teljes likvid pozíció
        $currentCash = $totalKassza + $totalBank;

        // Várható hónap végi szabad pénz
        $remainingDays = $daysInMonth - $daysPassed;
        $projectedRemainingCosts = $avgMonthlyCosts > 0 ? round($avgMonthlyCosts * ($remainingDays / $daysInMonth)) : 0;
        $freeCashEstimate = $currentCash - $projectedRemainingCosts - $avgMonthlyProviders - $avgMonthlyLoan;

        view('layouts/app', [
            'content' => 'finance/summary',
            'data' => [
                'pageTitle'       => 'Pénzügyi összesítő',
                'activeTab'       => 'kimutat',
                'summary'         => $summary,
                'dateFrom'        => $dateFrom,
                'dateTo'          => $dateTo,
                'kasszak'         => $kasszak,
                'bankAccounts'    => $bankAccounts,
                'loanAccounts'    => $loanAccounts,
                'totalKassza'     => $totalKassza,
                'totalBank'       => $totalBank,
                'totalLoan'       => $totalLoan,
                'totalCash'       => $totalCash,
                'prevMonthName'   => date('Y. F', strtotime('-1 month')),
                'vatPayable'      => $vatPayable,
                'vatDeductible'   => $vatDeductible,
                'vatNet'          => $vatNet,
                'prevRevenueBrutto' => $prevRevenueBrutto,
                'salaryByEmployee'  => $salaryByEmployee,
                'salaryTotal'       => $salaryTotal,
                'ownerPayments'     => $ownerPayments,
                'ownerTotal'        => $ownerTotal,
                'plCurrent'         => $plCurrent,
                'plPrev'            => $plPrev,
                'plChange'          => $plChange,
                'forecast'          => [
                    'avgMonthlyCosts'    => $avgMonthlyCosts,
                    'avgMonthlyProviders'=> $avgMonthlyProviders,
                    'avgMonthlyLoan'     => $avgMonthlyLoan,
                    'projectedRevenue'   => $projectedRevenue,
                    'projectedVat'       => $projectedVat,
                    'currentCash'        => $currentCash,
                    'freeCashEstimate'   => $freeCashEstimate,
                    'daysPassed'         => $daysPassed,
                    'daysInMonth'        => $daysInMonth,
                    'totalLoan'          => $totalLoan,
                ],
            ]
        ]);
    }
}
