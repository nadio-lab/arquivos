<?php
$page_title = 'Funcionários';
require_once __DIR__ . '/header.php';
requireAdmin();

$msg = ''; $msg_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id   = (int)($_POST['id'] ?? 0);
        $nome = sanitize($_POST['nome']);
        $email = sanitize($_POST['email']);
        $perfil = sanitize($_POST['perfil']);
        if ($id) {
            db()->execute("UPDATE usuarios SET nome=?,email=?,perfil=? WHERE id=?", [$nome, $email, $perfil, $id]);
            if (!empty($_POST['nova_senha'])) {
                db()->execute("UPDATE usuarios SET senha=? WHERE id=?", [password_hash($_POST['nova_senha'], PASSWORD_DEFAULT), $id]);
            }
            $msg = 'Funcionário actualizado!';
        } else {
            $senha = password_hash($_POST['senha'] ?? 'password', PASSWORD_DEFAULT);
            db()->insert("INSERT INTO usuarios (nome,email,senha,perfil) VALUES (?,?,?,?)", [$nome, $email, $senha, $perfil]);
            $msg = 'Funcionário criado com sucesso!';
        }
    } elseif ($action === 'toggle') {
        db()->execute("UPDATE usuarios SET ativo = !ativo WHERE id=?", [(int)$_POST['id']]);
        $msg = 'Estado alterado!';
    }
}

$funcionarios = db()->fetchAll("SELECT * FROM usuarios ORDER BY nome");
$edit = isset($_GET['edit']) ? db()->fetch("SELECT * FROM usuarios WHERE id=?", [(int)$_GET['edit']]) : null;
$perfis = ['admin'=>'Administrador','farmaceutico'=>'Farmacêutico','atendente'=>'Atendente','financeiro'=>'Financeiro'];
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-person-plus me-2"></i><?= $edit ? 'Editar' : 'Novo' ?> Funcionário</h5>
                <?php if ($edit): ?><a href="funcionarios.php" class="btn btn-sm btn-outline-secondary">Cancelar</a><?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($edit['nome'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($edit['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Perfil / Função *</label>
                        <select name="perfil" class="form-select" required>
                            <?php foreach ($perfis as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($edit['perfil'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($edit): ?>
                    <div class="mb-3">
                        <label class="form-label">Nova Senha <small class="text-muted">(deixe vazio para não alterar)</small></label>
                        <input type="password" name="nova_senha" class="form-control" minlength="6">
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label">Senha *</label>
                        <input type="password" name="senha" class="form-control" required minlength="6" value="password">
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-circle me-2"></i>Guardar</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="bi bi-people me-2"></i>Equipa (<?= count($funcionarios) ?>)</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Funcionário</th><th>Email</th><th>Perfil</th><th>Estado</th><th>Criado em</th><th>Acções</th></tr></thead>
                    <tbody>
                    <?php foreach ($funcionarios as $f): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="user-avatar"><?= strtoupper(substr($f['nome'],0,2)) ?></div>
                                <span class="fw-semibold"><?= htmlspecialchars($f['nome']) ?></span>
                            </div>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($f['email']) ?></td>
                        <td>
                            <?php $perfil_colors = ['admin'=>'danger','farmaceutico'=>'primary','atendente'=>'info','financeiro'=>'success']; ?>
                            <span class="badge bg-<?= $perfil_colors[$f['perfil']] ?? 'secondary' ?>"><?= $perfis[$f['perfil']] ?? $f['perfil'] ?></span>
                        </td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $f['id'] ?>">
                                <button type="submit" class="badge border-0 badge-status-<?= $f['ativo'] ? 'ativo' : 'inativo' ?>" style="cursor:pointer"><?= $f['ativo'] ? 'Activo' : 'Inactivo' ?></button>
                            </form>
                        </td>
                        <td class="text-muted"><?= date('d/m/Y', strtotime($f['criado_em'])) ?></td>
                        <td>
                            <a href="?edit=<?= $f['id'] ?>" class="btn btn-icon btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
