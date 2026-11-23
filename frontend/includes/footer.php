
<footer class="border-t border-slate-200 bg-white px-6 py-4">
    <div class="flex flex-col justify-between gap-2 text-xs text-slate-500 sm:flex-row">
        <p>© <?php echo date('Y'); ?> NOOMA Management System. All rights reserved.</p>
        <p>Developed with Tailwind CSS</p>
    </div>
</footer>

<?php
$notificationMessage = '';
$notificationType = 'success';

if (isset($_SESSION['flash']) && is_array($_SESSION['flash'])) {
    $notificationMessage = (string) ($_SESSION['flash']['message'] ?? '');
    $notificationType = (string) ($_SESSION['flash']['type'] ?? 'success');
    unset($_SESSION['flash']);
} elseif (!empty($_SESSION['flash_error'])) {
    $notificationMessage = (string) $_SESSION['flash_error'];
    $notificationType = 'error';
    unset($_SESSION['flash_error'], $_SESSION['flash_success']);
} elseif (!empty($_SESSION['flash_success'])) {
    $notificationMessage = (string) $_SESSION['flash_success'];
    unset($_SESSION['flash_success'], $_SESSION['flash_error']);
}

require __DIR__ . '/../components/notification.php';
unset($notificationMessage, $notificationType);
?>
