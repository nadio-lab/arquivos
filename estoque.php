<?php
$page_title = 'Gestão de Estoque';
require_once __DIR__ . '/header.php';
requireRole(['admin','farmaceutico']);

$msg = ''; $msg_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'ajuste') {
        $prod_id = (int)$_POST['produto_id'];
        $tipo    = $_POST['tipo']; // entrada/saida
        $qtd     = (int)$_POST['quantidade'];
        $obs     = sanitize($_POST['observacoes'] ?? '');
        if ($tipo === 'entrada') {
            db()->execute("UPDATE produtos SET estoque_atual = estoque_atual + ? WHERE id=?", [$qtd, $prod_id]);
            $msg = "Entrada de $qtd unidade(s) registada!";
        } else {
            $prod = db()->fetch("SELECT estoque_atual FROM produtos WHERE id=?", [$prod_id]);
            if ($prod['estoque_atual'] >= $qtd) {
                db()->execute("UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id=?", [$qtd, $prod_id]);
                $msg = "Saída de $qtd unidade(s) registada!";
            } else {
                $msg = "Estoque insuficiente!"; $msg_type = 'danger';
            }
        }
    }
}

$filter   = $_GET['filter'] ?? 'all';
$search   = sanitize($_GET['q'] ?? '');
$where_parts = ["p.ativo=1"];
$params = [];
if ($filter === 'baixo')  { $where_parts[] = "p.estoque_atual <= p.estoque_minimo AND p.estoque_atual > 0"; }
if ($filter === 'zerado') { $where_parts[] = "p.estoque_atual = 0"; }
if ($filter === 'ok')     { $where_parts[] = "p.estoque_atual > p.estoque_minimo"; }
if ($search)              { $where_parts[] = "p.nome LIKE ?"; $params[] = "%$search%"; }
$where = "WHERE " . implode(" AND ", $where_parts);

$produtos  = db()->fetchAll("SELECT p.*, c.nome cat_nome FROM produtos p LEFT JOIN categorias c ON c.id=p.categoria_id $where ORDER BY p.nome", $params);

$stats = db()->fetch("SELECT
    COUNT(*) total,
    SUM(CASE WHEN estoque_atual=0 THEN 1 ELSE 0 END) zerado,
    SUM(CASE WHEN estoque_atual>0 AND estoque_atual<=estoque_minimo THEN 1 ELSE 0 END) baixo,
    SUM(CASE WHEN estoque_atual>estoque_minimo THEN 1 ELSE 0 END) ok
    FROM produtos WHERE ativo=1");

$produtos_list = db()->fetchAll("SELECT id, nome FROM produtos WHERE ativo=1 ORDER BY nome");
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-3">
        <a href="?filter=all" class="stat-card text-decoration-none">
            <div class="stat-icon blue"><i class="bi bi-boxes"></i></div>
            <div class="stat-info"><span class="stat-value"><?= $stats['total'] ?></span><div class="stat-label">Total Produtos</div></div>
        </a>
    </div>
    <div class="col-sm-3">
        <a href="?filter=ok" class="stat-card text-decoration-none">
            <div class="stat-icon green"><i class="bi bi-check2-circle"></i></div>
            <div class="stat-info"><span class="stat-value"><?= $stats['ok'] ?></span><div class="stat-label">Estoque OK</div></div>
        </a>
    </div>
    <div class="col-sm-3">
        <a href="?filter=baixo" class="stat-card text-decoration-none">
            <div class="stat-icon orange"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-info"><span class="stat-value"><?= $stats['baixo'] ?></span><div class="stat-label">Estoque Baixo</div></div>
        </a>
    </div>
    <div class="col-sm-3">
        <a href="?filter=zerado" class="stat-card text-decoration-none">
            <div class="stat-icon red"><i class="bi bi-x-circle"></i></div>
            <div class="stat-info"><span class="stat-value"><?= $stats['zerado'] ?></span><div class="stat-label">Sem Estoque</div></div>
        </a>
    </div>
</div>

<div class="row g-3">
    <!-- Adjustment Form -->
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="bi bi-sliders me-2"></i>Ajuste de Estoque</h5></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="ajuste">
                    <div class="mb-3">
                        <label class="form-label">Produto *</label>
                        <select name="produto_id" class="form-select" required>
                            <option value="">-- Selecionar --</option>
                            <?php foreach ($produtos_list as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo" value="entrada" id="t-entrada" checked>
                                <label class="form-check-label text-success fw-semibold" for="t-entrada">▲ Entrada</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo" value="saida" id="t-saida">
                                <label class="form-check-label text-danger fw-semibold" for="t-saida">▼ Saída</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantidade *</label>
                        <input type="number" name="quantidade" min="1" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo</label>
                        <textarea name="observacoes" class="form-control" rows="2" placeholder="Ex: Compra, devolução, avaria..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-circle me-2"></i>Confirmar</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Stock Table -->
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">
                    <i class="bi bi-archive me-2"></i>Inventário
                    <span class="badge bg-secondary ms-1"><?= count($produtos) ?></span>
                    <?php if ($filter !== 'all'): ?>
                    <span class="badge bg-primary ms-1"><?= ucfirst($filter) ?></span>
                    <?php endif; ?>
                </h5>
                <form class="d-flex gap-2">
                    <input type="hidden" name="filter" value="<?= $filter ?>">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Pesquisar..." value="<?= htmlspecialchars($search) ?>" style="width:200px">
                    <button class="btn btn-sm btn-outline-primary">Buscar</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table datatable mb-0">
                        <thead><tr>
                            <th>Código</th><th>Nome</th><th>Categoria</th><th>Stock Actual</th><th>Stock Mínimo</th><th>Situação</th><th>Validade</th><th>P. Custo</th><th>P. Venda</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($produtos as $p):
                            if ($p['estoque_atual'] == 0) { $sit = ['Esgotado','danger']; }
                            elseif ($p['estoque_atual'] <= $p['estoque_minimo']) { $sit = ['Baixo','warning']; }
                            else { $sit = ['Normal','success']; }
                            $exp = $p['validade'] && $p['validade'] < date('Y-m-d');
                        ?>
                        <tr>
                            <td><code><?= htmlspecialchars($p['codigo']) ?></code></td>
                            <td class="fw-semibold"><?= htmlspecialchars($p['nome']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($p['cat_nome'] ?? '-') ?></td>
                            <td>
                                <span class="fw-bold fs-6 <?= $sit[1] === 'danger' ? 'text-danger' : ($sit[1]==='warning' ? 'text-warning' : 'text-success') ?>">
                                    <?= $p['estoque_atual'] ?>
                                </span>
                            </td>
                            <td class="text-muted"><?= $p['estoque_minimo'] ?></td>
                            <td><span class="badge bg-<?= $sit[1] ?>"><?= $sit[0] ?></span></td>
                            <td class="<?= $exp ? 'text-danger fw-semibold' : '' ?>">
                                <?= $p['validade'] ? date('d/m/Y', strtotime($p['validade'])) . ($exp ? ' ⚠️' : '') : '-' ?>
                            </td>
                            <td class="text-muted">Kz <?= number_format($p['preco_custo'], 2, ',', '.') ?></td>
                            <td class="fw-semibold text-primary">Kz <?= number_format($p['preco_venda'], 2, ',', '.') ?></td>
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
