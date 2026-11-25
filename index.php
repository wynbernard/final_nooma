<?php
declare(strict_types=1);

require_once __DIR__ . '/backend/database/session.php';

// ---- Edit your content here ----
$site = [
    'name'    => 'NOOMA Kitchen+Bar',
    'phone'   => '0917 801 2949',
    'tel'     => '+639178012949',
    'address' => 'Lacson St, Bacolod, 6100 Negros Occidental',
    'plus'    => 'MXJ4+X6C',
    'opens'   => '11:00 AM Daily',
    'price'   => '₱500–1,000',
    'rating'  => '4.4',
    'reviews' => 50,
    'maps'    => 'https://www.google.com/maps/search/?api=1&query=NOOMA+Kitchen+Bar+Lacson+St+Bacolod',
];

// [name, description, tag]
$menu = [
    ['Beef Pares',    'Slow-braised savory beef paired with aromatic garlic rice and rich broth—a reviewer-approved must-try.', 'Must-try'],
    ['Crispy Shrimp', 'Golden, delicate crunch tossed in house signature seasoning. Highlighted on our kitchen menu.',             'Highlight'],
    ['Tuna Kinilaw',  'Fresh catch cured in bright citrus, ginger, and local chilies. Frequently mentioned by guests.',         'Guest favorite'],
    ['Kare-Kare',     'Velvety roasted peanut stew crafted in the classic Filipino tradition, served with house bagoong.',     'Guest favorite'],
    ['Salad',         'Crisp seasonal greens dressed in a light, refreshing house vinaigrette.',                                'Highlight'],
    ['Cocktails',     'Handcrafted evening pours—our balanced Amaretto Sour remains a perennial guest pick.',                  'Guest favorite'],
];

$reviews = [
    ['KT A',       'The Beef Pares is a must-try!'],
    ['Kuhh',       'Crispy Shrimp & Amaretto Sour was delicious.'],
    ['Ela Melits', 'The interior space is nice and aesthetically pleasing.'],
];

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function menuImage(string $name): ?string
{
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
    foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
        $path = "frontend/public/images/{$slug}.{$ext}";
        if (is_file(__DIR__ . '/' . str_replace('/', DIRECTORY_SEPARATOR, $path))) {
            return $path;
        }
    }
    return null;
}

$btnGold  = 'inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-full bg-gold px-8 py-3.5 text-xs font-semibold uppercase tracking-[0.18em] text-navy-950 shadow-lg shadow-gold/15 transition duration-300 hover:-translate-y-0.5 hover:bg-amber-300';
$btnGhost = 'inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-full border border-white/20 bg-white/[0.02] px-8 py-3.5 text-xs font-semibold uppercase tracking-[0.18em] text-white backdrop-blur-sm transition duration-300 hover:-translate-y-0.5 hover:border-gold/70 hover:text-gold';
$eyebrow  = 'inline-flex items-center gap-3 text-[11px] font-semibold uppercase tracking-[0.28em] text-gold';
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#050b1a">
    <title>NOOMA Kitchen + Bar | Bacolod City</title>
    <link rel="icon" type="image/png" href="<?= e(url('/frontend/public/assets/image.png')) ?>">
    <meta name="description" content="<?= e($site['name']) ?> in Bacolod: dine-in and takeout, beef pares, crispy shrimp, cocktails. <?= e($site['price']) ?> per person.">
    <?php require __DIR__ . '/frontend/links/tailwind.php'; ?>
</head>
<body class="overflow-x-hidden bg-navy-950 font-sans text-slate-300 antialiased selection:bg-gold/30 selection:text-white">

<!-- Subtle Top Ambient Glow -->
<div class="pointer-events-none fixed inset-x-0 top-0 z-0 h-[520px] bg-[radial-gradient(55%_60%_at_50%_-10%,rgba(232,176,74,0.10),transparent)]"></div>

