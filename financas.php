<?php
$page_title = 'Finanças';
require_once __DIR__ . '/header.php';
requireRole(['admin','financeiro']);

$mes  = $_GET['mes'] ?? date('Y-m');
$ano  = substr($mes, 0, 4);

// KPIs
$receitas     = db()->fetch("SELECT COALESCE(SUM(total),0) t, COUNT(*) c FROM vendas WHERE DATE_FORMAT(criado_em,'%Y-%m')=? AND status='concluida'", [$mes]);
$despesas_pg  = db()->fetch("SELECT COALESCE(SUM(valor),0) t FROM contas_pagar WHERE DATE_FORMAT(criado_em,'%Y-%m')=? AND status='pago'", [$mes]);
$cp_pendente  = db()->fetch("SELECT COUNT(*) c, COALESCE(SUM(valor),0) t FROM contas_pagar WHERE status='pendente'");
$cr_pendente  = db()->fetch("SELECT COUNT(*) c, COALESCE(SUM(valor),0) t FROM contas_receber WHERE status='pendente'");
$saldo        = $receitas['t'] - $despesas_pg['t'];

// Monthly chart (last 6 months)
$meses_data = db()->fetchAll("
    SELECT DATE_FORMAT(criado_em,'%Y-%m') m, SUM(total) t
    FROM vendas WHERE status='concluida' AND criado_em >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY m ORDER BY m");

// Recent transactions in caixa
$transacoes = db()->fetchAll("
    SELECT c.*, u.nome func_nome FROM caixa c JOIN usuarios u ON u.id=c.usuario_id
    ORDER BY c.criado_em DESC LIMIT 20");

// Contas a pagar próximas
$contas_vencer = db()->fetchAll("
    SELECT cp.*, f.nome forn_nome FROM contas_pagar cp LEFT JOIN fornecedores f ON f.id=cp.fornecedor_id
    WHERE cp.status='pendente' ORDER BY cp.vencimento LIMIT 10");
?>

<!-- KPI Row -->
<div class="quick-stats fade-in mb-4">
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-arrow-down-circle-fill"></i></div>
        <div class="stat-info">
            <span class="stat-value">Kz <?= number_format($receitas['t'], 0, ',', '.') ?></span>
            <div class="stat-label">Receitas — <?= date('M/Y', strtotime($mes . '-01')) ?></div>
            <div class="stat-change up"><?= $receitas['c'] ?> vendas</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-arrow-up-circle-fill"></i></div>
        <div class="stat-info">
            <span class="stat-value">Kz <?= number_format($despesas_pg['t'], 0, ',', '.') ?></span>
            <div class="stat-label">Despesas Pagas</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?= $saldo >= 0 ? 'teal' : 'red' ?>"><i class="bi bi-wallet2"></i></div>
        <div class="stat-info">
            <span class="stat-value">Kz <?= number_format(abs($saldo), 0, ',', '.') ?></span>
            <div class="stat-label">Saldo Líquido</div>
            <div class="stat-change <?= $saldo >= 0 ? 'up' : 'down' ?>"><?= $saldo >= 0 ? '▲ Positivo' : '▼ Negativo' ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-hourglass-split"></i></div>
        <div class="stat-info">
            <span class="stat-value">Kz <?= number_format($cp_pendente['t'], 0, ',', '.') ?></span>
            <div class="stat-label">Contas a Pagar Pendentes</div>
            <div class="stat-change down"><?= $cp_pendente['c'] ?> contas</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-clock-history"></i></div>
        <div class="stat-info">
            <span class="stat-value">Kz <?= number_format($cr_pendente['t'], 0, ',', '.') ?></span>
            <div class="stat-label">Contas a Receber</div>
            <div class="stat-change up"><?= $cr_pendente['c'] ?> contas</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Revenue Chart -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-graph-up me-2 text-success"></i>Receitas — Últimos 6 Meses</h5>
                <form class="d-flex gap-2">
                    <input type="month" name="mes" value="<?= $mes ?>" class="form-control form-control-sm" style="width:160px">
                    <button class="btn btn-sm btn-outline-primary">Filtrar</button>
                </form>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="110"></canvas>
            </div>
        </div>
    </div>

    <!-- Balance Summary -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="bi bi-pie-chart me-2"></i>Balanço</h5></div>
            <div class="card-body">
                <canvas id="balanceChart" height="200"></canvas>
                <div class="mt-3">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Total Receitas</span>
                        <span class="fw-bold text-success">Kz <?= number_format($receitas['t'], 2, ',', '.') ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Total Despesas</span>
                        <span class="fw-bold text-danger">Kz <?= number_format($despesas_pg['t'], 2, ',', '.') ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="fw-semibold">Resultado</span>
                        <span class="fw-bold <?= $saldo >= 0 ? 'text-success' : 'text-danger' ?>">Kz <?= number_format($saldo, 2, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Bills -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-calendar-exclamation me-2 text-danger"></i>Contas a Pagar</h5>
                <a href="contas_pagar.php" class="btn btn-sm btn-outline-primary">Gerir</a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Descrição</th><th>Fornecedor</th><th>Valor</th><th>Vencimento</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($contas_vencer as $c):
                        $vencido = $c['vencimento'] < date('Y-m-d') && $c['status'] === 'pendente';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($c['descricao']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($c['forn_nome'] ?? '-') ?></td>
                        <td class="fw-semibold">Kz <?= number_format($c['valor'], 2, ',', '.') ?></td>
                        <td class="<?= $vencido ? 'text-danger fw-semibold' : '' ?>"><?= date('d/m/Y', strtotime($c['vencimento'])) ?></td>
                        <td><span class="badge badge-status-<?= $vencido ? 'vencido' : $c['status'] ?>"><?= $vencido ? 'Vencido' : ucfirst($c['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($contas_vencer)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">Sem contas pendentes</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Cash Movements -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-cash-stack me-2 text-primary"></i>Movimentos de Caixa</h5>
                <a href="caixa.php" class="btn btn-sm btn-outline-primary">Ver tudo</a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Descrição</th><th>Tipo</th><th>Valor</th><th>Data/Hora</th></tr></thead>
                    <tbody>
                    <?php foreach ($transacoes as $t): ?>
                    <tr>
                        <td><?= htmlspecialchars($t['descricao']) ?></td>
                        <td>
                            <span class="badge <?= $t['tipo'] === 'entrada' ? 'bg-success' : 'bg-danger' ?>">
                                <?= $t['tipo'] === 'entrada' ? '▲ Entrada' : '▼ Saída' ?>
                            </span>
                        </td>
                        <td class="fw-semibold <?= $t['tipo'] === 'entrada' ? 'text-success' : 'text-danger' ?>">
                            <?= $t['tipo'] === 'entrada' ? '+' : '-' ?> Kz <?= number_format($t['valor'], 2, ',', '.') ?>
                        </td>
                        <td class="text-muted" style="font-size:12px"><?= date('d/m H:i', strtotime($t['criado_em'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Chart data
$labels = []; $rev = [];
foreach ($meses_data as $m) { $labels[] = date('M/Y', strtotime($m['m'].'-01')); $rev[] = $m['t']; }
?>
<script>
// Revenue Line Chart
new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Receitas (Kz)',
            data: <?= json_encode($rev) ?>,
            borderColor: '#10B981',
            backgroundColor: 'rgba(16,185,129,.1)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#10B981',
            pointRadius: 5,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { callback: v => 'Kz ' + v.toLocaleString() } },
            x: { grid: { display: false } }
        }
    }
});

// Balance Doughnut
new Chart(document.getElementById('balanceChart'), {
    type: 'doughnut',
    data: {
        labels: ['Receitas', 'Despesas'],
        datasets: [{ data: [<?= $receitas['t'] ?>, <?= $despesas_pg['t'] ?>], backgroundColor: ['#10B981','#EF4444'], borderWidth: 0 }]
    },
    options: { responsive: true, cutout: '65%', plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
