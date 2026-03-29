<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware, Database};

class TaskController
{
    // A rendszer indulási dátuma - ettől a naptól kezdődik a könyvelés
    private const START_DATE = '2026-03-28';

    /**
     * API: Napi feladatok ellenőrzése (mai + tegnapi hiányok)
     */
    public function apiCheck(): void
    {
        Middleware::auth();

        $db = Database::getInstance();
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $tasks = [];

        if (Auth::isOwner()) {
            $stores = $db->query('SELECT id, name FROM stores ORDER BY name')->fetchAll();
            foreach ($stores as $store) {
                $tasks = array_merge($tasks, $this->checkStoreDay($db, $store, $today, 'Ma'));
                // Tegnapit csak akkor kérjük, ha tegnap >= kezdődátum
                if ($yesterday >= self::START_DATE) {
                    $tasks = array_merge($tasks, $this->checkStoreDay($db, $store, $yesterday, 'Tegnap'));
                }
            }

            // Módosított beosztások jóváhagyásra várnak
            $modifiedSchedules = $db->query(
                "SELECT sa.*, s.name as store_name FROM schedule_approvals sa
                 JOIN stores s ON sa.store_id = s.id
                 WHERE sa.status = 'modified' ORDER BY sa.modified_at DESC"
            )->fetchAll();
            foreach ($modifiedSchedules as $ms) {
                $tasks[] = [
                    'id'       => 'schedule_approve_' . $ms['store_id'] . '_' . $ms['year'] . '_' . $ms['month'],
                    'text'     => 'Beosztás módosítás elfogadása — ' . $ms['store_name'] . ' (' . $ms['year'] . '. ' . $ms['month'] . '. hó)',
                    'done'     => false,
                    'overdue'  => true,
                    'link'     => '/schedule',
                    'icon'     => 'fa-calendar-check',
                    'date'     => date('Y-m-d'),
                ];
            }

            // Kártyás forgalom beérkezés emlékeztető
            // Hétfőn: péntek-vasárnap, más napon: tegnap
            // Ünnepnapokon nincs kártyás forgalom → kihagyjuk
            $dayOfWeek = (int)date('N'); // 1=hétfő, 7=vasárnap
            if ($dayOfWeek === 1) {
                $cardDateFrom = date('Y-m-d', strtotime('-3 days'));
                $cardDateTo = date('Y-m-d', strtotime('-1 day'));
                $cardLabel = 'Hétvégi (pé-szo-vas) kártyás forgalom beérkezés';
            } else {
                $cardDateFrom = $yesterday;
                $cardDateTo = $yesterday;
                $cardLabel = 'Tegnapi kártyás forgalom beérkezés';
            }

            if ($cardDateFrom >= self::START_DATE) {
                // Ünnepnapok az időszakban
                $stmt = $db->prepare('SELECT holiday_date FROM holidays WHERE holiday_date BETWEEN :df AND :dt');
                $stmt->execute(['df' => $cardDateFrom, 'dt' => $cardDateTo]);
                $holidays = array_column($stmt->fetchAll(), 'holiday_date');

                // Van-e legalább 1 nem ünnepnap az időszakban?
                $hasWorkDay = false;
                $d = new \DateTime($cardDateFrom);
                $end = new \DateTime($cardDateTo);
                while ($d <= $end) {
                    if (!in_array($d->format('Y-m-d'), $holidays)) {
                        $hasWorkDay = true;
                        break;
                    }
                    $d->modify('+1 day');
                }

                if ($hasWorkDay) {
                    $banks = $db->query('SELECT id, name FROM banks WHERE is_active = 1 ORDER BY name')->fetchAll();
                    foreach ($banks as $bank) {
                        $stmt = $db->prepare(
                            "SELECT COUNT(*) FROM bank_transactions
                             WHERE bank_id = :bank_id AND type = 'kartya_beerkezes'
                             AND date_from = :df AND date_to = :dt"
                        );
                        $stmt->execute(['bank_id' => $bank['id'], 'df' => $cardDateFrom, 'dt' => $cardDateTo]);
                        $hasCard = (int)$stmt->fetchColumn() > 0;

                        $tasks[] = [
                            'id'       => "card_income_{$bank['id']}_{$cardDateFrom}_{$cardDateTo}",
                            'text'     => "{$cardLabel} — {$bank['name']}",
                            'done'     => $hasCard,
                            'overdue'  => !$hasCard,
                            'link'     => '/bank-transactions/card/create',
                            'icon'     => 'fa-credit-card',
                            'date'     => $today,
                        ];
                    }
                }
            }

            // Bankszámlakivonat feltöltés emlékeztető (hónap 5. napjától az előző hónapra)
            if ((int)date('j') >= 5) {
                $prevYear = (int)date('Y', strtotime('-1 month'));
                $prevMonth = (int)date('m', strtotime('-1 month'));
                $allBanks = $db->query('SELECT id, name FROM banks WHERE is_active = 1 AND is_loan = 0 ORDER BY name')->fetchAll();
                foreach ($allBanks as $bank) {
                    $stmt = $db->prepare("SELECT COUNT(*) FROM bank_statements WHERE bank_id = :bid AND year = :y AND month = :m");
                    $stmt->execute(['bid' => $bank['id'], 'y' => $prevYear, 'm' => $prevMonth]);
                    $hasStatement = (int)$stmt->fetchColumn() > 0;

                    if (!$hasStatement) {
                        $monthNames = ['','jan.','feb.','márc.','ápr.','máj.','jún.','júl.','aug.','szept.','okt.','nov.','dec.'];
                        $tasks[] = [
                            'id'       => "statement_{$bank['id']}_{$prevYear}_{$prevMonth}",
                            'text'     => "Bankszámlakivonat feltöltése — {$bank['name']} ({$prevYear}. {$monthNames[$prevMonth]})",
                            'done'     => false,
                            'overdue'  => true,
                            'link'     => '/accounting',
                            'icon'     => 'fa-file-lines',
                            'date'     => $today,
                        ];
                    }
                }
            }
        } else {
            $storeId = Auth::storeId();
            if ($storeId) {
                $store = $db->prepare('SELECT id, name FROM stores WHERE id = :id');
                $store->execute(['id' => $storeId]);
                $store = $store->fetch();
                if ($store) {
                    $tasks = array_merge($tasks, $this->checkStoreDay($db, $store, $today, 'Ma'));
                    if ($yesterday >= self::START_DATE) {
                        $tasks = array_merge($tasks, $this->checkStoreDay($db, $store, $yesterday, 'Tegnap'));
                    }
                }
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'tasks' => $tasks,
            'count' => count(array_filter($tasks, fn($t) => !$t['done'])),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function checkStoreDay(\PDO $db, array $store, string $date, string $label): array
    {
        $tasks = [];
        $storeId = $store['id'];
        $storeName = $store['name'];
        $dayLabel = $label === 'Ma' ? 'Mai' : 'Tegnapi';
        $isOverdue = $label === 'Tegnap';

        // Napi készpénz forgalom
        $stmt = $db->prepare("SELECT COUNT(*) FROM financial_records WHERE store_id = :s AND record_date = :d AND purpose = 'napi_keszpenz'");
        $stmt->execute(['s' => $storeId, 'd' => $date]);
        $hasKeszpenz = (int)$stmt->fetchColumn() > 0;

        $tasks[] = [
            'id'       => "keszpenz_{$storeId}_{$date}",
            'text'     => "{$dayLabel} készpénz forgalom — {$storeName}",
            'done'     => $hasKeszpenz,
            'overdue'  => $isOverdue && !$hasKeszpenz,
            'link'     => '/finance/create',
            'icon'     => 'fa-money-bill',
            'date'     => $date,
        ];

        // Napi bankkártya forgalom
        $stmt = $db->prepare("SELECT COUNT(*) FROM financial_records WHERE store_id = :s AND record_date = :d AND purpose = 'napi_bankkartya'");
        $stmt->execute(['s' => $storeId, 'd' => $date]);
        $hasBankkartya = (int)$stmt->fetchColumn() > 0;

        $tasks[] = [
            'id'       => "bankkartya_{$storeId}_{$date}",
            'text'     => "{$dayLabel} bankkártya forgalom — {$storeName}",
            'done'     => $hasBankkartya,
            'overdue'  => $isOverdue && !$hasBankkartya,
            'link'     => '/finance/create',
            'icon'     => 'fa-credit-card',
            'date'     => $date,
        ];

        // Kassza nyitó (csak mai napra)
        if ($label === 'Ma') {
            $stmt = $db->prepare("SELECT COUNT(*) FROM financial_records WHERE store_id = :s AND record_date = :d AND purpose = 'kassza_nyito'");
            $stmt->execute(['s' => $storeId, 'd' => $date]);
            $hasKassza = (int)$stmt->fetchColumn() > 0;

            $tasks[] = [
                'id'       => "kassza_{$storeId}_{$date}",
                'text'     => "Mai kassza nyitó — {$storeName}",
                'done'     => $hasKassza,
                'overdue'  => false,
                'link'     => '/finance/create',
                'icon'     => 'fa-cash-register',
                'date'     => $date,
            ];
        }

        // Értékelés
        $stmt = $db->prepare("SELECT COUNT(*) FROM evaluations WHERE store_id = :s AND record_date = :d");
        $stmt->execute(['s' => $storeId, 'd' => $date]);
        $hasEval = (int)$stmt->fetchColumn() > 0;

        $tasks[] = [
            'id'       => "ertekeles_{$storeId}_{$date}",
            'text'     => "{$dayLabel} értékelés — {$storeName}",
            'done'     => $hasEval,
            'overdue'  => $isOverdue && !$hasEval,
            'link'     => '/evaluations/create',
            'icon'     => 'fa-star',
            'date'     => $date,
        ];

        // Selejt napi érték (csak ha volt selejt aznap)
        $stmt = $db->prepare("SELECT COUNT(*) FROM defect_items WHERE store_id = :s AND DATE(scanned_at) = :d");
        $stmt->execute(['s' => $storeId, 'd' => $date]);
        $hasDefects = (int)$stmt->fetchColumn() > 0;

        if ($hasDefects) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM defect_daily_values WHERE store_id = :s AND value_date = :d");
            $stmt->execute(['s' => $storeId, 'd' => $date]);
            $hasDailyValue = (int)$stmt->fetchColumn() > 0;

            $tasks[] = [
                'id'       => "selejt_ertek_{$storeId}_{$date}",
                'text'     => "{$dayLabel} selejt összérték — {$storeName}",
                'done'     => $hasDailyValue,
                'overdue'  => $isOverdue && !$hasDailyValue,
                'link'     => '/defects',
                'icon'     => 'fa-coins',
                'date'     => $date,
            ];
        }

        return $tasks;
    }
}
