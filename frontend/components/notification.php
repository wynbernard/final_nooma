<!-- Reusable Popup Notification Modal / Toast -->
<div id="nooma-toast" role="status" aria-live="polite" class="fixed bottom-6 right-6 z-[110] translate-y-20 opacity-0 transition-all duration-300 ease-out pointer-events-none">
    <div id="nooma-toast-content" class="flex items-center gap-3 rounded-2xl border border-white/15 bg-slate-900 px-5 py-4 shadow-2xl shadow-black/70">
        <!-- Status Icon / Symbol -->
        <div id="nooma-toast-icon" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold">
            <!-- Dynamically changes via JS -->
        </div>
        
        <!-- Message Text -->
        <div>
            <p id="nooma-toast-title" class="text-xs font-bold uppercase tracking-wider text-slate-400">Notification</p>
            <p id="nooma-toast-message" class="text-sm font-medium text-white"></p>
        </div>

        <!-- Close Button -->
        <button type="button" onclick="hideNotification()" class="ml-4 text-slate-400 hover:text-white">&times;</button>
    </div>
</div>

<script>
/**
 * Global Notification Popup Handler
 * @param {string} message - The text to display
 * @param {string} type - 'success' or 'error'
 */
function showNotification(message, type = 'success') {
    const toast = document.getElementById('nooma-toast');
    const toastContent = document.getElementById('nooma-toast-content');
    const toastIcon = document.getElementById('nooma-toast-icon');
    const toastTitle = document.getElementById('nooma-toast-title');
    const toastMessage = document.getElementById('nooma-toast-message');

    if (!toast) return;

    // Set content
    toastMessage.textContent = message;

    // Apply color schemes based on type
    if (type === 'success') {
        toastTitle.textContent = 'Success';
        toastIcon.className = 'flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
        toastIcon.innerHTML = '✓';
        toastContent.className = 'flex items-center gap-3 rounded-2xl border border-emerald-500/30 bg-slate-900 px-5 py-4 shadow-2xl shadow-black/70';
    } else {
        toastTitle.textContent = 'Notice / Error';
        toastIcon.className = 'flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-500/20 text-red-400 border border-red-500/30';
        toastIcon.innerHTML = '✕';
        toastContent.className = 'flex items-center gap-3 rounded-2xl border border-red-500/30 bg-slate-900 px-5 py-4 shadow-2xl shadow-black/70';
    }

    // Show toast
    toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
    toast.classList.add('translate-y-0', 'opacity-100', 'pointer-events-auto');

    // Auto-hide after 4 seconds
    clearTimeout(window.toastTimer);
    window.toastTimer = setTimeout(() => {
        hideNotification();
    }, 4000);
}

function hideNotification() {
    const toast = document.getElementById('nooma-toast');
    if (!toast) return;
    toast.classList.remove('translate-y-0', 'opacity-100', 'pointer-events-auto');
    toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
}
</script>

<?php if (!empty($notificationMessage)): ?>
<script>
showNotification(
    <?= json_encode((string) $notificationMessage, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
    <?= json_encode(($notificationType ?? 'success') === 'error' ? 'error' : 'success') ?>
);
</script>
<?php endif; ?>