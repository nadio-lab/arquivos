<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) redirect(APP_URL . '/admin/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    if (login($email, $senha)) {
        redirect(APP_URL . '/admin/dashboard.php');
    } else {
        $error = 'Email ou senha incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmaciaPro | Entrar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/main.css" rel="stylesheet">
</head>
<body>
<div class="login-page">
    <div class="login-box fade-in">
        <!-- Logo -->
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center" style="width:60px;height:60px;background:linear-gradient(135deg,#00B09B,#00CFB4);border-radius:16px;font-size:28px;color:#fff;margin-bottom:16px">
                <i class="bi bi-capsule-pill"></i>
            </div>
            <h1 style="font-size:22px;font-weight:800;margin:0">FarmaciaPro</h1>
            <p style="color:var(--text-muted);font-size:13px;margin:4px 0 0">Sistema de Gestão Farmacêutica</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= $error ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="admin@farmacia.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Senha</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="senha" id="senha" class="form-control" placeholder="••••••••" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePass()">
                        <i class="bi bi-eye" id="eye-icon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                <i class="bi bi-box-arrow-in-right me-2"></i>Entrar no Sistema
            </button>
        </form>

        <div class="text-center mt-4" style="padding:12px;background:var(--surface-2);border-radius:var(--radius-sm);border:1px solid var(--border)">
            <p class="mb-1" style="font-size:12px;color:var(--text-muted);font-weight:600">CREDENCIAIS DE DEMONSTRAÇÃO</p>
            <code style="font-size:12px">admin@farmacia.com</code>
            <span style="color:var(--text-muted);margin:0 6px">|</span>
            <code style="font-size:12px">password</code>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass() {
    const el = document.getElementById('senha');
    const icon = document.getElementById('eye-icon');
    if (el.type === 'password') { el.type = 'text'; icon.className = 'bi bi-eye-slash'; }
    else { el.type = 'password'; icon.className = 'bi bi-eye'; }
}
</script>
</body>
</html>
