<?php
use App\Core\Auth;
use App\Models\TabPermission;

$currentUser = Auth::user();
$isOwner = Auth::isOwner();
$isAccountant = Auth::isAccountant();

if ($isOwner && !TabPermission::hasAnyPermissions($currentUser['id'])) {
    // Tulajdonos jogosultság beállítás nélkül: mindent lát
    $visibleTabs = array_keys(TabPermission::TABS);
} else {
    $visibleTabs = TabPermission::getVisibleTabs($currentUser['id']);
}

// Chat menüpont megtartása

$activeTab = $data['activeTab'] ?? '';
?>
<aside id="sidebar" class="fixed left-0 top-0 h-screen w-64 bg-sidebar flex flex-col px-4 py-4 z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
    <!-- Brand -->
    <div class="mb-6 px-2">
        <h1 class="text-lg font-heading font-bold text-white tracking-tight">Elite Fashion</h1>
        <p class="text-[10px] text-gray-500 font-medium">Ügyviteli Rendszer</p>
    </div>

    <!-- Navigation -->
    <nav class="flex-grow space-y-0.5 overflow-y-auto">
        <?php foreach (TabPermission::TABS as $slug => $tab): ?>
            <?php if (!in_array($slug, $visibleTabs)) continue; ?>
            <?php
                $isActive = ($activeTab === $slug);
                $href = match($slug) {
                    'dashboard'  => '/',
                    'konyveles'  => '/finance',
                    'fizetes'    => '/salary',
                    'ertekeles'  => '/evaluations',
                    'szabadsag'  => '/vacation',
                    'beosztas'   => '/schedule',
                    'szamlak'    => '/invoices',
                    'selejt'     => '/defects',
                    'kimutat'        => '/finance/summary',
                    'konyvelo_docs'  => '/accounting',
                    'chat'           => '/chat',
                    default          => '#',
                };
            ?>
            <a href="<?= base_url($href) ?>"
               class="flex items-center gap-3 px-3 py-2 text-[13px] font-medium rounded-lg transition-colors duration-200
                      <?= $isActive
                          ? 'bg-white/10 text-accent'
                          : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' ?>">
                <i class="fa-solid <?= $tab['icon'] ?> text-sm w-4 text-center"></i>
                <?= $tab['label'] ?>
            </a>
        <?php endforeach; ?>

        <?php $isSuperAdmin = $isOwner && !TabPermission::hasAnyPermissions($currentUser['id']); ?>
        <?php if ($isSuperAdmin): ?>
            <div class="border-t border-white/10 mt-3 pt-3">
                <p class="px-3 text-[9px] text-gray-500 uppercase tracking-widest font-bold mb-1">Kezelés</p>

                <a href="<?= base_url('/stores') ?>"
                   class="flex items-center gap-3 px-3 py-2 text-[13px] font-medium rounded-lg transition-colors duration-200
                          <?= ($activeTab === 'stores') ? 'bg-white/10 text-accent' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' ?>">
                    <i class="fa-solid fa-store text-sm w-4 text-center"></i>
                    Boltok
                </a>

                <a href="<?= base_url('/users') ?>"
                   class="flex items-center gap-3 px-3 py-2 text-[13px] font-medium rounded-lg transition-colors duration-200
                          <?= ($activeTab === 'users') ? 'bg-white/10 text-accent' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' ?>">
                    <i class="fa-solid fa-users-gear text-sm w-4 text-center"></i>
                    Fiókok
                </a>

                <a href="<?= base_url('/employees') ?>"
                   class="flex items-center gap-3 px-3 py-2 text-[13px] font-medium rounded-lg transition-colors duration-200
                          <?= ($activeTab === 'employees') ? 'bg-white/10 text-accent' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' ?>">
                    <i class="fa-solid fa-users text-sm w-4 text-center"></i>
                    Dolgozók
                </a>

                <a href="<?= base_url('/banks') ?>"
                   class="flex items-center gap-3 px-3 py-2 text-[13px] font-medium rounded-lg transition-colors duration-200
                          <?= ($activeTab === 'banks') ? 'bg-white/10 text-accent' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' ?>">
                    <i class="fa-solid fa-building-columns text-sm w-4 text-center"></i>
                    Bankok
                </a>

                <a href="<?= base_url('/bank-transactions') ?>"
                   class="flex items-center gap-3 px-3 py-2 text-[13px] font-medium rounded-lg transition-colors duration-200
                          <?= ($activeTab === 'bank_transactions') ? 'bg-white/10 text-accent' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' ?>">
                    <i class="fa-solid fa-money-bill-transfer text-sm w-4 text-center"></i>
                    Bank tranzakciók
                </a>

                <a href="<?= base_url('/holidays') ?>"
                   class="flex items-center gap-3 px-3 py-2 text-[13px] font-medium rounded-lg transition-colors duration-200
                          <?= ($activeTab === 'holidays') ? 'bg-white/10 text-accent' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' ?>">
                    <i class="fa-solid fa-flag text-sm w-4 text-center"></i>
                    Ünnepnapok
                </a>

                <a href="<?= base_url('/settings/permissions') ?>"
                   class="flex items-center gap-3 px-3 py-2 text-[13px] font-medium rounded-lg transition-colors duration-200
                          <?= ($activeTab === 'settings') ? 'bg-white/10 text-accent' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' ?>">
                    <i class="fa-solid fa-user-shield text-sm w-4 text-center"></i>
                    Jogosultságok
                </a>

                <a href="<?= base_url('/settings/company') ?>"
                   class="flex items-center gap-3 px-3 py-2 text-[13px] font-medium rounded-lg transition-colors duration-200
                          <?= ($activeTab === 'settings') ? 'bg-white/10 text-accent' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' ?>">
                    <i class="fa-solid fa-gear text-sm w-4 text-center"></i>
                    Cégbeállítások
                </a>

                <a href="<?= base_url('/backup') ?>"
                   class="flex items-center gap-3 px-3 py-2 text-[13px] font-medium rounded-lg transition-colors duration-200
                          <?= ($activeTab === 'backup') ? 'bg-white/10 text-accent' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' ?>">
                    <i class="fa-solid fa-database text-sm w-4 text-center"></i>
                    Adatmentés
                </a>

                <a href="<?= base_url('/audit') ?>"
                   class="flex items-center gap-3 px-3 py-2 text-[13px] font-medium rounded-lg transition-colors duration-200
                          <?= ($activeTab === 'audit') ? 'bg-white/10 text-accent' : 'text-gray-400 hover:bg-white/5 hover:text-gray-200' ?>">
                    <i class="fa-solid fa-clock-rotate-left text-sm w-4 text-center"></i>
                    Napló
                </a>
            </div>
        <?php endif; ?>
    </nav>

    <!-- User info + logout -->
    <div class="mt-auto pt-3 border-t border-white/10">
        <div class="flex items-center gap-2 mb-2 px-1">
            <div class="h-8 w-8 rounded-full bg-accent/20 flex items-center justify-center text-accent font-heading font-bold text-xs">
                <?= e(mb_substr($currentUser['name'], 0, 2)) ?>
            </div>
            <div class="flex-grow overflow-hidden">
                <p class="text-white text-xs font-medium truncate"><?= e($currentUser['name']) ?></p>
                <p class="text-gray-500 text-[9px] uppercase tracking-wider font-bold">
                    <?= $isOwner ? 'Tulajdonos' : ($isAccountant ? 'Könyvelő' : e($currentUser['store_name'] ?? 'Bolt')) ?>
                </p>
            </div>
        </div>
        <form method="POST" action="<?= base_url('/logout') ?>">
            <?= csrf_field() ?>
            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2 text-gray-400 hover:text-red-400 hover:bg-white/5 rounded-lg text-xs font-medium transition-colors">
                <i class="fa-solid fa-right-from-bracket text-sm"></i>
                Kijelentkezés
            </button>
        </form>
    </div>
</aside>
