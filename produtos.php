<?php
$page_title = 'Produtos';
require_once __DIR__ . '/header.php';
requireRole(['admin','farmaceutico']);

// Handle actions
$msg = ''; $msg_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id   = (int)($_POST['id'] ?? 0);
        $data = [
            sanitize($_POST['codigo']),
            sanitize($_POST['nome']),
            sanitize($_POST['descricao'] ?? ''),
            (int)$_POST['categoria_id'],
            $_POST['fornecedor_id'] ? (int)$_POST['fornecedor_id'] : null,
            (float)$_POST['preco_custo'],
            (float)$_POST['preco_venda'],
            (int)$_POST['estoque_atual'],
            (int)$_POST['estoque_minimo'],
            $_POST['validade'] ?: null,
            sanitize($_POST['lote'] ?? ''),
            (int)($_POST['requer_receita'] ?? 0),
        ];
        if ($id) {
            db()->execute("UPDATE produtos SET codigo=?,nome=?,descricao=?,categoria_id=?,fornecedor_id=?,preco_custo=?,preco_venda=?,estoque_atual=?,estoque_minimo=?,validade=?,lote=?,requer_receita=? WHERE id=?",
                [...$data, $id]);
            $msg = 'Produto actualizado com sucesso!';
        } else {
            db()->insert("INSERT INTO produtos (codigo,nome,descricao,categoria_id,fornecedor_id,preco_custo,preco_venda,estoque_atual,estoque_minimo,validade,lote,requer_receita) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)", $data);
            $msg = 'Produto adicionado com sucesso!';
        }
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        db()->execute("UPDATE produtos SET ativo = !ativo WHERE id=?", [$id]);
        $msg = 'Estado do produto alterado.';
    }
}

$produtos    = db()->fetchAll("SELECT p.*, c.nome cat_nome, f.nome forn_nome FROM produtos p LEFT JOIN categorias c ON c.id=p.categoria_id LEFT JOIN fornecedores f ON f.id=p.fornecedor_id ORDER BY p.nome");
$categorias  = db()->fetchAll("SELECT * FROM categorias ORDER BY nome");
$fornecedores = db()->fetchAll("SELECT * FROM fornecedores WHERE ativo=1 ORDER BY nome");
$edit = null;
if (isset($_GET['edit'])) $edit = db()->fetch("SELECT * FROM produtos WHERE id=?", [(int)$_GET['edit']]);
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-3">
    <!-- Form -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-<?= $edit ? 'pencil' : 'plus-circle' ?>-fill me-2"></i><?= $edit ? 'Editar' : 'Novo' ?> Produto</h5>
                <?php if ($edit): ?><a href="produtos.php" class="btn btn-sm btn-outline-secondary">Cancelar</a><?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label">Código *</label>
                            <input type="text" name="codigo" class="form-control" required value="<?= htmlspecialchars($edit['codigo'] ?? '') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Lote</label>
                            <input type="text" name="lote" class="form-control" value="<?= htmlspecialchars($edit['lote'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nome *</label>
                            <input type="text" name="nome" class="form-control" required value="<?= htmlspecialchars($edit['nome'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <textarea name="descricao" class="form-control" rows="2"><?= htmlspecialchars($edit['descricao'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Categoria</label>
                            <select name="categoria_id" class="form-select">
                                <option value="">-- Selecionar --</option>
                                <?php foreach ($categorias as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($edit['categoria_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Fornecedor</label>
                            <select name="fornecedor_id" class="form-select">
                                <option value="">-- Selecionar --</option>
                                <?php foreach ($fornecedores as $f): ?>
                                <option value="<?= $f['id'] ?>" <?= ($edit['fornecedor_id'] ?? '') == $f['id'] ? 'selected' : '' ?>><?= htmlspecialchars($f['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">P. Custo (Kz)</label>
                            <input type="number" name="preco_custo" step="0.01" class="form-control" value="<?= $edit['preco_custo'] ?? '0' ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">P. Venda (Kz) *</label>
                            <input type="number" name="preco_venda" step="0.01" class="form-control" required value="<?= $edit['preco_venda'] ?? '' ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Estoque Actual</label>
                            <input type="number" name="estoque_atual" class="form-control" value="<?= $edit['estoque_atual'] ?? '0' ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Estoque Mínimo</label>
                            <input type="number" name="estoque_minimo" class="form-control" value="<?= $edit['estoque_minimo'] ?? '10' ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Data Validade</label>
                            <input type="date" name="validade" class="form-control" value="<?= $edit['validade'] ?? '' ?>">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="requer_receita" value="1" id="rec" <?= ($edit['requer_receita'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="rec">Requer Receita Médica</label>
                            </div>
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle me-2"></i>Guardar Produto
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-box-seam-fill me-2"></i>Lista de Produtos (<?= count($produtos) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable mb-0">
                        <thead><tr>
                            <th>Código</th><th>Nome</th><th>Categoria</th><th>P. Venda</th><th>Estoque</th><th>Validade</th><th>Estado</th><th>Acções</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($produtos as $p): 
                            $stock_class = $p['estoque_atual'] == 0 ? 'stock-empty' : ($p['estoque_atual'] <= $p['estoque_minimo'] ? 'stock-low' : 'stock-ok');
                        ?>
                        <tr>
                            <td><code><?= htmlspecialchars($p['codigo']) ?></code></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($p['nome']) ?></div>
                                <?php if ($p['requer_receita']): ?><span class="badge bg-warning text-dark" style="font-size:10px">Receita</span><?php endif; ?>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($p['cat_nome'] ?? '-') ?></td>
                            <td class="fw-semibold">Kz <?= number_format($p['preco_venda'], 2, ',', '.') ?></td>
                            <td class="<?= $stock_class ?>">
                                <span class="stock-alert-badge"></span>
                                <?= $p['estoque_atual'] ?>
                                <small class="text-muted">/<?= $p['estoque_minimo'] ?></small>
                            </td>
                            <td class="<?= $p['validade'] && $p['validade'] < date('Y-m-d') ? 'text-danger fw-semibold' : '' ?>">
                                <?= $p['validade'] ? date('d/m/Y', strtotime($p['validade'])) : '-' ?>
                            </td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="badge badge-status-<?= $p['ativo'] ? 'ativo' : 'inativo' ?> border-0" style="cursor:pointer">
                                        <?= $p['ativo'] ? 'Activo' : 'Inactivo' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="?edit=<?= $p['id'] ?>" class="btn btn-icon btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
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
