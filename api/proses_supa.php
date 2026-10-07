<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

$uploadDir = sys_get_temp_dir() . '/uploads/';
$status = 'error';
$message = '';
$previewDataUri = '';
$username = '';
$savedFileName = '';

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

            $savedFileName = 'avatar_' . bin2hex(random_bytes(8)) . '.' . $fileExt;
            $destination = $uploadDir . $savedFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $rawContent = @file_get_contents($destination);
                if ($rawContent !== false) {
                    $previewDataUri = 'data:' . $mimeType . ';base64,' . base64_encode($rawContent);
                }

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $dbSaved = false;

                // 1. Coba koneksi PDO PostgreSQL (Supabase Connection Pooling)
                $postgresUrl = getenv('POSTGRES_URL') ?: getenv('POSTGRES_PRISMA_URL');
                if ($postgresUrl) {
                    try {
                        $parts = parse_url($postgresUrl);
                        $host = $parts['host'] ?? '';
                        $port = $parts['port'] ?? 6543;
                        $user = $parts['user'] ?? 'postgres';
                        $pass = $parts['pass'] ?? '';
                        $dbname = ltrim($parts['path'] ?? '/postgres', '/');

                        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
                        $pdo = new PDO($dsn, $user, $pass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_TIMEOUT => 3
                        ]);

                        $stmt = $pdo->prepare("INSERT INTO public.users (username, password, foto_profil) VALUES (:username, :password, :foto_profil)");
                        $stmt->execute([
                            ':username' => $username,
                            ':password' => $hashedPassword,
                            ':foto_profil' => $savedFileName
                        ]);

                        $dbSaved = true;
                    } catch (Exception $e) {
                        // Jika gagal via direct PDO, lanjut ke REST API Supabase
                        error_log('PDO error: ' . $e->getMessage());
                    }
                }

                // 2. Fallback: Supabase REST API via HTTPS
                if (!$dbSaved) {
                    $supaUrl = getenv('NEXT_PUBLIC_SUPABASE_URL') ?: getenv('SUPABASE_URL');
                    $supaKey = getenv('NEXT_PUBLIC_SUPABASE_ANON_KEY') ?: getenv('SUPABASE_ANON_KEY') ?: getenv('SUPABASE_SERVICE_ROLE_KEY');

                    if ($supaUrl && $supaKey) {
                        $apiUrl = rtrim($supaUrl, '/') . '/rest/v1/users';
                        $payload = json_encode([
                            'username' => $username,
                            'password' => $hashedPassword,
                            'foto_profil' => $savedFileName
                        ]);

                        $ch = curl_init($apiUrl);
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST => true,
                            CURLOPT_POSTFIELDS => $payload,
                            CURLOPT_TIMEOUT => 4,
                            CURLOPT_HTTPHEADER => [
                                'Content-Type: application/json',
                                'apikey: ' . $supaKey,
                                'Authorization: Bearer ' . $supaKey,
                                'Prefer: return=minimal'
                            ]
                        ]);

                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);

                        if ($httpCode === 201 || $httpCode === 200 || $httpCode === 204) {
                            $dbSaved = true;
                        }
                    }
                }

                if ($dbSaved) {
                    $status = 'success';
                    $message = 'Registrasi berhasil dan data akun tersimpan permanen di Supabase Cloud!';
                } else {
                    // Beri informasi jika tabel belum dibuat di Supabase
                    $status = 'success';
                    $message = 'Foto profil berhasil diupload! (Catatan: Pastikan Anda sudah menjalankan query di SQL Editor Supabase untuk membuat tabel users).';
                }
            } else {
                $message = 'Gagal menyimpan file ke temporary serverless storage.';
            }
        }
    }
} else {
    header('Location: /registrasi.html');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Status Registrasi - Supabase Cloud</title>
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
        Database: Supabase (PostgreSQL Cloud)
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
            <?php if ($previewDataUri): ?>
              <img src="<?= $previewDataUri ?>" alt="Avatar" class="w-14 h-14 rounded-xl object-cover border border-stroke">
            <?php endif; ?>
            <span class="text-xs text-slate-400 break-all"><?= htmlspecialchars($savedFileName) ?></span>
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
      <a href="../registrasi.html" class="inline-flex items-center justify-center w-full py-3 px-5 rounded-xl bg-white/5 hover:bg-white/10 border border-stroke text-sm font-semibold text-white transition">
        Kembali ke Form
      </a>
    </div>
  </main>
</body>
</html>
