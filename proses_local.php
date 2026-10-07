<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$uploadDir = __DIR__ . '/uploads/';
$status = 'error';
$message = '';
$uploadedFilePath = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $message = 'Username dan password wajib diisi.';
    } elseif (!isset($_FILES['foto_profil']) || $_FILES['foto_profil']['error'] !== UPLOAD_ERR_OK) {
        $message = 'File foto profil gagal diunggah atau tidak ditemukan.';
    } else {
        $file = $_FILES['foto_profil'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeType = function_exists('mime_content_type')
            ? mime_content_type($file['tmp_name'])
            : 'image/jpeg';

        if ($file['size'] > $maxSize) {
            $message = 'Ukuran file melebihi batas maksimal 2MB.';
        } elseif (!in_array($mimeType, $allowedTypes, true) || !in_array($fileExt, $allowedExtensions, true)) {
            $message = 'Format file tidak didukung. Harap unggah format JPG, PNG, atau WEBP.';
        } else {
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }

            $newFileName = 'avatar_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $conn = @new mysqli('localhost', 'root', '', 'db_topup_game');

                if ($conn->connect_error) {
                    @unlink($destination);
                    $message = 'Gagal terhubung ke MySQL lokal. Pastikan modul MySQL di XAMPP sudah di-START. Error: ' . $conn->connect_error;
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (username, password, foto_profil) VALUES (?, ?, ?)");

                    if ($stmt) {
                        $stmt->bind_param("sss", $username, $hashedPassword, $newFileName);
                        if ($stmt->execute()) {
                            $status = 'success';
                            $message = 'Registrasi berhasil dan data tersimpan ke MySQL Lokal (phpMyAdmin)!';
                            $uploadedFilePath = 'uploads/' . $newFileName;
                        } else {
                            @unlink($destination);
                            if ($conn->errno === 1062) {
                                $message = 'Username sudah digunakan, silakan pilih username lain.';
                            } else {
                                $message = 'Gagal menyimpan data ke database: ' . $stmt->error;
                            }
                        }
                        $stmt->close();
                    } else {
                        @unlink($destination);
                        $message = 'Gagal menyiapkan query database: ' . $conn->error;
                    }
                    $conn->close();
                }
            } else {
                $message = 'Gagal menyimpan file ke folder uploads.';
            }
        }
    }
} else {
    header('Location: registrasi.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Status Registrasi (Lokal)</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
          },
          colors: {
            surface: '#0f1420',
            stroke: 'rgba(255, 255, 255, 0.08)',
          }
        }
      }
    }
  </script>
</head>
<body class="bg-[#07090e] text-slate-100 font-sans min-h-screen flex items-center justify-center p-4 selection:bg-cyan-500 selection:text-black">
  <main class="w-full max-w-md bg-surface/90 border border-stroke rounded-3xl p-7 sm:p-8 shadow-2xl backdrop-blur-xl text-center">
    <?php if ($status === 'success'): ?>
      <div class="w-14 h-14 mx-auto rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mb-4 text-2xl">
        ✓
      </div>
      <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[11px] font-semibold mb-2">
        Database: Local MySQL (phpMyAdmin)
      </div>
      <h1 class="text-2xl font-extrabold text-white">Registrasi Berhasil</h1>
      <p class="text-slate-400 text-sm mt-1.5"><?= htmlspecialchars($message) ?></p>

      <div class="mt-6 p-4 rounded-2xl bg-[#090d16] border border-stroke text-left space-y-3">
        <div>
          <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Username</span>
          <p class="text-white font-medium text-sm"><?= htmlspecialchars($username) ?></p>
        </div>
        <div>
          <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Foto Profil</span>
          <div class="mt-2 flex items-center gap-3">
            <img src="<?= htmlspecialchars($uploadedFilePath) ?>" alt="Avatar" class="w-14 h-14 rounded-xl object-cover border border-stroke">
            <span class="text-xs text-slate-400 break-all"><?= htmlspecialchars(basename($uploadedFilePath)) ?></span>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="w-14 h-14 mx-auto rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center mb-4 text-2xl">
        ✕
      </div>
      <h1 class="text-2xl font-extrabold text-white">Gagal Memproses</h1>
      <p class="text-slate-400 text-sm mt-1.5"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <div class="mt-7">
      <a href="registrasi.html" class="inline-flex items-center justify-center w-full py-3 px-5 rounded-xl bg-white/5 hover:bg-white/10 border border-stroke text-sm font-semibold text-white transition">
        Kembali ke Form
      </a>
    </div>
  </main>
</body>
</html>
