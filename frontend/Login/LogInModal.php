<?php
 $loginUrl = 'backend/Login/login.php';
?>
<!-- Login modal -->
<dialog id="login-modal" aria-labelledby="login-title"
        class="m-auto w-[calc(100%-2rem)] max-w-md rounded-3xl border border-white/10 bg-navy-900 p-0 text-slate-300 shadow-2xl shadow-black/60 backdrop:bg-navy-950/80 backdrop:backdrop-blur-sm">
    <div class="relative p-7 sm:p-9">
        <button type="button" data-close-login aria-label="Close"
                class="absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full text-slate-400 transition hover:bg-white/10 hover:text-white">✕</button>

        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-white font-serif text-xl font-bold text-navy-950" aria-hidden="true">N</div>
        <h2 id="login-title" class="mt-5 font-serif text-3xl font-bold text-white">Welcome back</h2>
        <p class="mt-1 text-sm text-slate-400">Log in to your <?= e($site['name']) ?> account.</p>

        <form id="login-form" method="post" action="<?= e($loginUrl) ?>" data-connected="<?= $loginUrl !== '' ? '1' : '0' ?>" class="mt-7 space-y-5">
            <input type="hidden" name="_csrf" value="<?= e($_SESSION['csrf']) ?>">

            <div>
                <label for="login-username" class="text-sm font-medium text-slate-200">Username</label>
                <input id="login-username" name="username" type="text" required maxlength="100" autocomplete="username" placeholder="Your username" autocapitalize="none" spellcheck="false"
                       class="mt-2 w-full rounded-xl border border-white/15 bg-navy-950 px-4 py-3 text-white placeholder-slate-500 focus:border-gold focus:outline-none">
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label for="login-password" class="text-sm font-medium text-slate-200">Password</label>
                    <a href="#" class="text-xs text-gold hover:text-amber-300">Forgot password?</a>
                </div>
                <div class="relative mt-2">
                    <input id="login-password" name="password" type="password" required minlength="8" autocomplete="current-password" placeholder="••••••••"
                           class="w-full rounded-xl border border-white/15 bg-navy-950 py-3 pl-4 pr-16 text-white placeholder-slate-500 focus:border-gold focus:outline-none">
                    <button type="button" id="toggle-pw" aria-pressed="false" aria-controls="login-password"
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded px-1 text-xs font-medium text-slate-400 hover:text-white">Show</button>
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-400">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-white/20 bg-navy-950 accent-amber-400">
                Remember me
            </label>

            <p id="login-msg" role="alert" class="hidden rounded-xl bg-blue-500/10 px-4 py-3 text-sm text-blue-200"></p>

            <button type="submit" class="w-full rounded-full bg-gold px-7 py-3 text-sm font-semibold text-navy-950 transition hover:bg-amber-300">Log in</button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-500">Need help? Call <a class="text-slate-300 underline underline-offset-2" href="tel:<?= e($site['tel']) ?>"><?= e($site['phone']) ?></a></p>
    </div>
    <?php require __DIR__ . '/../components/notification.php'; ?>
</dialog>

<script src="frontend/js/LogInModal.js" defer></script>
