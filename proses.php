<?php
if (in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'])) {
    require __DIR__ . '/proses_local.php';
} else {
    require __DIR__ . '/api/proses_supa.php';
}
