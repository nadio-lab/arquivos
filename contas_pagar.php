<?php
$page_title = 'Contas a Pagar';
require_once __DIR__ . '/header.php';
requireRole(['admin','financeiro']);

$msg = ''; $msg_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $data = [sanitize($_POST['descricao']), $_POST['fornecedor_id'] ?: null, (float)$_POST['valor'], $_POST['vencimento'], sanitize($_POST['categoria'] ?? ''), sanitize($_POST['observacoes'] ?? '')];
        if ($id) {
            db()->execute("UPDATE contas_pagar SET descricao=?,fornecedor_id=?,valor=?,vencimento=?,categoria=?,observacoes=? WHERE id=?", [...$data, $id]);
            $msg = 'Conta actualizada!';
        } else {
            db()->insert("INSERT INTO contas_pagar (descricao,fornecedor_id,valor,vencimento,categoria,observacoes) VALUES (?,?,?,?,?,?)", $data);
            $msg = 'Conta registada com sucesso!';
        }
    } elseif ($action === 'pagar') {
        $id = (int)$_POST['id'];
        db()->execute("UPDATE contas_pagar SET status='pago', pago_em=CURDATE() WHERE id=?", [$id]);
        // Register in caixa
        $conta = db()->fetch("SELECT * FROM contas_pagar WHERE id=?", [$id]);
        db()->insert("INSERT INTO caixa (tipo,descricao,valor,usuario_id) VALUES ('saida',?,?,?)", [$conta['descricao'], $conta['valor'], currentUser()['id']]);
        $msg = 'Conta marcada como paga!';
    } elseif ($action === 'delete') {
        db()->execute("DELETE FROM contas_pagar WHERE id=?", [(int)$_POST['id']]);
        $msg = 'Conta eliminada.'; $msg_type = 'warning';
    }
}

$status_f  = $_GET['status'] ?? '';
$where     = $status_f ? "WHERE status=?" : '';
$params    = $status_f ? [$status_f] : [];
$contas    = db()->fetchAll("SELECT cp.*, f.nome forn_nome FROM contas_pagar cp LEFT JOIN fornecedores f ON f.id=cp.fornecedor_id $where ORDER BY vencimento", $params);
$forns     = db()->fetchAll("SELECT id,nome FROM fornecedores WHERE ativo=1 ORDER BY nome");
$edit      = isset($_GET['edit']) ? db()->fetch("SELECT * FROM contas_pagar WHERE id=?", [(int)$_GET['edit']]) : null;

$totais = db()->fetch("SELECT
    COALESCE(SUM(CASE WHEN status='pendente' THEN valor END),0) pendente,
    COALESCE(SUM(CASE WHEN status='pago' THEN valor END),0) pago,
    COALESCE(SUM(CASE WHEN status='vencido' OR (status='pendente' AND vencimento < CURDATE()) THEN valor END),0) vencido
    FROM contas_pagar");
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card"><div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
        <div class="stat-info"><span class="stat-value">Kz <?= number_format($totais['pendente'],0,',','.') ?></span><div class="stat-label">Pendentes</div></div></div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card"><div class="stat-icon red"><i class="bi bi-exclamation-octagon"></i></div>
        <div class="stat-info"><span class="stat-value">Kz <?= number_format($totais['vencido'],0,',','.') ?></span><div class="stat-label">Vencidos</div></div></div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card"><div class="stat-icon green"><i class="bi bi-check-all"></i></div>
        <div class="stat-info"><span class="stat-value">Kz <?= number_format($totais['pago'],0,',','.') ?></span><div class="stat-label">Pagos</div></div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-plus-circle me-2"></i><?= $edit ? 'Editar' : 'Nova' ?> Conta</h5>
                <?php if ($edit): ?><a href="contas_pagar.php" class="btn btn-sm btn-outline-secondary">Cancelar</a><?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
                    <div class="mb-3">
                        <label class="form-label">Descrição *</label>
                        <input type="text" name="descricao" class="form-control" required value="<?= htmlspecialchars($edit['descricao'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fornecedor</label>
                        <select name="fornecedor_id" class="form-select">
                            <option value="">-- Selecionar --</option>
                            <?php foreach ($forns as $f): ?>
                            <option value="<?= $f['id'] ?>" <?= ($edit['fornecedor_id'] ?? '') == $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Valor (Kz) *</label>
                        <input type="number" name="valor" step="0.01" class="form-control" required value="<?= $edit['valor'] ?? '' ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vencimento *</label>
                        <input type="date" name="vencimento" class="form-control" required value="<?= $edit['vencimento'] ?? '' ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <input type="text" name="categoria" class="form-control" placeholder="Ex: Fornecedores, Serviços..." value="<?= htmlspecialchars($edit['categoria'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="2"><?= htmlspecialchars($edit['observacoes'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-circle me-2"></i>Guardar</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-list-check me-2"></i>Lista de Contas (<?= count($contas) ?>)</h5>
                <div class="d-flex gap-2">
                    <?php foreach ([''=>'Todas','pendente'=>'Pendente','pago'=>'Pago','vencido'=>'Vencido'] as $k=>$v): ?>
                    <a href="?status=<?= $k ?>" class="btn btn-sm <?= $status_f === $k ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $v ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable mb-0">
                        <thead><tr><th>Descrição</th><th>Fornecedor</th><th>Valor</th><th>Vencimento</th><th>Estado</th><th>Acções</th></tr></thead>
                        <tbody>
                        <?php foreach ($contas as $c):
                            $vencido = $c['status'] === 'pendente' && $c['vencimento'] < date('Y-m-d');
                        ?>
                        <tr>
                            <td><div class="fw-semibold"><?= htmlspecialchars($c['descricao']) ?></div><?php if ($c['categoria']): ?><small class="text-muted"><?= htmlspecialchars($c['categoria']) ?></small><?php endif; ?></td>
                            <td class="text-muted"><?= htmlspecialchars($c['forn_nome'] ?? '-') ?></td>
                            <td class="fw-semibold">Kz <?= number_format($c['valor'], 2, ',', '.') ?></td>
                            <td class="<?= $vencido ? 'text-danger fw-semibold' : '' ?>"><?= date('d/m/Y', strtotime($c['vencimento'])) ?></td>
                            <td><span class="badge badge-status-<?= $vencido ? 'vencido' : $c['status'] ?>"><?= $vencido ? 'Vencido' : ucfirst($c['status']) ?></span></td>
                            <td>
                                <?php if ($c['status'] === 'pendente'): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="pagar"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn btn-icon btn-sm btn-success" title="Marcar como pago"><i class="bi bi-check-lg"></i></button>
                                </form>
                                <?php endif; ?>
                                <a href="?edit=<?= $c['id'] ?>" class="btn btn-icon btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="btn btn-icon btn-sm btn-outline-danger btn-delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
