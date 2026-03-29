<?php
$s = $data['settings'] ?? [];
$inputCls = 'w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest';
?>

<form method="POST" action="<?= base_url('/settings/company') ?>">
    <?= csrf_field() ?>

    <div class="flex flex-wrap flex-col sm:flex-row sm:items-end justify-between gap-3 mb-6">
        <div>
            <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Cégbeállítások</h1>
            <p class="text-on-surface-variant text-xs sm:text-sm">Cégadatok, email számla feldolgozás, API kulcsok.</p>
        </div>
        <button type="submit" class="w-full sm:w-auto px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full flex items-center justify-center gap-2 shadow-lg text-sm">
            <i class="fa-solid fa-check"></i> Mentés
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <!-- Cégadatok -->
        <div class="bg-surface-container-lowest rounded-xl p-4 sm:p-6">
            <h3 class="font-heading font-bold text-on-surface mb-4 flex items-center gap-2">
                <i class="fa-solid fa-building text-primary"></i> Cégadatok
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Cégnév</label>
                    <input type="text" name="company_name" value="<?= e($s['company_name'] ?? '') ?>" class="<?= $inputCls ?>" placeholder="pl. Elite Fashion Kft.">
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Cégnév variációk</label>
                    <input type="text" name="company_name_variants" value="<?= e($s['company_name_variants'] ?? '') ?>" class="<?= $inputCls ?>" placeholder="pl. Elite Divat, ELITE FASHION KFT">
                    <p class="text-[10px] text-on-surface-variant mt-1">Vesszővel elválasztva. Az AI ezeket is elfogadja a számlán.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Adószám</label>
                    <input type="text" name="company_tax_number" value="<?= e($s['company_tax_number'] ?? '') ?>" class="<?= $inputCls ?>" placeholder="pl. 12345678-2-42">
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Cím</label>
                    <input type="text" name="company_address" value="<?= e($s['company_address'] ?? '') ?>" class="<?= $inputCls ?>" placeholder="pl. 1051 Budapest, Vörösmarty tér 1.">
                </div>
            </div>
        </div>

        <!-- IMAP beállítások -->
        <div class="bg-surface-container-lowest rounded-xl p-4 sm:p-6">
            <h3 class="font-heading font-bold text-on-surface mb-4 flex items-center gap-2">
                <i class="fa-solid fa-envelope text-blue-500"></i> Email számla feldolgozás
            </h3>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">IMAP szerver</label>
                        <input type="text" name="imap_host" value="<?= e($s['imap_host'] ?? '') ?>" class="<?= $inputCls ?>" placeholder="pl. imap.gmail.com">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Port</label>
                        <input type="text" name="imap_port" value="<?= e($s['imap_port'] ?? '993') ?>" class="<?= $inputCls ?>" placeholder="993">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Email cím</label>
                    <input type="email" name="imap_email" value="<?= e($s['imap_email'] ?? '') ?>" class="<?= $inputCls ?>" placeholder="szamlak@elitedivat.hu">
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Jelszó / App password</label>
                    <input type="password" name="imap_password" value="<?= e($s['imap_password'] ?? '') ?>" class="<?= $inputCls ?>" placeholder="••••••••">
                    <p class="text-[10px] text-on-surface-variant mt-1">Gmail esetén App Password kell (2FA bekapcsolva).</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Titkosítás</label>
                    <select name="imap_encryption" class="<?= $inputCls ?>">
                        <option value="ssl" <?= ($s['imap_encryption'] ?? 'ssl') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        <option value="tls" <?= ($s['imap_encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS</option>
                        <option value="none" <?= ($s['imap_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>Nincs</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- API kulcs -->
        <div class="bg-surface-container-lowest rounded-xl p-4 sm:p-6 lg:col-span-2">
            <h3 class="font-heading font-bold text-on-surface mb-4 flex items-center gap-2">
                <i class="fa-solid fa-robot text-purple-500"></i> AI feldolgozás (Anthropic Claude)
            </h3>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">API kulcs</label>
                <input type="password" name="anthropic_api_key" value="<?= e($s['anthropic_api_key'] ?? '') ?>" class="<?= $inputCls ?>" placeholder="sk-ant-...">
                <p class="text-[10px] text-on-surface-variant mt-1">Anthropic Console → API Keys → <a href="https://console.anthropic.com/settings/keys" target="_blank" class="text-primary underline">Létrehozás</a></p>
            </div>
        </div>

    </div>
</form>