<!-- Navigation -->
<header class="fixed inset-x-0 top-4 z-40 px-4 sm:top-6">
    <div class="mx-auto flex max-w-4xl items-center justify-between gap-4 rounded-full border border-white/10 bg-navy-950/80 px-3 py-2 shadow-2xl shadow-black/60 backdrop-blur-xl">
        <a href="#top" aria-label="<?= e($site['name']) ?>" class="flex h-10 w-28 shrink-0 items-center justify-center overflow-hidden rounded-full px-2 transition opacity-95 hover:opacity-100 sm:w-32">
            <img src="frontend/public/assets/image.png" alt="<?= e($site['name']) ?>" class="h-full w-full object-contain">
        </a>

        <nav aria-label="Main" class="hidden items-center gap-8 text-xs font-medium uppercase tracking-[0.2em] text-slate-300 md:flex">
            <a href="#menu" class="py-1 transition hover:text-gold">Menu</a>
            <a href="#vibe" class="py-1 transition hover:text-gold">Atmosphere</a>
            <a href="#reviews" class="py-1 transition hover:text-gold">Reviews</a>
            <a href="#visit" class="py-1 transition hover:text-gold">Visit</a>
             <button type="button" data-open-login class="rounded-2xl px-4 py-3 text-left font-medium text-gold hover:bg-white/10">Log in</button>
        </nav>

        <div class="flex items-center gap-2">
            <a href="tel:<?= e($site['tel']) ?>" class="whitespace-nowrap rounded-full border border-gold/40 bg-gold/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.15em] text-gold transition duration-300 hover:bg-gold hover:text-navy-950 sm:px-5">
                <span class="sm:hidden">Reserve</span>
                <span class="hidden sm:inline"><?= e($site['phone']) ?></span>
            </a>
            <button id="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu" aria-label="Open menu" class="flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-white/[0.03] text-white transition hover:border-gold/50 md:hidden">
                <span class="flex flex-col gap-1.5" aria-hidden="true">
                    <span class="block h-[1.5px] w-4 bg-white"></span>
                    <span class="block h-[1.5px] w-4 bg-white"></span>
                </span>
            </button>
        </div>
    </div>

    <!-- Mobile Dropdown -->
    <div id="mobile-menu" class="mx-auto mt-2.5 hidden max-w-4xl rounded-3xl border border-white/10 bg-navy-900/95 p-4 shadow-2xl shadow-black/70 backdrop-blur-xl md:hidden">
        <nav aria-label="Mobile" class="flex flex-col divide-y divide-white/5 text-xs font-semibold uppercase tracking-[0.2em]">
            <a href="#menu" class="px-4 py-3.5 text-slate-200 transition hover:text-gold">Menu Highlights</a>
            <a href="#vibe" class="px-4 py-3.5 text-slate-200 transition hover:text-gold">Day &amp; Night Vibe</a>
            <a href="#reviews" class="px-4 py-3.5 text-slate-200 transition hover:text-gold">Guest Reviews</a>
            <a href="#visit" class="px-4 py-3.5 text-slate-200 transition hover:text-gold">Location &amp; Hours</a>
            <button type="button" data-open-login class="px-4 py-3.5 text-left text-gold transition hover:text-amber-300">Log in</button>
        </nav>
    </div>
</header>

