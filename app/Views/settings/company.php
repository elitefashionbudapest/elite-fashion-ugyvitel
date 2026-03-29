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
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Adószám</label>
                        <input type="text" name="company_tax_number" value="<?= e($s['company_tax_number'] ?? '') ?>" class="<?= $inputCls ?>" placeholder="pl. 12345678-2-42">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">EU adószám (VAT)</label>
                        <input type="text" name="company_eu_vat" value="<?= e($s['company_eu_vat'] ?? '') ?>" class="<?= $inputCls ?>" placeholder="pl. HU12345678">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Cím</label>
                    <input type="text" name="company_address" value="<?= e($s['company_address'] ?? '') ?>" class="<?= $inputCls ?>" placeholder="pl. 1051 Budapest, Vörösmarty tér 1.">
                </div>
            </div>
        </div>

        <!-- Gmail API csatlakozás -->
        <div class="bg-surface-container-lowest rounded-xl p-4 sm:p-6">
            <h3 class="font-heading font-bold text-on-surface mb-4 flex items-center gap-2">
                <i class="fa-solid fa-envelope text-blue-500"></i> Email számla feldolgozás
            </h3>

            <?php $gmailEmail = $s['google_email'] ?? ''; ?>
            <?php if ($gmailEmail): ?>
                <!-- Csatlakoztatva -->
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                            <i class="fa-solid fa-circle-check text-emerald-600"></i>
                        </div>
                        <div>
                            <p class="font-bold text-emerald-800 text-sm">Gmail csatlakoztatva</p>
                            <p class="text-xs text-emerald-600"><?= e($gmailEmail) ?></p>
                        </div>
                    </div>
                    <form method="POST" action="<?= base_url('/settings/company/disconnect-gmail') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="px-3 py-1.5 bg-red-50 text-red-600 text-xs font-bold rounded-lg border border-red-200 hover:bg-red-100">
                            Lecsatlakoztatás
                        </button>
                    </form>
                </div>
                <p class="text-[10px] text-on-surface-variant mt-2">A rendszer naponta reggel 5-kor automatikusan átnézi a beérkezett számlákat.</p>
            <?php else: ?>
                <!-- Nincs csatlakoztatva -->
                <div class="space-y-3 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Google Client ID</label>
                        <input type="text" name="google_client_id" value="<?= e($s['google_client_id'] ?? '') ?>" class="<?= $inputCls ?>" placeholder="...apps.googleusercontent.com">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Google Client Secret</label>
                        <input type="password" name="google_client_secret" value="<?= e($s['google_client_secret'] ?? '') ?>" class="<?= $inputCls ?>" placeholder="GOCSPX-...">
                    </div>
                </div>

                <?php if (!empty($s['google_client_id']) && !empty($s['google_client_secret'])): ?>
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 text-center">
                    <i class="fa-brands fa-google text-4xl text-gray-300 mb-2 block"></i>
                    <p class="text-sm text-gray-600 mb-3">Csatlakoztasd a Gmail fiókodat.</p>
                    <a href="<?= base_url('/settings/company/connect-gmail') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white font-bold text-sm rounded-xl hover:bg-blue-700 transition-colors">
                        <i class="fa-brands fa-google"></i> Gmail csatlakoztatása
                    </a>
                </div>
                <?php else: ?>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-xs text-amber-700">
                    <i class="fa-solid fa-circle-info mr-1"></i> Először mentsd el a Client ID-t és Secret-et, utána tudod csatlakoztatni.
                </div>
                <?php endif; ?>
                <p class="text-[10px] text-on-surface-variant mt-2">Google OAuth2 — biztonságos, a jelszavad nem kerül mentésre.</p>
            <?php endif; ?>
        </div>

        <!-- API kulcs -->
        <div class="bg-surface-container-lowest rounded-xl p-4 sm:p-6 lg:col-span-2">
            <h3 class="font-heading font-bold text-on-surface mb-4 flex items-center gap-2">
                <i class="fa-solid fa-robot text-purple-500"></i> AI feldolgozás (Anthropic Claude)
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">API kulcs</label>
                    <input type="password" name="anthropic_api_key" value="<?= e($s['anthropic_api_key'] ?? '') ?>" class="<?= $inputCls ?>" placeholder="sk-ant-...">
                    <p class="text-[10px] text-on-surface-variant mt-1">Anthropic Console → API Keys → <a href="https://console.anthropic.com/settings/keys" target="_blank" class="text-primary underline">Létrehozás</a></p>
                </div>
                <!-- API teszt gomb -->
                <button type="button" onclick="testApi()" class="px-4 py-2.5 bg-purple-50 text-purple-700 font-bold text-xs rounded-xl border border-purple-200 hover:bg-purple-100 transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-flask-vial"></i> API kulcs tesztelése
                </button>
                <div id="api-result" class="hidden text-xs p-3 rounded-xl"></div>
            </div>
        </div>

    </div>
</form>

<script>
async function testApi() {
    const btn = event.target.closest('button');
    const result = document.getElementById('api-result');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Tesztelés...';
    result.className = 'text-xs p-3 rounded-xl bg-gray-50 text-gray-500';
    result.textContent = 'API hívás...';
    result.classList.remove('hidden');

    try {
        const res = await fetch('<?= base_url('/settings/company/test-api') ?>');
        const data = await res.json();
        if (data.success) {
            result.className = 'text-xs p-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200';
            result.innerHTML = '<i class="fa-solid fa-circle-check mr-1"></i>' + data.message;
        } else {
            result.className = 'text-xs p-3 rounded-xl bg-red-50 text-red-700 border border-red-200';
            result.innerHTML = '<i class="fa-solid fa-circle-xmark mr-1"></i>' + data.message;
        }
    } catch(e) {
        result.className = 'text-xs p-3 rounded-xl bg-red-50 text-red-700 border border-red-200';
        result.textContent = 'Hiba: ' + e.message;
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-flask-vial"></i> API kulcs tesztelése';
}
</script>
