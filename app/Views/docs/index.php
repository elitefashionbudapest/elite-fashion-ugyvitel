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
        .doc-img { cursor: pointer; transition: transform 0.2s; border: 2px solid #e5e9e7; }
        .doc-img:hover { transform: scale(1.02); border-color: #d4fa4f; }
        .step-num { width: 28px; height: 28px; min-width: 28px; }
        #lightbox { display: none; }
        #lightbox.active { display: flex; }
        .nav-link.active { background: rgba(212,250,79,0.15); color: #506300; font-weight: 700; }
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
            <a href="#bevezetes" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors">
                <i class="fa-solid fa-house-chimney w-4 text-center text-xs"></i> Bevezetés
            </a>
            <a href="#bejelentkezes" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors">
                <i class="fa-solid fa-right-to-bracket w-4 text-center text-xs"></i> Bejelentkezés
            </a>
            <a href="#kezdolap" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors">
                <i class="fa-solid fa-gauge-high w-4 text-center text-xs"></i> Kezdőlap
            </a>
            <a href="#konyveles" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors">
                <i class="fa-solid fa-building-columns w-4 text-center text-xs"></i> Könyvelés
            </a>
            <a href="#fizetes" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors">
                <i class="fa-solid fa-money-bill-wave w-4 text-center text-xs"></i> Fizetések
            </a>
            <a href="#ertekeles" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors">
                <i class="fa-solid fa-star w-4 text-center text-xs"></i> Értékelések
            </a>
            <a href="#szabadsag" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors">
                <i class="fa-solid fa-umbrella-beach w-4 text-center text-xs"></i> Szabadság
            </a>
            <a href="#beosztas" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors">
                <i class="fa-solid fa-calendar-days w-4 text-center text-xs"></i> Beosztás
            </a>
            <a href="#szamlak" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors">
                <i class="fa-solid fa-file-invoice w-4 text-center text-xs"></i> Számlák
            </a>
            <a href="#selejt" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors">
                <i class="fa-solid fa-barcode w-4 text-center text-xs"></i> Selejt kezelés
            </a>
            <a href="#chat" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors">
                <i class="fa-solid fa-comments w-4 text-center text-xs"></i> Chat
            </a>
            <a href="#mobil" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors">
                <i class="fa-solid fa-mobile-screen w-4 text-center text-xs"></i> Mobil használat
            </a>
            <a href="#gyik" class="nav-link flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-on-surface-variant hover:bg-surface-container transition-colors">
                <i class="fa-solid fa-circle-question w-4 text-center text-xs"></i> GYIK
            </a>
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
                <option value="#kezdolap">Kezdőlap</option>
                <option value="#konyveles">Könyvelés</option>
                <option value="#fizetes">Fizetések</option>
                <option value="#ertekeles">Értékelések</option>
                <option value="#szabadsag">Szabadság</option>
                <option value="#beosztas">Beosztás</option>
                <option value="#szamlak">Számlák</option>
                <option value="#selejt">Selejt kezelés</option>
                <option value="#chat">Chat</option>
                <option value="#mobil">Mobil használat</option>
                <option value="#gyik">GYIK</option>
            </select>
        </div>

        <!-- BEVEZETÉS -->
        <section id="bevezetes" class="mb-12">
            <div class="bg-sidebar rounded-2xl p-8 text-white mb-8">
                <h2 class="font-heading font-extrabold text-3xl mb-2">Üdvözlünk!</h2>
                <p class="text-gray-400 text-sm leading-relaxed max-w-2xl">
                    Ez az útmutató segít eligazodni az Elite Fashion Ügyviteli Rendszerben.
                    Minden funkciót lépésről lépésre bemutatunk, képernyőképekkel illusztrálva.
                    Ha bármilyen kérdésed van, használd a rendszerbe épített <strong class="text-accent">Chat</strong> funkciót.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-surface-container-lowest rounded-xl p-5 border-l-4 border-primary-container">
                    <i class="fa-solid fa-bolt text-primary text-lg mb-2"></i>
                    <h4 class="font-heading font-bold text-sm mb-1">Gyors és egyszerű</h4>
                    <p class="text-xs text-on-surface-variant">A rendszer mobiltelefonról is teljesen használható.</p>
                </div>
                <div class="bg-surface-container-lowest rounded-xl p-5 border-l-4 border-primary-container">
                    <i class="fa-solid fa-shield-halved text-primary text-lg mb-2"></i>
                    <h4 class="font-heading font-bold text-sm mb-1">Biztonságos</h4>
                    <p class="text-xs text-on-surface-variant">Minden adat titkosított kapcsolaton keresztül közlekedik.</p>
                </div>
                <div class="bg-surface-container-lowest rounded-xl p-5 border-l-4 border-primary-container">
                    <i class="fa-solid fa-clock-rotate-left text-primary text-lg mb-2"></i>
                    <h4 class="font-heading font-bold text-sm mb-1">Automatikus mentés</h4>
                    <p class="text-xs text-on-surface-variant">A selejt szkennelés és más műveletek azonnal mentődnek.</p>
                </div>
            </div>
        </section>

        <!-- BEJELENTKEZÉS -->
        <section id="bejelentkezes" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-4 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">1</span>
                Bejelentkezés
            </h2>
            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            A rendszert a böngészőben éred el. Írd be a kapott webcímet, és jelentkezz be az e-mail címeddel és jelszavaddal.
                        </p>
                        <div class="space-y-3">
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                <p class="text-sm">Nyisd meg a böngészőt és írd be az oldal címét</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                <p class="text-sm">Add meg az <strong>e-mail címedet</strong> és a <strong>jelszavadat</strong></p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                <p class="text-sm">Pipáld be az <strong>"Emlékezz rám"</strong> opciót, hogy ne kelljen mindig bejelentkezni</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                                <p class="text-sm">Kattints a <strong>"Bejelentkezés"</strong> gombra</p>
                            </div>
                        </div>
                        <div class="mt-4 bg-amber-50 border border-amber-200 rounded-lg p-3">
                            <p class="text-xs text-amber-700"><i class="fa-solid fa-triangle-exclamation mr-1"></i> <strong>Fontos:</strong> Ha elfelejtettéd a jelszavadat, szólj a vezetőnek — ő tud újat beállítani.</p>
                        </div>
                    </div>
                    <img src="<?= base_url('/docs/screenshots/login.png') ?>" alt="Bejelentkezés" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                </div>
            </div>
        </section>

        <!-- KEZDŐLAP -->
        <section id="kezdolap" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-4 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">2</span>
                Kezdőlap
            </h2>
            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            A kezdőlap a bejelentkezés után jelenik meg. Itt egy pillanat alatt áttekintheted a napi teendőket.
                        </p>
                        <h4 class="font-heading font-bold text-sm mb-2">Amit itt látsz:</h4>
                        <ul class="space-y-2 text-sm">
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span><strong>Napi feladatok</strong> — mely teendők vannak még hátra (könyvelés, selejt, stb.)</span></li>
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span><strong>Közös chat</strong> — írhatsz üzenetet a többi boltnak/vezetőnek</span></li>
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span><strong>Napi összesítő</strong> — a legfontosabb számok egy helyen</span></li>
                        </ul>
                        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <p class="text-xs text-blue-700"><i class="fa-solid fa-lightbulb mr-1"></i> <strong>Tipp:</strong> A bal oldali menüből bármikor eléred az összes funkciót.</p>
                        </div>
                    </div>
                    <img src="<?= base_url('/docs/screenshots/dashboard.png') ?>" alt="Kezdőlap" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                </div>
            </div>
        </section>

        <!-- KÖNYVELÉS -->
        <section id="konyveles" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-4 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">3</span>
                Könyvelés
            </h2>
            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            A Könyvelés oldalon rögzítheted a napi pénzügyi tételeket: bevételt, kiadásokat, befizetéseket.
                        </p>
                        <h4 class="font-heading font-bold text-sm mb-2">Hogyan rögzíts új tételt:</h4>
                        <div class="space-y-3">
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                <p class="text-sm">Kattints az <strong>"Új tétel"</strong> gombra</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                <p class="text-sm">Válaszd ki a <strong>típust</strong> (bevétel, kiadás, stb.)</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                <p class="text-sm">Írd be az <strong>összeget</strong> és a <strong>megjegyzést</strong></p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                                <p class="text-sm">Kattints a <strong>"Mentés"</strong> gombra</p>
                            </div>
                        </div>
                    </div>
                    <img src="<?= base_url('/docs/screenshots/finance.png') ?>" alt="Könyvelés" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                </div>
            </div>
        </section>

        <!-- FIZETÉSEK -->
        <section id="fizetes" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-4 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">4</span>
                Fizetések
            </h2>
            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            Itt láthatod a saját fizetésed adatait: kifizetések, jutalmak, levonások.
                        </p>
                        <h4 class="font-heading font-bold text-sm mb-2">Amit itt találsz:</h4>
                        <ul class="space-y-2 text-sm">
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span>Korábbi kifizetések listája dátummal és összeggel</span></li>
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span>Új fizetés rögzítése</span></li>
                        </ul>
                    </div>
                    <img src="<?= base_url('/docs/screenshots/salary.png') ?>" alt="Fizetések" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                </div>
            </div>
        </section>

        <!-- ÉRTÉKELÉSEK -->
        <section id="ertekeles" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-4 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">5</span>
                Értékelések
            </h2>
            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            Az értékelések oldalon havi teljesítményértékeléseket rögzíthetsz a dolgozókról.
                        </p>
                        <h4 class="font-heading font-bold text-sm mb-2">Új értékelés rögzítése:</h4>
                        <div class="space-y-3">
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                <p class="text-sm">Kattints az <strong>"Új értékelés"</strong> gombra</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                <p class="text-sm">Válaszd ki a <strong>dolgozót</strong> és a <strong>hónapot</strong></p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                <p class="text-sm">Töltsd ki a pontszámokat és mentsd el</p>
                            </div>
                        </div>
                    </div>
                    <img src="<?= base_url('/docs/screenshots/evaluations.png') ?>" alt="Értékelések" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                </div>
            </div>
        </section>

        <!-- SZABADSÁG -->
        <section id="szabadsag" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-4 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">6</span>
                Szabadság
            </h2>
            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            Szabadság igénylése és nyomon követése. Láthatod a kért, jóváhagyott és elutasított napokat.
                        </p>
                        <h4 class="font-heading font-bold text-sm mb-2">Szabadság igénylése:</h4>
                        <div class="space-y-3">
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                <p class="text-sm">Kattints az <strong>"Új igénylés"</strong> gombra</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                <p class="text-sm">Válaszd ki a <strong>kezdő</strong> és <strong>befejező dátumot</strong></p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                <p class="text-sm">Add meg az <strong>okot</strong> (szabadság, betegszabadság, stb.)</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                                <p class="text-sm">Küld el — a vezető <strong>jóváhagyja vagy elutasítja</strong></p>
                            </div>
                        </div>
                    </div>
                    <img src="<?= base_url('/docs/screenshots/vacation.png') ?>" alt="Szabadság" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                </div>
            </div>
        </section>

        <!-- BEOSZTÁS -->
        <section id="beosztas" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-4 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">7</span>
                Beosztás
            </h2>
            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            A heti/havi munkabeosztás naptár nézetben. Láthatod, mikor kell bemenned dolgozni.
                        </p>
                        <h4 class="font-heading font-bold text-sm mb-2">Amit itt tudsz:</h4>
                        <ul class="space-y-2 text-sm">
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span>Saját beosztásod megtekintése naptár nézetben</span></li>
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span>Műszakok áttekintése (reggel/délután)</span></li>
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span>Módosítás kérése a vezetőtől</span></li>
                        </ul>
                    </div>
                    <img src="<?= base_url('/docs/screenshots/schedule.png') ?>" alt="Beosztás" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                </div>
            </div>
        </section>

        <!-- SZÁMLÁK -->
        <section id="szamlak" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-4 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">8</span>
                Számlák
            </h2>
            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            Bejövő számlák kezelése — rögzítés, fizetési állapot követés, lejárat figyelés.
                        </p>
                        <h4 class="font-heading font-bold text-sm mb-2">Új számla rögzítése:</h4>
                        <div class="space-y-3">
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                <p class="text-sm">Kattints az <strong>"Új számla"</strong> gombra</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                <p class="text-sm">Töltsd ki a szállító nevét, összeget, határidőt</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                <p class="text-sm">Kifizetés után jelöld <strong>"Fizetett"</strong>-nek</p>
                            </div>
                        </div>
                    </div>
                    <img src="<?= base_url('/docs/screenshots/invoices.png') ?>" alt="Számlák" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                </div>
            </div>
        </section>

        <!-- SELEJT -->
        <section id="selejt" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-4 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">9</span>
                Selejt kezelés
            </h2>
            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            Selejtesnek ítélt termékek rögzítése vonalkód szkennerrel. A rendszer automatikusan felismeri a terméket és kiírja a nevét és árát.
                        </p>
                        <h4 class="font-heading font-bold text-sm mb-2">Selejt rögzítése:</h4>
                        <div class="space-y-3">
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">1</span>
                                <p class="text-sm">Menj a <strong>Selejt</strong> oldalra</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">2</span>
                                <p class="text-sm">A vonalkód szkenner automatikusan a <strong>beviteli mezőbe</strong> ír</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">3</span>
                                <p class="text-sm"><strong>Olvasd be a vonalkódot</strong> — a rendszer automatikusan menti</p>
                            </div>
                            <div class="flex gap-3 items-start">
                                <span class="step-num rounded-full bg-surface-container text-on-surface-variant text-[11px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">4</span>
                                <p class="text-sm">Megjelenik a <strong>termék neve</strong> és <strong>bruttó ára</strong></p>
                            </div>
                        </div>
                        <div class="mt-4 bg-green-50 border border-green-200 rounded-lg p-3">
                            <p class="text-xs text-green-700"><i class="fa-solid fa-circle-info mr-1"></i> A nap végén add meg a <strong>napi selejt összértéket</strong> az alsó mezőben. A becsült értéket a rendszer automatikusan számolja.</p>
                        </div>
                    </div>
                    <img src="<?= base_url('/docs/screenshots/defects.png') ?>" alt="Selejt kezelés" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                </div>
            </div>
        </section>

        <!-- CHAT -->
        <section id="chat" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-4 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">10</span>
                Chat
            </h2>
            <div class="bg-surface-container-lowest rounded-xl p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                    <div>
                        <p class="text-sm text-on-surface-variant mb-4 leading-relaxed">
                            Valós idejű üzenetküldés a többi bolttal és a vezetőséggel. Van közös és privát chat is.
                        </p>
                        <h4 class="font-heading font-bold text-sm mb-2">Használata:</h4>
                        <ul class="space-y-2 text-sm">
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span><strong>Közös chat</strong> — mindenki látja, aki be van jelentkezve</span></li>
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span><strong>Privát üzenet</strong> — kattints egy személyre a bal oldali listában</span></li>
                            <li class="flex gap-2"><i class="fa-solid fa-check text-primary mt-1"></i> <span><strong>Üzenet visszavonása</strong> — a saját üzeneteden lévő <i class="fa-solid fa-trash-can text-xs"></i> ikonra kattintva</span></li>
                        </ul>
                        <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <p class="text-xs text-blue-700"><i class="fa-solid fa-lightbulb mr-1"></i> <strong>Tipp:</strong> A kezdőlapon is elérheted a közös chatet anélkül, hogy átnavigálnál.</p>
                        </div>
                    </div>
                    <img src="<?= base_url('/docs/screenshots/chat.png') ?>" alt="Chat" class="doc-img rounded-xl w-full" onclick="openLightbox(this.src)">
                </div>
            </div>
        </section>

        <!-- MOBIL HASZNÁLAT -->
        <section id="mobil" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-4 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center"><i class="fa-solid fa-mobile-screen text-[10px]"></i></span>
                Mobil használat
            </h2>
            <div class="bg-surface-container-lowest rounded-xl p-6">
                <p class="text-sm text-on-surface-variant mb-6 leading-relaxed">
                    A rendszer teljesen használható telefonról is. Ugyanazt látod mint számítógépen, mobilra optimalizált elrendezésben.
                </p>
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
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <p class="text-xs text-blue-700"><i class="fa-solid fa-lightbulb mr-1"></i> <strong>Tipp:</strong> A böngészőben add hozzá a kezdőképernyőhöz ("Add to Home Screen") — úgy úgy nyílik meg, mint egy valódi alkalmazás!</p>
                </div>
            </div>
        </section>

        <!-- GYIK -->
        <section id="gyik" class="mb-12">
            <h2 class="font-heading font-extrabold text-2xl text-on-surface mb-4 flex items-center gap-3">
                <span class="step-num rounded-full bg-primary-container text-primary text-xs font-bold flex items-center justify-center">?</span>
                Gyakran Ismételt Kérdések
            </h2>
            <div class="space-y-3">
                <details class="bg-surface-container-lowest rounded-xl overflow-hidden group">
                    <summary class="p-5 cursor-pointer flex items-center justify-between hover:bg-surface-container-low transition-colors">
                        <span class="font-heading font-bold text-sm">Elfelejtettem a jelszavamat. Mit tegyek?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-on-surface-variant group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-on-surface-variant">Szólj a boltvezetőnek vagy a tulajdonosnak — ő tud új jelszót beállítani a fiókok kezelésében.</div>
                </details>

                <details class="bg-surface-container-lowest rounded-xl overflow-hidden group">
                    <summary class="p-5 cursor-pointer flex items-center justify-between hover:bg-surface-container-low transition-colors">
                        <span class="font-heading font-bold text-sm">A vonalkód szkenner nem működik. Mi a teendő?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-on-surface-variant group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-on-surface-variant">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Ellenőrizd, hogy a kurzor a vonalkód beviteli mezőben áll-e (kattints bele)</li>
                            <li>Ellenőrizd, hogy a szkenner USB-vel csatlakozik-e</li>
                            <li>Próbáld meg kézzel beírni a vonalkódot és nyomj Entert</li>
                            <li>Ha semmi sem működik, frissítsd az oldalt (F5 vagy Ctrl+R)</li>
                        </ul>
                    </div>
                </details>

                <details class="bg-surface-container-lowest rounded-xl overflow-hidden group">
                    <summary class="p-5 cursor-pointer flex items-center justify-between hover:bg-surface-container-low transition-colors">
                        <span class="font-heading font-bold text-sm">Hogyan használjam telefonról?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-on-surface-variant group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-on-surface-variant">
                        Nyisd meg a böngészőt a telefonodon, írd be a webcímet és jelentkezz be.
                        Az oldal automatikusan mobilra optimalizálva jelenik meg.
                        A bal felső sarokban a <i class="fa-solid fa-bars text-xs"></i> ikonnal nyithatod ki a menüt.
                    </div>
                </details>

                <details class="bg-surface-container-lowest rounded-xl overflow-hidden group">
                    <summary class="p-5 cursor-pointer flex items-center justify-between hover:bg-surface-container-low transition-colors">
                        <span class="font-heading font-bold text-sm">Rossz adatot rögzítettem. Lehet törölni?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-on-surface-variant group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-on-surface-variant">A legtöbb tétel törlését csak a tulajdonos tudja megtenni. Szólj a vezetőnek, aki ki tudja törölni a hibás tételt.</div>
                </details>

                <details class="bg-surface-container-lowest rounded-xl overflow-hidden group">
                    <summary class="p-5 cursor-pointer flex items-center justify-between hover:bg-surface-container-low transition-colors">
                        <span class="font-heading font-bold text-sm">Nem látom valamelyik menüpontot. Miért?</span>
                        <i class="fa-solid fa-chevron-down text-xs text-on-surface-variant group-open:rotate-180 transition-transform"></i>
                    </summary>
                    <div class="px-5 pb-5 text-sm text-on-surface-variant">A menüpontok jogosultság alapján jelennek meg. Ha valamit nem látsz, az azt jelenti, hogy a fiókodon nincs bekapcsolva az adott funkció. Kérd meg a tulajdonost, hogy állítsa be a Jogosultságok oldalon.</div>
                </details>
            </div>
        </section>

    </main>
</div>

<!-- LÁBLÉC -->
<footer class="bg-sidebar py-6 mt-12">
    <div class="max-w-7xl mx-auto px-6 text-center">
        <p class="text-gray-500 text-xs">Elite Fashion Ügyviteli Rendszer &copy; <?= date('Y') ?></p>
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
const sections = document.querySelectorAll('section[id]');
const navLinks = document.querySelectorAll('.nav-link');
window.addEventListener('scroll', function() {
    let current = '';
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
