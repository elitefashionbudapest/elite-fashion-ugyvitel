<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Használati útmutató - Elite Fashion Ügyvitel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#506300',
                        'primary-container': '#d4fa4f',
                        accent: '#D9FF54',
                        sidebar: '#0b0f0e',
                        surface: '#f4f7f5',
                        'surface-container': '#e5e9e7',
                        'surface-container-low': '#eef2ef',
                        'surface-container-lowest': '#ffffff',
                        'on-surface': '#2b2f2e',
                        'on-surface-variant': '#585c5b',
                    },
                    fontFamily: {
                        heading: ['Manrope', 'sans-serif'],
                        body: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        html { scroll-behavior: smooth; }
        .doc-img { cursor: pointer; transition: all 0.2s; border: 2px solid #e5e9e7; }
        .doc-img:hover { transform: scale(1.02); border-color: #d4fa4f; box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .step-num { width: 28px; height: 28px; min-width: 28px; }
        #lightbox { display: none; }
        #lightbox.active { display: flex; }
        .nav-link.active { background: rgba(212,250,79,0.15); color: #506300; font-weight: 700; }
        .info-box { @apply bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700; }
        .warn-box { @apply bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-700; }
        .success-box { @apply bg-green-50 border border-green-200 rounded-lg p-3 text-xs text-green-700; }
        .key-badge { @apply inline-flex items-center px-2 py-0.5 bg-gray-100 border border-gray-300 rounded text-[11px] font-mono font-bold text-gray-600; }
    </style>
</head>
<body class="bg-surface text-on-surface font-body">

<!-- FEJLÉC -->
<header class="bg-sidebar sticky top-0 z-50 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-accent/20 flex items-center justify-center">
                <i class="fa-solid fa-book text-accent text-lg"></i>
            </div>
            <div>
                <h1 class="text-white font-heading font-bold text-lg leading-tight">Elite Fashion</h1>
                <p class="text-gray-500 text-[10px] font-semibold uppercase tracking-widest">Használati útmutató</p>
            </div>
        </div>
        <a href="<?= base_url('/') ?>" class="px-4 py-2 bg-accent/10 text-accent rounded-full text-xs font-bold hover:bg-accent/20 transition-colors">
            <i class="fa-solid fa-arrow-left mr-1"></i> Vissza a rendszerbe
        </a>
    </div>
</header>

<div class="max-w-7xl mx-auto px-4 sm:px-6 flex gap-8 mt-6">

    <!-- OLDALSÁV - NAVIGÁCIÓ -->
    <nav class="hidden lg:block w-56 flex-shrink-0">
        <div class="sticky top-24 space-y-1">
            <p class="text-[9px] font-bold text-on-surface-variant uppercase tracking-widest px-3 mb-2">Tartalom</p>
            <a href="#bevezetes" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors"><i class="fa-solid fa-house-chimney w-4 text-center text-xs"></i> Bevezetés</a>
            <a href="#bejelentkezes" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors"><i class="fa-solid fa-right-to-bracket w-4 text-center text-xs"></i> Bejelentkezés</a>
            <a href="#navigacio" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors"><i class="fa-solid fa-bars w-4 text-center text-xs"></i> Navigáció</a>
            <a href="#kezdolap" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors"><i class="fa-solid fa-gauge-high w-4 text-center text-xs"></i> Kezdőlap</a>
            <a href="#konyveles" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors"><i class="fa-solid fa-building-columns w-4 text-center text-xs"></i> Könyvelés</a>
            <a href="#fizetes" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors"><i class="fa-solid fa-money-bill-wave w-4 text-center text-xs"></i> Fizetések</a>
            <a href="#ertekeles" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors"><i class="fa-solid fa-star w-4 text-center text-xs"></i> Értékelések</a>
            <a href="#szabadsag" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors"><i class="fa-solid fa-umbrella-beach w-4 text-center text-xs"></i> Szabadság</a>
            <a href="#beosztas" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors"><i class="fa-solid fa-calendar-days w-4 text-center text-xs"></i> Beosztás</a>
            <a href="#szamlak" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors"><i class="fa-solid fa-file-invoice w-4 text-center text-xs"></i> Számlák</a>
            <a href="#selejt" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors"><i class="fa-solid fa-barcode w-4 text-center text-xs"></i> Selejt kezelés</a>
            <a href="#chat" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors"><i class="fa-solid fa-comments w-4 text-center text-xs"></i> Chat</a>
            <a href="#napzaras" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors"><i class="fa-solid fa-lock w-4 text-center text-xs"></i> Napzárás</a>
            <a href="#mobil" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors"><i class="fa-solid fa-mobile-screen w-4 text-center text-xs"></i> Mobil használat</a>
            <a href="#gyik" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors"><i class="fa-solid fa-circle-question w-4 text-center text-xs"></i> GYIK</a>
        </div>
    </nav>

    <!-- FŐ TARTALOM -->
    <main class="flex-1 min-w-0 pb-20">

        <!-- Mobil navigáció -->
        <div class="lg:hidden mb-6 bg-surface-container-lowest rounded-xl p-4">
            <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-2">Ugrás a szekcióra</p>
            <select onchange="location.hash=this.value" class="w-full rounded-lg border-surface-container text-sm py-2">
                <option value="#bevezetes">Bevezetés</option>
                <option value="#bejelentkezes">Bejelentkezés</option>
                <option value="#navigacio">Navigáció</option>
                <option value="#kezdolap">Kezdőlap</option>
                <option value="#konyveles">Könyvelés</option>
                <option value="#fizetes">Fizetések</option>
                <option value="#ertekeles">Értékelések</option>
                <option value="#szabadsag">Szabadság</option>
                <option value="#beosztas">Beosztás</option>
                <option value="#szamlak">Számlák</option>
                <option value="#selejt">Selejt kezelés</option>
                <option value="#chat">Chat</option>
                <option value="#napzaras">Napzárás</option>
                <option value="#mobil">Mobil használat</option>
                <option value="#gyik">GYIK</option>
            </select>
        </div>

        <!-- ============================================================ -->
        <!-- BEVEZETÉS -->
        <!-- ============================================================ -->
        <section id="bevezetes" class="mb-12">
            <div class="bg-sidebar rounded-2xl p-8 text-white mb-8">
                <h2 class="font-heading font-extrabold text-3xl mb-3">Üdvözlünk az Elite Fashion Ügyviteli Rendszerben!</h2>
                <p class="text-gray-400 text-sm leading-relaxed max-w-3xl mb-4">
                    Ez az útmutató lépésről lépésre végigvezet a rendszer minden funkcióján.
                    Minden szekciót képernyőképekkel illusztráltunk, hogy könnyen megtaláld amit keresel.
                    A képekre kattintva nagyban is megnézheted őket.
                </p>
                <p class="text-gray-400 text-sm leading-relaxed max-w-3xl">
                    Ha bármilyen kérdésed van munka közben, használd a rendszerbe épített <strong class="text-accent">Chat</strong> funkciót,
                    és írd meg a kérdésedet — a vezető vagy a többi bolt kollegája segít.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-surface-container-lowest rounded-xl p-5 border-l-4 border-primary-container">
                    <i class="fa-solid fa-bolt text-primary text-lg mb-2"></i>
                    <h4 class="font-heading font-bold text-sm mb-1">Gyors és egyszerű</h4>
                    <p class="text-xs text-on-surface-variant leading-relaxed">A rendszer mobiltelefonról is teljesen használható. Nem kell alkalmazást letölteni — a böngészőben nyílik meg.</p>
                </div>
                <div class="bg-surface-container-lowest rounded-xl p-5 border-l-4 border-primary-container">
                    <i class="fa-solid fa-shield-halved text-primary text-lg mb-2"></i>
                    <h4 class="font-heading font-bold text-sm mb-1">Biztonságos</h4>
                    <p class="text-xs text-on-surface-variant leading-relaxed">Minden adat titkosított kapcsolaton keresztül közlekedik. A jelszavad senki sem látja, még a tulajdonos sem.</p>
                </div>
                <div class="bg-surface-container-lowest rounded-xl p-5 border-l-4 border-primary-container">
                    <i class="fa-solid fa-clock-rotate-left text-primary text-lg mb-2"></i>
                    <h4 class="font-heading font-bold text-sm mb-1">Automatikus mentés</h4>
                    <p class="text-xs text-on-surface-variant leading-relaxed">A legtöbb művelet azonnal mentődik a szerverre. A vonalkód szkennelésnél pl. nem kell külön mentés gombot nyomni.</p>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- BEJELENTKEZÉS -->
        <!-- ============================================================ -->
        <section id="bejelentkezes" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-1 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">1</span>
                Bejelentkezés
            </h2>
            <p class="text-sm text-on-surface-variant mb-4 ml-10">Hogyan lépj be a rendszerbe</p>

            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            A rendszert a böngészőben éred el — számítógépen és telefonon egyaránt. Az első lépés a bejelentkezés.
                        </p>

                        <h4 class="font-heading font-bold text-sm mb-3">Lépések:</h4>
                        <div class="space-y-4 mb-4">
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                <div>
                                    <p class="text-sm font-semibold">Nyisd meg a böngészőt</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">Chrome, Safari, Firefox — bármelyik böngésző megfelelő. Írd be a kapott webcímet a böngésző címsorába.</p>
                                </div>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                <div>
                                    <p class="text-sm font-semibold">Add meg az e-mail címed és jelszavad</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">Ezeket a bejelentkezési adatokat a vezetőtől kaptad. Ügyelj a kis- és nagybetűkre a jelszóban!</p>
                                </div>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                <div>
                                    <p class="text-sm font-semibold">Pipáld be az "Emlékezz rám" opciót</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">Így legközelebb nem kell újra bejelentkezned ezen az eszközön. Ha közös számítógépet használsz, <strong>ne</strong> pipáld be!</p>
                                </div>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                                <div>
                                    <p class="text-sm font-semibold">Igazold, hogy nem vagy robot</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">Kattints a "Nem vagyok robot" jelölőnégyzetre. Ez egy biztonsági ellenőrzés.</p>
                                </div>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">5</span>
                                <div>
                                    <p class="text-sm font-semibold">Kattints a "Bejelentkezés" gombra</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">Sikeres bejelentkezés után a Kezdőlapra kerülsz.</p>
                                </div>
                            </div>
                        </div>

                        <div class="warn-box">
                            <p><i class="fa-solid fa-triangle-exclamation mr-1"></i> <strong>Ha elfelejtettéd a jelszavad:</strong> Szólj a boltvezetőnek vagy a tulajdonosnak — ő tud új jelszót beállítani. Jelszó-emlékeztetőt a rendszer nem küld.</p>
                        </div>

                        <div class="info-box mt-3">
                            <p><i class="fa-solid fa-lightbulb mr-1"></i> <strong>Tipp:</strong> Ha többször is rossz jelszót írsz be, a rendszer átmenetileg letilt a túl sok próbálkozás ellen. Ilyenkor várj pár percet és próbáld újra.</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <img src="<?= base_url('/docs/screenshots/login.png') ?>" alt="Bejelentkezés üres" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                            <p class="text-[10px] text-on-surface-variant text-center mt-1">A bejelentkezési képernyő megnyitás után</p>
                        </div>
                        <div>
                            <img src="<?= base_url('/docs/screenshots/login-filled.png') ?>" alt="Bejelentkezés kitöltve" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                            <p class="text-[10px] text-on-surface-variant text-center mt-1">Kitöltött adatokkal, készen a bejelentkezésre</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- NAVIGÁCIÓ -->
        <!-- ============================================================ -->
        <section id="navigacio" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-1 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">2</span>
                Navigáció a rendszerben
            </h2>
            <p class="text-sm text-on-surface-variant mb-4 ml-10">Hogyan találsz meg mindent</p>

            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                    <div class="lg:col-span-2">
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            A rendszerben egy sötét oldalsáv (menüsor) segít eligazodni. Ez <strong>számítógépen mindig látható</strong> a bal oldalon.
                            <strong>Telefonon</strong> a bal felső sarokban lévő <i class="fa-solid fa-bars text-xs"></i> ikonra kattintva nyílik ki.
                        </p>

                        <h4 class="font-heading font-bold text-sm mb-3">Menüpontok magyarázata:</h4>
                        <div class="space-y-2">
                            <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-container-low">
                                <i class="fa-solid fa-gauge-high w-5 text-center text-primary"></i>
                                <div>
                                    <p class="text-sm font-semibold">Kezdőlap</p>
                                    <p class="text-xs text-on-surface-variant">Napi áttekintés, feladatok, chat gyors elérése</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-container-low">
                                <i class="fa-solid fa-building-columns w-5 text-center text-primary"></i>
                                <div>
                                    <p class="text-sm font-semibold">Könyvelés</p>
                                    <p class="text-xs text-on-surface-variant">Napi pénzmozgások rögzítése (bevétel, kiadás, befizetés)</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-container-low">
                                <i class="fa-solid fa-money-bill-wave w-5 text-center text-primary"></i>
                                <div>
                                    <p class="text-sm font-semibold">Fizetések</p>
                                    <p class="text-xs text-on-surface-variant">Saját fizetési előzmények és kifizetések</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-container-low">
                                <i class="fa-solid fa-star w-5 text-center text-primary"></i>
                                <div>
                                    <p class="text-sm font-semibold">Értékelések</p>
                                    <p class="text-xs text-on-surface-variant">Dolgozók havi teljesítményértékelése</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-container-low">
                                <i class="fa-solid fa-umbrella-beach w-5 text-center text-primary"></i>
                                <div>
                                    <p class="text-sm font-semibold">Szabadság</p>
                                    <p class="text-xs text-on-surface-variant">Szabadság igénylése, jóváhagyás nyomon követése</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-container-low">
                                <i class="fa-solid fa-calendar-days w-5 text-center text-primary"></i>
                                <div>
                                    <p class="text-sm font-semibold">Beosztás</p>
                                    <p class="text-xs text-on-surface-variant">Heti/havi munkabeosztás naptár nézetben</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-container-low">
                                <i class="fa-solid fa-file-invoice w-5 text-center text-primary"></i>
                                <div>
                                    <p class="text-sm font-semibold">Számlák</p>
                                    <p class="text-xs text-on-surface-variant">Bejövő számlák kezelése, fizetési állapot</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-container-low">
                                <i class="fa-solid fa-barcode w-5 text-center text-primary"></i>
                                <div>
                                    <p class="text-sm font-semibold">Selejt</p>
                                    <p class="text-xs text-on-surface-variant">Selejtesnek ítélt termékek vonalkód-szkennelése</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-container-low">
                                <i class="fa-solid fa-comments w-5 text-center text-primary"></i>
                                <div>
                                    <p class="text-sm font-semibold">Chat</p>
                                    <p class="text-xs text-on-surface-variant">Üzenetek küldése a többi boltnak és a vezetőnek</p>
                                </div>
                            </div>
                        </div>

                        <div class="info-box mt-4">
                            <p><i class="fa-solid fa-lightbulb mr-1"></i> <strong>Megjegyzés:</strong> Nem mindenki látja az összes menüpontot. A jogosultságok boltonként eltérőek lehetnek — a tulajdonos állítja be, ki mihez fér hozzá.</p>
                        </div>

                        <div class="mt-4 p-3 bg-surface-container-low rounded-lg">
                            <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Kijelentkezés</p>
                            <p class="text-sm text-on-surface-variant">A menü alján, a neved alatt találod a <strong>"Kijelentkezés"</strong> gombot. Közös gépen mindig jelentkezz ki, amikor befejezted a munkát!</p>
                        </div>
                    </div>
                    <div>
                        <img src="<?= base_url('/docs/screenshots/sidebar.png') ?>" alt="Oldalsáv menü" class="doc-img rounded-xl w-full max-w-[250px] mx-auto" onclick="openLightbox(this.src)">
                        <p class="text-[10px] text-on-surface-variant text-center mt-2">Az oldalsáv menü — az aktív oldal kiemelve jelenik meg</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- KEZDŐLAP -->
        <!-- ============================================================ -->
        <section id="kezdolap" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-1 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">3</span>
                Kezdőlap
            </h2>
            <p class="text-sm text-on-surface-variant mb-4 ml-10">A napi áttekintés és teendők</p>

            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            A Kezdőlap a bejelentkezés után automatikusan megjelenik. Itt <strong>egy pillanat alatt</strong> áttekintheted, mi a helyzet a boltban.
                        </p>

                        <h4 class="font-heading font-bold text-sm mb-3">A Kezdőlap részei:</h4>

                        <div class="space-y-4 mb-4">
                            <div class="p-3 bg-surface-container-low rounded-lg">
                                <p class="text-sm font-bold mb-1"><i class="fa-solid fa-list-check text-primary mr-1"></i> Napi feladatok</p>
                                <p class="text-xs text-on-surface-variant leading-relaxed">Itt látod, mely napi teendők vannak hátra: könyvelés rögzítése, selejt összérték beírása stb. A zöld pipa <i class="fa-solid fa-circle-check text-green-500 text-[10px]"></i> jelzi a kész feladatokat, a piros <i class="fa-solid fa-circle text-red-500 text-[10px]"></i> a hiányzókat. Az adott feladatra kattintva egyből az adott oldalra jutsz.</p>
                            </div>

                            <div class="p-3 bg-surface-container-low rounded-lg">
                                <p class="text-sm font-bold mb-1"><i class="fa-solid fa-comments text-primary mr-1"></i> Közös chat</p>
                                <p class="text-xs text-on-surface-variant leading-relaxed">A jobb oldalon (desktopon) vagy alul (mobilon) közvetlenül üzenhetsz a többi boltnak és a vezetőnek anélkül, hogy a Chat menübe kellene navigálnod. Az üzenetek valós időben frissülnek.</p>
                            </div>

                            <div class="p-3 bg-surface-container-low rounded-lg">
                                <p class="text-sm font-bold mb-1"><i class="fa-solid fa-chart-simple text-primary mr-1"></i> Napi összesítő</p>
                                <p class="text-xs text-on-surface-variant leading-relaxed">Az oldal tetején a mai napi legfontosabb számok és állapotok összefoglalása.</p>
                            </div>
                        </div>

                        <div class="info-box">
                            <p><i class="fa-solid fa-lightbulb mr-1"></i> <strong>Tipp:</strong> A Kezdőlapra bármikor visszatérhetsz az oldalsáv tetején lévő "Kezdőlap" menüpontra kattintva.</p>
                        </div>
                    </div>
                    <div>
                        <img src="<?= base_url('/docs/screenshots/dashboard.png') ?>" alt="Kezdőlap" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                        <p class="text-[10px] text-on-surface-variant text-center mt-1">A Kezdőlap áttekintő nézete — napi feladatok, chat, és összesítő</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- KÖNYVELÉS -->
        <!-- ============================================================ -->
        <section id="konyveles" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-1 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">4</span>
                Könyvelés
            </h2>
            <p class="text-sm text-on-surface-variant mb-4 ml-10">Napi pénzmozgások rögzítése</p>

            <div class="bg-surface-container-lowest rounded-xl p-6 mb-4">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            A Könyvelés oldalon rögzíted a bolt napi pénzmozgásait. Minden befizetést, kiadást és bevételt itt kell feljegyezni.
                        </p>

                        <h4 class="font-heading font-bold text-sm mb-3">Az oldal felépítése:</h4>
                        <ul class="space-y-2 text-sm mb-4">
                            <li class="flex gap-2"><i class="fa-solid fa-filter text-primary mt-1 text-xs"></i> <span><strong>Szűrők</strong> — felül választhatsz dátumot, hónapot és tételtípust. Alapértelmezetten a mai nap tételei jelennek meg.</span></li>
                            <li class="flex gap-2"><i class="fa-solid fa-table text-primary mt-1 text-xs"></i> <span><strong>Tételek táblázata</strong> — a rögzített pénzmozgások listája dátum, típus, összeg és megjegyzés oszlopokkal.</span></li>
                            <li class="flex gap-2"><i class="fa-solid fa-plus text-primary mt-1 text-xs"></i> <span><strong>"Új tétel" gomb</strong> — a jobb felső sarokban új pénzmozgást rögzíthetsz.</span></li>
                        </ul>

                        <div class="warn-box">
                            <p><i class="fa-solid fa-triangle-exclamation mr-1"></i> <strong>Fontos:</strong> A könyvelési tételeket csak a tulajdonos tudja törölni. Ha hibásan rögzítettél valamit, szólj a vezetőnek.</p>
                        </div>
                    </div>
                    <div>
                        <img src="<?= base_url('/docs/screenshots/finance.png') ?>" alt="Könyvelés lista" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                        <p class="text-[10px] text-on-surface-variant text-center mt-1">A Könyvelés oldal — szűrőkkel és tételek listájával</p>
                    </div>
                </div>
            </div>

            <!-- Új tétel rögzítése -->
            <div class="bg-surface-container-lowest rounded-xl p-6">
                <h3 class="font-heading font-bold text-lg mb-4"><i class="fa-solid fa-plus-circle text-primary mr-2"></i>Új tétel rögzítése</h3>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <div class="space-y-4">
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                <div>
                                    <p class="text-sm font-semibold">Kattints az "Új tétel" gombra</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">A Könyvelés oldal jobb felső sarkában találod.</p>
                                </div>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                <div>
                                    <p class="text-sm font-semibold">Válaszd ki a dátumot</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">Alapértelmezetten a mai dátum van kiválasztva. Ha tegnapi tételt rögzítesz, kattints a dátumra és módosítsd.</p>
                                </div>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                <div>
                                    <p class="text-sm font-semibold">Válaszd ki a pénzmozgás célját</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">Pl. "Napi bevétel", "Befizetve takarékba", "Selejt befizetés" stb. A rendszer előre definiált kategóriákat kínál fel.</p>
                                </div>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                                <div>
                                    <p class="text-sm font-semibold">Írd be az összeget</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">Csak számot írj be, Ft jelet nem kell. A mező melletti számológép ikonra kattintva összeadhatod az értékeket.</p>
                                </div>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">5</span>
                                <div>
                                    <p class="text-sm font-semibold">Kattints a "Mentés" gombra</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">A tétel rögzítve lesz és megjelenik a könyvelés listában.</p>
                                </div>
                            </div>
                        </div>

                        <div class="info-box mt-4">
                            <p><i class="fa-solid fa-calculator mr-1"></i> <strong>Számológép funkció:</strong> Ha több összeget kell összeadnod (pl. több nyugta), a beviteli mező mellett a számológép ikon segít. Beírhatsz számításokat, pl. <span class="key-badge">12500+8700+3200</span>.</p>
                        </div>
                    </div>
                    <div>
                        <img src="<?= base_url('/docs/screenshots/finance-create.png') ?>" alt="Új könyvelési tétel" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                        <p class="text-[10px] text-on-surface-variant text-center mt-1">Az "Új tétel" rögzítési képernyő</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- FIZETÉSEK -->
        <!-- ============================================================ -->
        <section id="fizetes" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-1 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">5</span>
                Fizetések
            </h2>
            <p class="text-sm text-on-surface-variant mb-4 ml-10">Kifizetések rögzítése és előzmények</p>

            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            Itt rögzítheted és visszanézheted a fizetéseket. A korábbi kifizetések listája dátummal és összeggel jelenik meg.
                        </p>

                        <h4 class="font-heading font-bold text-sm mb-3">Amit itt látsz:</h4>
                        <ul class="space-y-2 text-sm mb-4">
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span><strong>Kifizetések listája</strong> — dátum, dolgozó neve, összeg, típus</span></li>
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span><strong>Szűrők</strong> — hónap, dolgozó szerinti szűrés</span></li>
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span><strong>Új fizetés rögzítése</strong> — az "Új fizetés" gombbal</span></li>
                        </ul>

                        <h4 class="font-heading font-bold text-sm mb-3">Új fizetés rögzítése:</h4>
                        <div class="space-y-3 mb-4">
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                <p class="text-sm">Kattints az <strong>"Új fizetés"</strong> gombra</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                <p class="text-sm">Válaszd ki a <strong>dolgozót</strong>, a <strong>dátumot</strong> és a <strong>típust</strong></p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                <p class="text-sm">Írd be az <strong>összeget</strong> és ha szükséges, adj hozzá <strong>megjegyzést</strong></p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                                <p class="text-sm">Kattints a <strong>"Mentés"</strong> gombra</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <img src="<?= base_url('/docs/screenshots/salary.png') ?>" alt="Fizetések lista" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                            <p class="text-[10px] text-on-surface-variant text-center mt-1">A Fizetések oldal — korábbi kifizetések listája</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- ÉRTÉKELÉSEK -->
        <!-- ============================================================ -->
        <section id="ertekeles" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-1 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">6</span>
                Értékelések
            </h2>
            <p class="text-sm text-on-surface-variant mb-4 ml-10">Dolgozók havi teljesítményértékelése</p>

            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            Az Értékelések oldalon havi rendszerességgel rögzítheted a dolgozók teljesítményét.
                            Az értékelés több szempont alapján történik, pontozással.
                        </p>

                        <h4 class="font-heading font-bold text-sm mb-3">Új értékelés rögzítése:</h4>
                        <div class="space-y-3 mb-4">
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                <p class="text-sm">Kattints az <strong>"Új értékelés"</strong> gombra</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                <p class="text-sm">Válaszd ki a <strong>dolgozót</strong> és az <strong>értékelendő hónapot</strong></p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                <p class="text-sm">Töltsd ki a <strong>pontszámokat</strong> minden szempont szerint</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                                <p class="text-sm">Ha szükséges, írj <strong>szöveges megjegyzést</strong> is</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">5</span>
                                <p class="text-sm">Kattints a <strong>"Mentés"</strong> gombra</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <img src="<?= base_url('/docs/screenshots/evaluations.png') ?>" alt="Értékelések" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                        <p class="text-[10px] text-on-surface-variant text-center mt-1">Az Értékelések oldal</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- SZABADSÁG -->
        <!-- ============================================================ -->
        <section id="szabadsag" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-1 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">7</span>
                Szabadság
            </h2>
            <p class="text-sm text-on-surface-variant mb-4 ml-10">Szabadság igénylése és kezelése</p>

            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            Ezen az oldalon igényelhetsz szabadságot és láthatod a korábbi kéréseid állapotát.
                            Minden kérést a tulajdonos hagy jóvá vagy utasít el.
                        </p>

                        <h4 class="font-heading font-bold text-sm mb-3">Állapotjelölések:</h4>
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-amber-400"></span>
                                <p class="text-sm"><strong>Függőben</strong> — elküldted, de még nem döntöttek róla</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-green-500"></span>
                                <p class="text-sm"><strong>Jóváhagyva</strong> — elfogadták a szabadságodat</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-red-500"></span>
                                <p class="text-sm"><strong>Elutasítva</strong> — nem hagyták jóvá (pl. túl sokan vannak szabadságon)</p>
                            </div>
                        </div>

                        <h4 class="font-heading font-bold text-sm mb-3">Szabadság igénylése:</h4>
                        <div class="space-y-3 mb-4">
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                <p class="text-sm">Kattints az <strong>"Új igénylés"</strong> gombra</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                <p class="text-sm">Válaszd ki a <strong>típust</strong> (szabadság, betegszabadság, szabadnap)</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                <p class="text-sm">Add meg a <strong>kezdő</strong> és <strong>befejező dátumot</strong></p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                                <p class="text-sm">Írj <strong>megjegyzést</strong> ha szükséges (pl. az ok)</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">5</span>
                                <p class="text-sm">Kattints a <strong>"Küldés"</strong> gombra — értesítés megy a tulajdonosnak</p>
                            </div>
                        </div>

                        <div class="info-box">
                            <p><i class="fa-solid fa-lightbulb mr-1"></i> <strong>Tipp:</strong> Minél előbb igényelsz szabadságot, annál nagyobb az esélye a jóváhagyásnak. A rendszer automatikusan ellenőrzi, hányan vannak szabadságon az adott napon.</p>
                        </div>
                    </div>
                    <div>
                        <img src="<?= base_url('/docs/screenshots/vacation.png') ?>" alt="Szabadság" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                        <p class="text-[10px] text-on-surface-variant text-center mt-1">A Szabadság oldal — igénylések és állapotuk</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- BEOSZTÁS -->
        <!-- ============================================================ -->
        <section id="beosztas" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-1 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">8</span>
                Beosztás
            </h2>
            <p class="text-sm text-on-surface-variant mb-4 ml-10">Munkabeosztás naptár nézetben</p>

            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            A Beosztás oldalon naptár nézetben láthatod a munkabeosztásodat. A heti vagy havi nézet között válthatsz.
                        </p>

                        <h4 class="font-heading font-bold text-sm mb-3">Amit itt tudsz:</h4>
                        <ul class="space-y-2 text-sm mb-4">
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span><strong>Saját beosztás megtekintése</strong> — melyik napon, melyik műszakban dolgozol</span></li>
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span><strong>Műszakok</strong> — reggeli és délutáni műszakok más-más színnel jelölve</span></li>
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span><strong>Heti/havi nézet</strong> — a felső gombok segítségével válthatsz</span></li>
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span><strong>Módosítás kérése</strong> — ha nem jó a beosztásod, jelezd a vezetőnek</span></li>
                        </ul>

                        <div class="info-box">
                            <p><i class="fa-solid fa-lightbulb mr-1"></i> <strong>Tipp:</strong> A beosztást a tulajdonos vagy a boltvezetők készítik. Te csak megtekintheted és módosítást kérhetsz — közvetlenül nem tudod szerkeszteni.</p>
                        </div>
                    </div>
                    <div>
                        <img src="<?= base_url('/docs/screenshots/schedule.png') ?>" alt="Beosztás" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                        <p class="text-[10px] text-on-surface-variant text-center mt-1">A Beosztás oldal — naptár nézetben</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- SZÁMLÁK -->
        <!-- ============================================================ -->
        <section id="szamlak" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-1 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">9</span>
                Számlák
            </h2>
            <p class="text-sm text-on-surface-variant mb-4 ml-10">Bejövő számlák kezelése</p>

            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            A Számlák oldalon a cég bejövő számláit kezeled — rögzíted az új számlákat és nyomon követed a fizetési állapotukat.
                        </p>

                        <h4 class="font-heading font-bold text-sm mb-3">Új számla rögzítése:</h4>
                        <div class="space-y-3 mb-4">
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                <p class="text-sm">Kattints az <strong>"Új számla"</strong> gombra</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                <p class="text-sm">Töltsd ki a <strong>szállító nevét</strong> (gépelés közben javaslatokat kapsz korábban használt szállítókból)</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                <p class="text-sm">Add meg a <strong>számlaszámot</strong>, <strong>összeget</strong> és a <strong>fizetési határidőt</strong></p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                                <p class="text-sm">Mentés után a számla megjelenik a listában <strong>"Fizetetlen"</strong> állapottal</p>
                            </div>
                        </div>

                        <h4 class="font-heading font-bold text-sm mb-2">Számla kifizetve jelölése:</h4>
                        <p class="text-sm text-on-surface-variant mb-4">Ha kifizettél egy számlát, kattints a sor végén lévő <strong>"Fizetve"</strong> gombra. A számla zöldre vált és átkerül a kifizett tételek közé.</p>

                        <div class="warn-box">
                            <p><i class="fa-solid fa-triangle-exclamation mr-1"></i> <strong>Fontos:</strong> A lejárt határidejű számlák pirossal jelennek meg. Ezeket minél hamarabb rendezni kell!</p>
                        </div>
                    </div>
                    <div>
                        <img src="<?= base_url('/docs/screenshots/invoices.png') ?>" alt="Számlák" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                        <p class="text-[10px] text-on-surface-variant text-center mt-1">A Számlák oldal — bejövő számlák és állapotuk</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- SELEJT -->
        <!-- ============================================================ -->
        <section id="selejt" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-1 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">10</span>
                Selejt kezelés
            </h2>
            <p class="text-sm text-on-surface-variant mb-4 ml-10">Selejtesnek ítélt termékek vonalkód-szkennelése</p>

            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            Ha egy terméket selejtesnek ítélsz (sérült, hibás, stb.), itt rögzítheted vonalkód szkennerrel.
                            A rendszer <strong>automatikusan felismeri a terméket</strong> a vonalkód alapján és kiírja a nevét és bruttó árát.
                        </p>

                        <h4 class="font-heading font-bold text-sm mb-3">Selejt szkennelése lépésről lépésre:</h4>
                        <div class="space-y-4 mb-4">
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                <div>
                                    <p class="text-sm font-semibold">Navigálj a Selejt oldalra</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">A bal oldali menüből válaszd a "Selejt" menüpontot.</p>
                                </div>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                <div>
                                    <p class="text-sm font-semibold">Kattints a vonalkód beviteli mezőbe</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">A bal oldalon található "Vonalkód beolvasása..." feliratú mező. Általában automatikusan ide kerül a fókusz.</p>
                                </div>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                <div>
                                    <p class="text-sm font-semibold">Olvasd be a vonalkódot a szkennerrel</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">A szkenner automatikusan beírja a vonalkódot ÉS Entert küld. A rendszer <strong>azonnal menti</strong> a tételt — nem kell semmit nyomnod.</p>
                                </div>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                                <div>
                                    <p class="text-sm font-semibold">Ellenőrizd a visszajelzést</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">Sikeres mentés után zölden megjelenik az utolsó vonalkód, a <strong>termék neve</strong> és az <strong>ára</strong>. A jobb oldali táblázatban megjelenik az új tétel.</p>
                                </div>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">5</span>
                                <div>
                                    <p class="text-sm font-semibold">Folytasd a szkennelést</p>
                                    <p class="text-xs text-on-surface-variant mt-0.5">A mező automatikusan kiürül, és újra kész a következő vonalkódra. Egymás után korlátlanul szkennelhetsz.</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg mb-3">
                            <p class="text-xs text-amber-700"><i class="fa-solid fa-coins mr-1"></i> <strong>Napi összérték rögzítése:</strong> A nap végén a "Napi selejt összérték" mezőbe írd be a selejtek becsült összértékét Ft-ban. A rendszer automatikusan kiszámol egy becsült értéket a terméklistából, de ezt felülírhatod.</p>
                        </div>

                        <div class="p-3 bg-surface-container-low rounded-lg mb-3">
                            <p class="text-xs text-on-surface-variant"><i class="fa-solid fa-shield-halved mr-1"></i> <strong>Dupla szkennelés védelem:</strong> Ha 3 másodpercen belül ugyanazt a vonalkódot olvasod be, a rendszer figyelmen kívül hagyja — így nem gond, ha a szkenner véletlenül kétszer olvas.</p>
                        </div>

                        <div class="info-box">
                            <p><i class="fa-solid fa-file-arrow-down mr-1"></i> <strong>CSV Export:</strong> A jobb felső sarokban a "CSV Export" gombbal letöltheted az összes selejt tételt táblázat formátumban (Excel-ben megnyitható).</p>
                        </div>
                    </div>
                    <div>
                        <img src="<?= base_url('/docs/screenshots/defects.png') ?>" alt="Selejt kezelés" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                        <p class="text-[10px] text-on-surface-variant text-center mt-1">A Selejt oldal — bal oldalon a szkenner, jobb oldalon a tételek listája</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- CHAT -->
        <!-- ============================================================ -->
        <section id="chat" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-1 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">11</span>
                Chat
            </h2>
            <p class="text-sm text-on-surface-variant mb-4 ml-10">Valós idejű kommunikáció</p>

            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            A Chat funkcióval valós időben üzenhetsz a többi boltnak és a vezetőnek.
                            Az üzenetek azonnal megérkeznek — nem kell frissíteni az oldalt.
                        </p>

                        <h4 class="font-heading font-bold text-sm mb-3">Két fajta chat létezik:</h4>
                        <div class="space-y-3 mb-4">
                            <div class="p-3 bg-surface-container-low rounded-lg">
                                <p class="text-sm font-bold mb-1"><i class="fa-solid fa-users text-primary mr-1"></i> Közös chat</p>
                                <p class="text-xs text-on-surface-variant leading-relaxed">Mindenki látja, aki be van jelentkezve. Ide írj, ha az összes boltnak/kollegának szól az üzenet. A Kezdőlapon is elérheted a közös chatet.</p>
                            </div>
                            <div class="p-3 bg-surface-container-low rounded-lg">
                                <p class="text-sm font-bold mb-1"><i class="fa-solid fa-user text-primary mr-1"></i> Privát üzenet</p>
                                <p class="text-xs text-on-surface-variant leading-relaxed">Csak te és a kiválasztott személy látja. A bal oldali listában kattints a személy nevére a privát beszélgetés megnyitásához.</p>
                            </div>
                        </div>

                        <h4 class="font-heading font-bold text-sm mb-3">Üzenet küldése:</h4>
                        <div class="space-y-3 mb-4">
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                <p class="text-sm">Írd be az üzeneted az alsó szövegmezőbe</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                <p class="text-sm">Nyomj <span class="key-badge">Enter</span>-t vagy kattints a <i class="fa-solid fa-paper-plane text-xs"></i> küldés gombra</p>
                            </div>
                        </div>

                        <h4 class="font-heading font-bold text-sm mb-3">Üzenet visszavonása:</h4>
                        <p class="text-sm text-on-surface-variant mb-4">
                            A saját üzeneteid jobb felső sarkában egy kis <i class="fa-solid fa-trash-can text-xs"></i> kuka ikon jelenik meg.
                            Erre kattintva <strong>véglegesen törlöd</strong> az üzenetet — a másik fél sem fogja többé látni.
                        </p>

                        <div class="info-box">
                            <p><i class="fa-solid fa-bell mr-1"></i> <strong>Értesítés:</strong> Ha új üzenet érkezik és nem a Chat oldalon vagy, egy hang jelzi az új üzenetet, és a menüben megjelenik az olvasatlan üzenetek száma.</p>
                        </div>
                    </div>
                    <div>
                        <img src="<?= base_url('/docs/screenshots/chat.png') ?>" alt="Chat" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                        <p class="text-[10px] text-on-surface-variant text-center mt-1">A Chat oldal — bal oldalon beszélgetések, jobb oldalon üzenetek</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- NAPZÁRÁS -->
        <!-- ============================================================ -->
        <section id="napzaras" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-1 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">12</span>
                Napzárás
            </h2>
            <p class="text-sm text-on-surface-variant mb-4 ml-10">A nap végi teendők ellenőrzése</p>

            <div class="bg-surface-container-lowest rounded-xl p-6">
                <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                    Minden munkanap végén a rendszer ellenőrzi, hogy minden napi feladatot elvégeztél-e.
                    A Kezdőlapon a <strong>Napi feladatok</strong> szekcióban látod a teendőket.
                </p>

                <h4 class="font-heading font-bold text-sm mb-3">Nap végi ellenőrzőlista:</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                    <div class="p-3 bg-surface-container-low rounded-lg flex items-start gap-2">
                        <i class="fa-solid fa-square-check text-green-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-semibold">Könyvelési tételek rögzítve?</p>
                            <p class="text-xs text-on-surface-variant">Minden napi bevétel, kiadás, befizetés felírva.</p>
                        </div>
                    </div>
                    <div class="p-3 bg-surface-container-low rounded-lg flex items-start gap-2">
                        <i class="fa-solid fa-square-check text-green-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-semibold">Selejt összérték beírva?</p>
                            <p class="text-xs text-on-surface-variant">Ha volt selejt, a napi összértéket be kell írni.</p>
                        </div>
                    </div>
                    <div class="p-3 bg-surface-container-low rounded-lg flex items-start gap-2">
                        <i class="fa-solid fa-square-check text-green-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-semibold">Selejt befizetés rögzítve?</p>
                            <p class="text-xs text-on-surface-variant">Ha volt selejt befizetés, azt a könyvelésbe is fel kell rögzíteni.</p>
                        </div>
                    </div>
                    <div class="p-3 bg-surface-container-low rounded-lg flex items-start gap-2">
                        <i class="fa-solid fa-square-check text-green-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-semibold">Napi bevétel beírva?</p>
                            <p class="text-xs text-on-surface-variant">A napi forgalmat a könyvelésbe rögzíteni kell.</p>
                        </div>
                    </div>
                </div>

                <div class="warn-box">
                    <p><i class="fa-solid fa-triangle-exclamation mr-1"></i> <strong>Fontos:</strong> Ha valami hiányzik, a Kezdőlapon piros jelzéssel figyelmeztet a rendszer. Ezeket a feladatokat a nap végéig el kell végezni!</p>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- MOBIL HASZNÁLAT -->
        <!-- ============================================================ -->
        <section id="mobil" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-1 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center"><i class="fa-solid fa-mobile-screen text-[10px]"></i></span>
                Mobil használat
            </h2>
            <p class="text-sm text-on-surface-variant mb-4 ml-10">A rendszer telefonon</p>

            <div class="bg-surface-container-lowest rounded-xl p-6">
                <p class="text-sm text-on-surface-variant mb-6 leading-relaxed">
                    A rendszer <strong>teljesen használható telefonról</strong> is. Ugyanazokat a funkciókat éred el, mint számítógépen — mobilra optimalizált elrendezésben.
                    Nem kell alkalmazást letölteni, a böngészőben nyílik meg.
                </p>

                <h4 class="font-heading font-bold text-sm mb-3">Fontos tudnivalók mobilon:</h4>
                <ul class="space-y-3 text-sm mb-6">
                    <li class="flex gap-2"><i class="fa-solid fa-bars text-primary mt-1"></i> <span><strong>Menü megnyitása:</strong> A bal felső sarokban lévő <i class="fa-solid fa-bars text-xs"></i> (hamburger) ikonra kattintva nyílik ki az oldalsáv menü.</span></li>
                    <li class="flex gap-2"><i class="fa-solid fa-comments text-primary mt-1"></i> <span><strong>Chat:</strong> A kezdőlapon alul egy kihúzható chat sáv van — koppints rá a megnyitáshoz.</span></li>
                    <li class="flex gap-2"><i class="fa-solid fa-expand text-primary mt-1"></i> <span><strong>Táblázatok:</strong> A szélesebb táblázatoknál oldalra görgethetsz a teljes tartalom megtekintéséhez.</span></li>
                </ul>

                <h4 class="font-heading font-bold text-sm mb-3">Alkalmazásként telepítés:</h4>
                <div class="space-y-3 mb-6">
                    <div class="p-3 bg-surface-container-low rounded-lg">
                        <p class="text-sm font-bold mb-1"><i class="fa-brands fa-apple mr-1"></i> iPhone (Safari)</p>
                        <p class="text-xs text-on-surface-variant">Nyisd meg az oldalt Safari-ban → koppints a <i class="fa-solid fa-arrow-up-from-bracket text-xs"></i> Megosztás ikonra → "Hozzáadás a Főképernyőhöz"</p>
                    </div>
                    <div class="p-3 bg-surface-container-low rounded-lg">
                        <p class="text-sm font-bold mb-1"><i class="fa-brands fa-android mr-1"></i> Android (Chrome)</p>
                        <p class="text-xs text-on-surface-variant">Nyisd meg az oldalt Chrome-ban → koppints a <i class="fa-solid fa-ellipsis-vertical text-xs"></i> három pontra → "Hozzáadás a kezdőképernyőhöz"</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="text-center">
                        <img src="<?= base_url('/docs/screenshots/mobile-dashboard.png') ?>" alt="Mobil kezdőlap" class="doc-img rounded-xl w-full max-w-[280px] mx-auto mb-2" onclick="openLightbox(this.src)">
                        <p class="text-xs font-bold text-on-surface-variant">Kezdőlap</p>
                    </div>
                    <div class="text-center">
                        <img src="<?= base_url('/docs/screenshots/mobile-defects.png') ?>" alt="Mobil selejt" class="doc-img rounded-xl w-full max-w-[280px] mx-auto mb-2" onclick="openLightbox(this.src)">
                        <p class="text-xs font-bold text-on-surface-variant">Selejt kezelés</p>
                    </div>
                    <div class="text-center">
                        <img src="<?= base_url('/docs/screenshots/mobile-chat.png') ?>" alt="Mobil chat" class="doc-img rounded-xl w-full max-w-[280px] mx-auto mb-2" onclick="openLightbox(this.src)">
                        <p class="text-xs font-bold text-on-surface-variant">Chat</p>
                    </div>
                </div>

                <div class="success-box mt-4">
                    <p><i class="fa-solid fa-circle-check mr-1"></i> <strong>Telepítés után</strong> az alkalmazás ikon megjelenik a telefonod kezdőképernyőjén, és úgy nyílik meg, mint egy valódi app — teljes képernyőn, böngészősáv nélkül.</p>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- GYIK -->
        <!-- ============================================================ -->
        <section id="gyik" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-1 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">?</span>
                Gyakran Ismételt Kérdések
            </h2>
            <p class="text-sm text-on-surface-variant mb-4 ml-10">A leggyakoribb kérdések és válaszok</p>

            <div class="space-y-3">
                <details class="bg-surface-container-lowest rounded-xl overflow-hidden group" open>
                    <summary class="p-5 cursor-pointer flex items-center justify-between hover:bg-surface-container-low transition-colors">
                        <span class="font-heading font-bold text-sm">Elfelejtettem a jelszavamat. Mit tegyek?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-on-surface-variant group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-on-surface-variant leading-relaxed">Szólj a boltvezetőnek vagy a tulajdonosnak — ő tud új jelszót beállítani a Fiókok kezelésében. A rendszer nem küld jelszó-emlékeztetőt e-mailben. A jelszavadat senki sem tudja megnézni, csak újat lehet beállítani.</div>
                </details>

                <details class="bg-surface-container-lowest rounded-xl overflow-hidden group">
                    <summary class="p-5 cursor-pointer flex items-center justify-between hover:bg-surface-container-low transition-colors">
                        <span class="font-heading font-bold text-sm">A vonalkód szkenner nem működik. Mi a teendő?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-on-surface-variant group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-on-surface-variant leading-relaxed">
                        <p class="mb-2">Próbáld a következőket sorrendben:</p>
                        <ol class="list-decimal list-inside space-y-1">
                            <li>Kattints bele a vonalkód beviteli mezőbe — a kurzornak ott kell villognia</li>
                            <li>Ellenőrizd, hogy a szkenner USB kábele csatlakozik-e a számítógéphez</li>
                            <li>Próbáld meg kézzel beírni a vonalkódot és nyomj <span class="key-badge">Enter</span>-t</li>
                            <li>Frissítsd az oldalt: <span class="key-badge">F5</span> vagy <span class="key-badge">Ctrl</span>+<span class="key-badge">R</span></li>
                            <li>Ha semmi sem működik, próbáld meg egy másik böngészőben (Chrome ajánlott)</li>
                        </ol>
                    </div>
                </details>

                <details class="bg-surface-container-lowest rounded-xl overflow-hidden group">
                    <summary class="p-5 cursor-pointer flex items-center justify-between hover:bg-surface-container-low transition-colors">
                        <span class="font-heading font-bold text-sm">Hogyan használjam telefonról?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-on-surface-variant group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-on-surface-variant leading-relaxed">
                        Nyisd meg a böngészőt a telefonodon (Chrome vagy Safari), írd be a webcímet és jelentkezz be.
                        Az oldal automatikusan mobilra optimalizálva jelenik meg.
                        A bal felső sarokban a <i class="fa-solid fa-bars text-xs"></i> ikonnal nyithatod ki a menüt.
                        Részletesebb leírást a <a href="#mobil" class="text-primary font-semibold underline">Mobil használat</a> szekcióban találsz.
                    </div>
                </details>

                <details class="bg-surface-container-lowest rounded-xl overflow-hidden group">
                    <summary class="p-5 cursor-pointer flex items-center justify-between hover:bg-surface-container-low transition-colors">
                        <span class="font-heading font-bold text-sm">Rossz adatot rögzítettem. Lehet törölni?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-on-surface-variant group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-on-surface-variant leading-relaxed">A legtöbb tétel törlését csak a tulajdonos tudja megtenni. Szólj a vezetőnek, aki ki tudja törölni a hibás tételt. Chat üzenetet viszont magad is visszavonhatsz a kuka ikonnal.</div>
                </details>

                <details class="bg-surface-container-lowest rounded-xl overflow-hidden group">
                    <summary class="p-5 cursor-pointer flex items-center justify-between hover:bg-surface-container-low transition-colors">
                        <span class="font-heading font-bold text-sm">Nem látom valamelyik menüpontot. Miért?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-on-surface-variant group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-on-surface-variant leading-relaxed">A menüpontok jogosultság alapján jelennek meg. Ha valamit nem látsz, az azt jelenti, hogy a fiókodon nincs bekapcsolva az adott funkció. Kérd meg a tulajdonost, hogy állítsa be a Jogosultságok oldalon.</div>
                </details>

                <details class="bg-surface-container-lowest rounded-xl overflow-hidden group">
                    <summary class="p-5 cursor-pointer flex items-center justify-between hover:bg-surface-container-low transition-colors">
                        <span class="font-heading font-bold text-sm">A rendszer lassú vagy nem töltődik be. Mit csináljak?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-on-surface-variant group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-on-surface-variant leading-relaxed">
                        <ol class="list-decimal list-inside space-y-1">
                            <li>Ellenőrizd az internetkapcsolatodat (pl. nyiss meg egy másik oldalt)</li>
                            <li>Frissítsd az oldalt: <span class="key-badge">Ctrl</span>+<span class="key-badge">Shift</span>+<span class="key-badge">R</span> (teljes frissítés)</li>
                            <li>Zárd be a felesleges böngészőfüleket</li>
                            <li>Ha továbbra sem működik, próbáld meg inkognitó módban (Ctrl+Shift+N)</li>
                            <li>Ha a probléma tartós, szólj a tulajdonosnak</li>
                        </ol>
                    </div>
                </details>

                <details class="bg-surface-container-lowest rounded-xl overflow-hidden group">
                    <summary class="p-5 cursor-pointer flex items-center justify-between hover:bg-surface-container-low transition-colors">
                        <span class="font-heading font-bold text-sm">A szkenner dupla vonalkódot olvas be. Miért?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-on-surface-variant group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-on-surface-variant leading-relaxed">A rendszer automatikusan véd a dupla szkennelés ellen: ha 3 másodpercen belül ugyanazt a vonalkódot olvasod be, a másodikat figyelmen kívül hagyja. Ha mégis megjelenik kétszer, töröltesd az egyiket a vezetővel.</div>
                </details>
            </div>
        </section>

    </main>
</div>

<!-- LÁBLÉC -->
<footer class="bg-sidebar py-6 mt-12">
    <div class="max-w-7xl mx-auto px-6 text-center">
        <p class="text-gray-500 text-xs">Elite Fashion Ügyviteli Rendszer &copy; <?= date('Y') ?> — Használati útmutató</p>
    </div>
</footer>

<!-- LIGHTBOX -->
<div id="lightbox" class="fixed inset-0 z-[100] bg-black/90 items-center justify-center p-4" onclick="closeLightbox()">
    <img id="lightbox-img" class="max-w-full max-h-full rounded-xl shadow-2xl" onclick="event.stopPropagation()">
    <button class="absolute top-6 right-6 w-12 h-12 bg-white/10 text-white rounded-full text-xl flex items-center justify-center hover:bg-white/20 transition-colors" onclick="closeLightbox()">
        <i class="fa-solid fa-xmark"></i>
    </button>
</div>

<script>
function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeLightbox(); });

// Aktív navigáció követés
var sections = document.querySelectorAll('section[id]');
var navLinks = document.querySelectorAll('.nav-link');
window.addEventListener('scroll', function() {
    var current = '';
    sections.forEach(function(section) {
        if (window.scrollY >= section.offsetTop - 120) {
            current = section.getAttribute('id');
        }
    });
    navLinks.forEach(function(link) {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + current) {
            link.classList.add('active');
        }
    });
});
</script>
</body>
</html>
