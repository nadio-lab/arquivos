<?php
$page_title = 'Relatórios';
require_once __DIR__ . '/header.php';
requireRole(['admin','financeiro']);

$data_ini = $_GET['data_ini'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$tipo     = $_GET['tipo'] ?? 'vendas';

// Vendas por período
$vendas_periodo = db()->fetch("SELECT COUNT(*) total, COALESCE(SUM(total),0) receita, COALESCE(AVG(total),0) ticket FROM vendas WHERE DATE(criado_em) BETWEEN ? AND ? AND status='concluida'", [$data_ini, $data_fim]);
$vendas_dia     = db()->fetchAll("SELECT DATE(criado_em) d, COUNT(*) c, SUM(total) t FROM vendas WHERE DATE(criado_em) BETWEEN ? AND ? AND status='concluida' GROUP BY DATE(criado_em) ORDER BY d", [$data_ini, $data_fim]);
$por_pagamento  = db()->fetchAll("SELECT forma_pagamento, COUNT(*) c, SUM(total) t FROM vendas WHERE DATE(criado_em) BETWEEN ? AND ? AND status='concluida' GROUP BY forma_pagamento ORDER BY t DESC", [$data_ini, $data_fim]);
$top_produtos   = db()->fetchAll("SELECT p.nome, SUM(vi.quantidade) qtd, SUM(vi.subtotal) total FROM venda_itens vi JOIN produtos p ON p.id=vi.produto_id JOIN vendas v ON v.id=vi.venda_id WHERE DATE(v.criado_em) BETWEEN ? AND ? AND v.status='concluida' GROUP BY vi.produto_id ORDER BY total DESC LIMIT 10", [$data_ini, $data_fim]);
$top_clientes   = db()->fetchAll("SELECT COALESCE(c.nome,'Balcão') nome, COUNT(*) compras, SUM(v.total) total FROM vendas v LEFT JOIN clientes c ON c.id=v.cliente_id WHERE DATE(v.criado_em) BETWEEN ? AND ? AND v.status='concluida' GROUP BY v.cliente_id ORDER BY total DESC LIMIT 10", [$data_ini, $data_fim]);

$labels = array_column($vendas_dia, 'd');
$labels = array_map(fn($d) => date('d/m', strtotime($d)), $labels);
$values = array_column($vendas_dia, 't');
?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-3">
                <label class="form-label">Data Início</label>
                <input type="date" name="data_ini" class="form-control" value="<?= $data_ini ?>">
            </div>
            <div class="col-sm-3">
                <label class="form-label">Data Fim</label>
                <input type="date" name="data_fim" class="form-control" value="<?= $data_fim ?>">
            </div>
            <div class="col-sm-3">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-2"></i>Gerar Relatório</button>
            </div>
            <div class="col-sm-3">
                <button type="button" class="btn btn-outline-secondary w-100" onclick="window.print()"><i class="bi bi-printer me-2"></i>Imprimir</button>
            </div>
        </form>
    </div>
</div>

<!-- KPIs -->
<div class="quick-stats mb-4">
    <div class="stat-card">
        <div class="stat-icon teal"><i class="bi bi-receipt-cutoff"></i></div>
        <div class="stat-info"><span class="stat-value"><?= $vendas_periodo['total'] ?></span><div class="stat-label">Total de Vendas</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-cash-coin"></i></div>
        <div class="stat-info"><span class="stat-value">Kz <?= number_format($vendas_periodo['receita'], 0, ',', '.') ?></span><div class="stat-label">Receita Total</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-calculator"></i></div>
        <div class="stat-info"><span class="stat-value">Kz <?= number_format($vendas_periodo['ticket'], 0, ',', '.') ?></span><div class="stat-label">Ticket Médio</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-calendar-check"></i></div>
        <div class="stat-info"><span class="stat-value"><?= count($vendas_dia) ?></span><div class="stat-label">Dias com Vendas</div></div>
    </div>
</div>

<div class="row g-3">
    <!-- Chart -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="bi bi-bar-chart me-2"></i>Vendas por Dia</h5></div>
            <div class="card-body"><canvas id="reportChart" height="100"></canvas></div>
        </div>
    </div>

    <!-- Por Forma de Pagamento -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="bi bi-credit-card me-2"></i>Por Pagamento</h5></div>
            <div class="card-body">
                <canvas id="payChart" height="180"></canvas>
                <div class="mt-3">
                <?php foreach ($por_pagamento as $pp): ?>
                <div class="d-flex justify-content-between py-1 border-bottom">
                    <span class="text-muted"><?= ucfirst(str_replace('_',' ',$pp['forma_pagamento'])) ?></span>
                    <div class="text-end">
                        <div class="fw-semibold">Kz <?= number_format($pp['t'], 0, ',', '.') ?></div>
                        <small class="text-muted"><?= $pp['c'] ?> vendas</small>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Produtos -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="bi bi-trophy me-2 text-warning"></i>Top 10 Produtos</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>#</th><th>Produto</th><th>Qtd</th><th>Receita</th></tr></thead>
                    <tbody>
                    <?php foreach ($top_produtos as $i => $p): ?>
                    <tr>
                        <td><span class="badge bg-<?= $i<3?'warning':'secondary' ?>"><?= $i+1 ?></span></td>
                        <td class="fw-semibold"><?= htmlspecialchars($p['nome']) ?></td>
                        <td><?= $p['qtd'] ?></td>
                        <td class="text-success fw-semibold">Kz <?= number_format($p['total'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Clientes -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="bi bi-person-check me-2 text-primary"></i>Top 10 Clientes</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>#</th><th>Cliente</th><th>Compras</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($top_clientes as $i => $c): ?>
                    <tr>
                        <td><span class="badge bg-<?= $i<3?'primary':'secondary' ?>"><?= $i+1 ?></span></td>
                        <td class="fw-semibold"><?= htmlspecialchars($c['nome']) ?></td>
                        <td><?= $c['compras'] ?></td>
                        <td class="text-primary fw-semibold">Kz <?= number_format($c['total'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('reportChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{ label: 'Receita (Kz)', data: <?= json_encode($values) ?>, backgroundColor: 'rgba(0,176,155,.2)', borderColor: '#00B09B', borderWidth: 2, borderRadius: 4 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => 'Kz ' + v.toLocaleString() } }, x: { grid: { display: false } } } }
});

new Chart(document.getElementById('payChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_map(fn($p) => ucfirst(str_replace('_',' ',$p['forma_pagamento'])), $por_pagamento)) ?>,
        datasets: [{ data: <?= json_encode(array_column($por_pagamento, 't')) ?>, backgroundColor: ['#00B09B','#3B82F6','#F59E0B','#EF4444','#8B5CF6'], borderWidth: 0 }]
    },
    options: { responsive: true, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
