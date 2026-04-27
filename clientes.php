<?php
$page_title = 'Clientes';
require_once __DIR__ . '/header.php';
requireRole(['admin','farmaceutico','atendente']);

$msg = ''; $msg_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            sanitize($_POST['nome']),
            sanitize($_POST['cpf'] ?? ''),
            sanitize($_POST['telefone'] ?? ''),
            sanitize($_POST['email'] ?? ''),
            sanitize($_POST['endereco'] ?? ''),
            $_POST['data_nascimento'] ?: null,
            sanitize($_POST['observacoes'] ?? ''),
        ];
        if ($id) {
            db()->execute("UPDATE clientes SET nome=?,cpf=?,telefone=?,email=?,endereco=?,data_nascimento=?,observacoes=? WHERE id=?", [...$data, $id]);
            $msg = 'Cliente actualizado!';
        } else {
            db()->insert("INSERT INTO clientes (nome,cpf,telefone,email,endereco,data_nascimento,observacoes) VALUES (?,?,?,?,?,?,?)", $data);
            $msg = 'Cliente cadastrado com sucesso!';
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        db()->execute("DELETE FROM clientes WHERE id=?", [$id]);
        $msg = 'Cliente eliminado.'; $msg_type = 'warning';
    }
}

$search   = sanitize($_GET['q'] ?? '');
$where    = $search ? "WHERE nome LIKE ? OR cpf LIKE ? OR telefone LIKE ?" : '';
$params   = $search ? ["%$search%", "%$search%", "%$search%"] : [];
$clientes = db()->fetchAll("SELECT * FROM clientes $where ORDER BY nome", $params);
$edit     = null;
if (isset($_GET['edit'])) $edit = db()->fetch("SELECT * FROM clientes WHERE id=?", [(int)$_GET['edit']]);
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-person-plus-fill me-2"></i><?= $edit ? 'Editar' : 'Novo' ?> Cliente</h5>
                <?php if ($edit): ?><a href="clientes.php" class="btn btn-sm btn-outline-secondary">Cancelar</a><?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($edit['nome'] ?? '') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">BI / NIF</label>
                            <input type="text" name="cpf" class="form-control" value="<?= htmlspecialchars($edit['cpf'] ?? '') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Telefone</label>
                            <input type="text" name="telefone" class="form-control" value="<?= htmlspecialchars($edit['telefone'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($edit['email'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Endereço</label>
                            <textarea name="endereco" class="form-control" rows="2"><?= htmlspecialchars($edit['endereco'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Data de Nascimento</label>
                            <input type="date" name="data_nascimento" class="form-control" value="<?= $edit['data_nascimento'] ?? '' ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea name="observacoes" class="form-control" rows="2"><?= htmlspecialchars($edit['observacoes'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-circle me-2"></i>Guardar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-people-fill me-2"></i>Clientes (<?= count($clientes) ?>)</h5>
                <form class="d-flex gap-2">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Pesquisar..." value="<?= htmlspecialchars($search) ?>" style="width:200px">
                    <button class="btn btn-sm btn-outline-primary">Buscar</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable mb-0">
                        <thead><tr><th>Nome</th><th>BI/NIF</th><th>Telefone</th><th>Email</th><th>Cadastro</th><th>Acções</th></tr></thead>
                        <tbody>
                        <?php foreach ($clientes as $c): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="user-avatar-sm"><?= strtoupper(substr($c['nome'],0,1)) ?></div>
                                    <span class="fw-semibold"><?= htmlspecialchars($c['nome']) ?></span>
                                </div>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($c['cpf'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($c['telefone'] ?: '-') ?></td>
                            <td class="text-muted"><?= htmlspecialchars($c['email'] ?: '-') ?></td>
                            <td class="text-muted"><?= date('d/m/Y', strtotime($c['criado_em'])) ?></td>
                            <td>
                                <a href="?edit=<?= $c['id'] ?>" class="btn btn-icon btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn btn-icon btn-sm btn-outline-danger btn-delete" title="Eliminar"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($clientes)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum cliente encontrado</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