<main id="top" class="relative z-10">
    <!-- Hero Section -->
    <section class="relative overflow-hidden border-b border-white/[0.06]">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(50%_50%_at_85%_15%,rgba(232,176,74,0.12),transparent),radial-gradient(45%_45%_at_5%_95%,rgba(30,58,138,0.18),transparent)]"></div>

        <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-5 pb-20 pt-28 sm:px-8 sm:pb-28 sm:pt-36 lg:grid-cols-[1.15fr_0.85fr] lg:gap-16 lg:pt-44">
            <div class="text-center lg:text-left">
                <p class="<?= $eyebrow ?> justify-center lg:justify-start">
                    <span class="h-px w-6 bg-gold/60"></span>
                    Kitchen &amp; Cocktail Bar · Bacolod
                </p>

                <!-- Framed Brand Crest + Editorial Title -->
                <div class="mt-7 flex flex-col items-center gap-6 sm:flex-row lg:items-center">
                    <div class="relative shrink-0">
                        <div class="absolute -inset-1.5 rounded-full bg-gradient-to-tr from-gold/40 via-gold/10 to-transparent blur-sm"></div>
                        <img
                            src="frontend/public/assets/image.png"
                            alt="<?= e($site['name']) ?> Emblem"
                            class="relative h-28 w-28 rounded-full border border-gold/30 bg-navy-900 object-cover p-1 shadow-2xl sm:h-32 sm:w-32"
                        />
                    </div>
                    <div>
                        <h1 class="font-serif text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-[3.35rem] lg:leading-[1.08]">
                            NOOMA<br>
                            <span class="font-normal italic text-gold">Kitchen + Bar</span>
                        </h1>
                    </div>
                </div>

                <p class="mx-auto mt-7 max-w-xl text-base leading-relaxed text-slate-400 sm:text-lg lg:mx-0">
                    Honest Filipino comfort food crafted with care, an intimate dining room by day, and a spirited cocktail lounge once evening settles over Lacson Street.
                </p>

                <div class="mt-9 flex flex-col items-center gap-3.5 sm:flex-row sm:justify-center lg:justify-start">
                    <a href="tel:<?= e($site['tel']) ?>" class="<?= $btnGold ?>">
                        <span>Reserve a Table</span>
                    </a>
                    <a href="<?= e($site['maps']) ?>" target="_blank" rel="noopener noreferrer" class="<?= $btnGhost ?>">
                        <span>Get Directions</span>
                    </a>
                </div>
            </div>

            <!-- At a Glance Concierge Card -->
            <aside aria-label="At a glance" class="relative rounded-3xl border border-white/10 bg-gradient-to-b from-white/[0.06] to-white/[0.02] p-7 shadow-2xl shadow-black/50 backdrop-blur-md sm:p-9">
                <div class="flex items-center justify-between border-b border-white/10 pb-6">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.25em] text-gold">Guest Rating</p>
                        <p class="mt-1 text-xs text-slate-400">Verified Google Reviews</p>
                    </div>
                    <div class="flex items-baseline gap-2.5 text-right">
                        <span class="font-serif text-5xl font-bold tracking-tight text-white"><?= e($site['rating']) ?></span>
                        <div>
                            <div class="text-xs tracking-widest text-gold" aria-hidden="true">★★★★★</div>
                            <p class="mt-0.5 text-[11px] text-slate-400"><?= (int) $site['reviews'] ?> reviews</p>
                        </div>
                    </div>
                </div>

                <dl class="mt-2 divide-y divide-white/[0.07] text-sm">
                    <div class="flex items-center justify-between gap-4 py-4">
                        <dt class="text-xs uppercase tracking-[0.18em] text-slate-500">Average Spend</dt>
                        <dd class="font-medium text-white"><?= e($site['price']) ?> <span class="text-slate-400">/ guest</span></dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-4">
                        <dt class="text-xs uppercase tracking-[0.18em] text-slate-500">Dining Options</dt>
                        <dd class="font-medium text-white">Dine-in · Takeout</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 py-4">
                        <dt class="text-xs uppercase tracking-[0.18em] text-slate-500">Hours</dt>
                        <dd class="font-medium text-white">Opens <?= e($site['opens']) ?></dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 pt-4">
                        <dt class="text-xs uppercase tracking-[0.18em] text-slate-500">Neighborhood</dt>
                        <dd class="font-medium text-white">Lacson St, Bacolod</dd>
                    </div>
                </dl>
            </aside>
        </div>
    </section>

    <!-- Menu Highlights -->
    <section id="menu" class="mx-auto max-w-6xl scroll-mt-28 px-5 py-20 sm:px-8 sm:py-28">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
            <div>
                <p class="<?= $eyebrow ?>">
                    <span class="h-px w-6 bg-gold/60"></span>
                    Curated Selection
                </p>
                <h2 class="mt-3 font-serif text-3xl font-bold text-white sm:text-5xl">Menu Highlights</h2>
            </div>
            <p class="max-w-md text-sm leading-relaxed text-slate-400 sm:text-base">
                A focused repertoire of house classics and evening pours. Ask our servers about tonight’s off-menu specials.
            </p>
        </div>

        <div class="mt-12 grid gap-7 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($menu as [$name, $desc, $tag]): $img = menuImage($name); ?>
                <article class="group flex flex-col overflow-hidden rounded-3xl border border-white/10 bg-white/[0.025] transition duration-500 hover:-translate-y-1.5 hover:border-gold/40 hover:bg-white/[0.04] hover:shadow-2xl hover:shadow-black/50">
                    <div class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br from-navy-900 via-navy-950 to-black">
                        <?php if ($img): ?>
                            <img src="<?= e($img) ?>" alt="<?= e($name) ?>" width="800" height="600" loading="lazy"
                                 class="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-105">
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-navy-950/70 via-transparent to-transparent"></div>
                        <?php else: ?>
                            <!-- Editorial Monogram Frame for Upcoming Photos -->
                            <div class="relative flex h-full w-full flex-col items-center justify-center p-6 text-center">
                                <div class="absolute inset-4 rounded-2xl border border-white/[0.06]"></div>
                                <div class="flex h-16 w-16 items-center justify-center rounded-full border border-gold/30 bg-gold/[0.06]">
                                    <span class="font-serif text-3xl italic text-gold"><?= e(mb_substr($name, 0, 1)) ?></span>
                                </div>
                                <span class="mt-3 text-[10px] font-medium uppercase tracking-[0.28em] text-slate-500">Kitchen Preview</span>
                            </div>
                        <?php endif; ?>

                        <span class="absolute left-4 top-4 rounded-full border border-white/15 bg-navy-950/85 px-3.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-gold backdrop-blur-md">
                            <?= e($tag) ?>
                        </span>
                    </div>

                    <div class="flex flex-1 flex-col justify-between p-6 sm:p-7">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-serif text-2xl font-semibold text-white transition group-hover:text-gold"><?= e($name) ?></h3>
                            </div>
                            <p class="mt-2.5 text-sm leading-relaxed text-slate-400"><?= e($desc) ?></p>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Atmosphere / Vibe -->
    <section id="vibe" class="scroll-mt-28 border-y border-white/[0.08] bg-navy-900/40">
        <div class="mx-auto max-w-6xl px-5 py-20 sm:px-8 sm:py-28">
            <div class="max-w-2xl">
                <p class="<?= $eyebrow ?>">
                    <span class="h-px w-6 bg-gold/60"></span>
                    The Atmosphere
                </p>
                <h2 class="mt-3 font-serif text-3xl font-bold text-white sm:text-5xl">Two moods, one address</h2>
                <p class="mt-4 text-base leading-relaxed text-slate-400">
                    Guests love our warm, thoughtfully styled interior. Around 9:30 PM, the lights dim and the energy shifts—bringing handcrafted cocktails, welcoming bartenders, and curated music. Because the room is intimate, we recommend reserving ahead for peak hours.
                </p>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-2">
                <!-- Day Card -->
                <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-amber-300/[0.09] via-navy-950/80 to-navy-950 p-8 sm:p-10">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.25em] text-gold">Daytime · From 11:00 AM</span>
                        <span class="h-2 w-2 rounded-full bg-amber-300/80"></span>
                    </div>
                    <h3 class="mt-5 font-serif text-3xl font-semibold text-white">Cozy, unhurried dining</h3>
                    <p class="mt-3 text-sm leading-relaxed text-slate-400 sm:text-base">
                        Sunlit lunches and relaxed family dinners with attentive table service and hearty Filipino favorites.
                    </p>
                </div>

                <!-- Night Card -->
                <div class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-indigo-500/[0.14] via-navy-950/80 to-navy-950 p-8 sm:p-10">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-[0.25em] text-gold">Evening · From ~9:30 PM</span>
                        <span class="h-2 w-2 rounded-full bg-indigo-400/80"></span>
                    </div>
                    <h3 class="mt-5 font-serif text-3xl font-semibold text-white">Craft bar &amp; late-night music</h3>
                    <p class="mt-3 text-sm leading-relaxed text-slate-400 sm:text-base">
                        Signature cocktails, live DJ sets, and a vibrant late-night crowd in an intimate lounge setting.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Guest Reviews -->
    <section id="reviews" class="mx-auto max-w-6xl scroll-mt-28 px-5 py-20 sm:px-8 sm:py-28">
        <div class="flex flex-col justify-between gap-6 border-b border-white/10 pb-8 md:flex-row md:items-end">
            <div>
                <p class="<?= $eyebrow ?>">
                    <span class="h-px w-6 bg-gold/60"></span>
                    Testimonials
                </p>
                <h2 class="mt-3 font-serif text-3xl font-bold text-white sm:text-5xl">What guests say</h2>
            </div>
            <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-3">
                <span class="font-serif text-3xl font-bold text-white"><?= e($site['rating']) ?></span>
                <div class="text-xs">
                    <div class="tracking-widest text-gold">★★★★★</div>
                    <p class="text-slate-400">Based on <?= (int) $site['reviews'] ?> Google reviews</p>
                </div>
            </div>
        </div>

        <div class="mt-10 grid gap-6 md:grid-cols-3">
            <?php foreach ($reviews as [$who, $text]): ?>
                <figure class="flex flex-col justify-between rounded-3xl border border-white/10 bg-gradient-to-b from-white/[0.04] to-transparent p-7 sm:p-8">
                    <div>
                        <div class="flex items-center justify-between">
                            <div class="text-xs tracking-widest text-gold" aria-label="5 out of 5 stars">★★★★★</div>
                            <span class="font-serif text-4xl leading-none text-gold/25" aria-hidden="true">”</span>
                        </div>
                        <blockquote class="mt-4 font-serif text-xl leading-relaxed text-slate-100">
                            “<?= e($text) ?>”
                        </blockquote>
                    </div>
                    <figcaption class="mt-8 flex items-center gap-3 border-t border-white/[0.07] pt-4 text-xs uppercase tracking-[0.18em] text-slate-400">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gold/15 font-sans font-bold text-gold">
                            <?= e(mb_substr($who, 0, 1)) ?>
                        </span>
                        <div>
                            <span class="font-semibold text-slate-200"><?= e($who) ?></span>
                            <span class="block text-[10px] tracking-wider text-slate-500">Google Reviewer</span>
                        </div>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Visit & Reservations -->
    <section id="visit" class="scroll-mt-28 px-5 pb-20 sm:px-8 sm:pb-28">
        <div class="mx-auto grid max-w-6xl overflow-hidden rounded-3xl border border-white/15 bg-navy-900/60 shadow-2xl shadow-black/60 backdrop-blur-md md:grid-cols-2">
            <!-- Left Details -->
            <div class="border-b border-white/10 p-8 sm:p-11 md:border-b-0 md:border-r lg:p-14">
                <p class="<?= $eyebrow ?>">
                    <span class="h-px w-6 bg-gold/60"></span>
                    Visit Us
                </p>
                <h2 class="mt-3 font-serif text-3xl font-bold text-white sm:text-4xl">Find our table</h2>

                <dl class="mt-8 divide-y divide-white/10 text-sm">
                    <div class="py-4">
                        <dt class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Address</dt>
                        <dd class="mt-1.5 text-base text-white">
                            <?= e($site['address']) ?>
                            <span class="mt-0.5 block text-xs text-slate-400">Plus Code: <?= e($site['plus']) ?></span>
                        </dd>
                    </div>
                    <div class="py-4">
                        <dt class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Operating Hours</dt>
                        <dd class="mt-1.5 text-base text-white">Opens <?= e($site['opens']) ?></dd>
                    </div>
                    <div class="pt-4">
                        <dt class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Direct Line</dt>
                        <dd class="mt-1.5">
                            <a class="text-base font-medium text-gold underline decoration-gold/40 underline-offset-4 transition hover:text-amber-300" href="tel:<?= e($site['tel']) ?>">
                                <?= e($site['phone']) ?>
                            </a>
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Right CTA -->
            <div class="relative flex flex-col justify-center bg-gradient-to-br from-gold/[0.08] via-navy-950/90 to-navy-950 p-8 sm:p-11 lg:p-14">
                <p class="text-[11px] font-semibold uppercase tracking-[0.25em] text-gold">Reservations &amp; Takeout</p>
                <h3 class="mt-3 font-serif text-3xl font-bold text-white sm:text-4xl">Join us tonight</h3>
                <p class="mt-4 text-sm leading-relaxed text-slate-300 sm:text-base">
                    Whether you're stopping in for afternoon comfort food or late-night drinks with friends, give us a quick call and we'll have your table ready.
                </p>
                <div class="mt-9 flex flex-col gap-3.5 sm:flex-row">
                    <a href="tel:<?= e($site['tel']) ?>" class="<?= $btnGold ?>">Call <?= e($site['phone']) ?></a>
                    <a href="<?= e($site['maps']) ?>" target="_blank" rel="noopener noreferrer" class="<?= $btnGhost ?>">Open in Maps</a>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Footer -->
<footer class="border-t border-white/10 bg-navy-950/90">
    <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-5 py-10 text-center text-xs tracking-wider text-slate-500 sm:px-8 md:flex-row md:text-left">
        <div class="flex items-center gap-3">
            <span class="font-serif text-lg font-semibold tracking-normal text-white"><?= e($site['name']) ?></span>
            <span class="hidden text-white/20 md:inline">|</span>
            <span class="hidden md:inline"><?= e($site['address']) ?></span>
        </div>
        <p class="md:hidden"><?= e($site['address']) ?></p>
        <p>© <?= date('Y') ?> <?= e($site['name']) ?>. All rights reserved.</p>
    </div>
</footer>

<?php require __DIR__ . '/frontend/Login/LogInModal.php'; ?>
<script>
(function () {
    var btn = document.getElementById('menu-toggle'), menu = document.getElementById('mobile-menu');
    if (!btn || !menu) return;
    function set(open) {
        menu.classList.toggle('hidden', !open);
        btn.setAttribute('aria-expanded', String(open));
        btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    }
    btn.addEventListener('click', function () { set(menu.classList.contains('hidden')); });
    menu.addEventListener('click', function (e) { if (e.target.closest('a')) set(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') set(false); });
})();
</script>
</body>
</html>