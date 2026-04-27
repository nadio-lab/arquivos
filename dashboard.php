<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/header.php';

// ── Stats ──
$hoje = date('Y-m-d');
$mes  = date('Y-m');

$vendas_hoje   = db()->fetch("SELECT COUNT(*) c, COALESCE(SUM(total),0) t FROM vendas WHERE DATE(criado_em)=? AND status='concluida'", [$hoje]);
$vendas_mes    = db()->fetch("SELECT COALESCE(SUM(total),0) t FROM vendas WHERE DATE_FORMAT(criado_em,'%Y-%m')=? AND status='concluida'", [$mes]);
$total_produtos = db()->fetch("SELECT COUNT(*) c FROM produtos WHERE ativo=1");
$estoque_baixo  = db()->fetch("SELECT COUNT(*) c FROM produtos WHERE estoque_atual <= estoque_minimo AND ativo=1");
$total_clientes = db()->fetch("SELECT COUNT(*) c FROM clientes");
$contas_vencer  = db()->fetch("SELECT COUNT(*) c, COALESCE(SUM(valor),0) t FROM contas_pagar WHERE status='pendente' AND vencimento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)");

// Vendas últimos 7 dias
$vendas_semana = db()->fetchAll("
    SELECT DATE(criado_em) d, COALESCE(SUM(total),0) t, COUNT(*) c
    FROM vendas WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status='concluida'
    GROUP BY DATE(criado_em) ORDER BY d");

// Top produtos
$top_produtos = db()->fetchAll("
    SELECT p.nome, SUM(vi.quantidade) total_qtd, SUM(vi.subtotal) total_val
    FROM venda_itens vi JOIN produtos p ON p.id=vi.produto_id
    JOIN vendas v ON v.id=vi.venda_id
    WHERE v.status='concluida' AND DATE_FORMAT(v.criado_em,'%Y-%m')=?
    GROUP BY vi.produto_id ORDER BY total_qtd DESC LIMIT 5", [$mes]);

// Últimas vendas
$ultimas_vendas = db()->fetchAll("
    SELECT v.*, COALESCE(c.nome,'Balcão') cliente_nome
    FROM vendas v LEFT JOIN clientes c ON c.id=v.cliente_id
    ORDER BY v.criado_em DESC LIMIT 8");

// Fluxo caixa mês
$receitas = db()->fetch("SELECT COALESCE(SUM(total),0) t FROM vendas WHERE DATE_FORMAT(criado_em,'%Y-%m')=? AND status='concluida'", [$mes]);
$despesas = db()->fetch("SELECT COALESCE(SUM(valor),0) t FROM contas_pagar WHERE DATE_FORMAT(criado_em,'%Y-%m')=? AND status='pago'", [$mes]);
$lucro    = $receitas['t'] - $despesas['t'];

// Prepare chart data
$days = []; $sales = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $days[] = date('d/m', strtotime($day));
    $found = array_filter($vendas_semana, fn($r) => $r['d'] === $day);
    $sales[] = $found ? array_values($found)[0]['t'] : 0;
}
?>

<div class="quick-stats fade-in">
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-cart-check-fill"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($vendas_hoje['t'], 2, ',', '.') ?></span>
            <div class="stat-label">Vendas Hoje (Kz)</div>
            <div class="stat-change up"><i class="bi bi-receipt me-1"></i><?= $vendas_hoje['c'] ?> transacções</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal"><i class="bi bi-graph-up-arrow"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($vendas_mes['t'], 2, ',', '.') ?></span>
            <div class="stat-label">Receita do Mês (Kz)</div>
            <div class="stat-change up"><i class="bi bi-calendar me-1"></i><?= date('F Y') ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="bi bi-box-seam-fill"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= $total_produtos['c'] ?></span>
            <div class="stat-label">Produtos Activos</div>
            <?php if ($estoque_baixo['c'] > 0): ?>
            <div class="stat-change down"><i class="bi bi-exclamation-triangle me-1"></i><?= $estoque_baixo['c'] ?> com estoque baixo</div>
            <?php else: ?>
            <div class="stat-change up"><i class="bi bi-check-circle me-1"></i>Estoque OK</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-people-fill"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= $total_clientes['c'] ?></span>
            <div class="stat-label">Clientes Cadastrados</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon <?= $lucro >= 0 ? 'green' : 'red' ?>"><i class="bi bi-wallet2"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($lucro, 2, ',', '.') ?></span>
            <div class="stat-label">Saldo do Mês (Kz)</div>
            <div class="stat-change <?= $lucro >= 0 ? 'up' : 'down' ?>">
                <i class="bi bi-arrow-<?= $lucro >= 0 ? 'up' : 'down' ?> me-1"></i>Lucro líquido
            </div>
        </div>
    </div>
    <?php if ($contas_vencer['c'] > 0): ?>
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-calendar-x-fill"></i></div>
        <div class="stat-info">
            <span class="stat-value"><?= $contas_vencer['c'] ?></span>
            <div class="stat-label">Contas a Vencer (7 dias)</div>
            <div class="stat-change down">Kz <?= number_format($contas_vencer['t'], 2, ',', '.') ?></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row g-3">
    <!-- Gráfico Vendas -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Vendas — Últimos 7 Dias</h5>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Resumo Financeiro -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-pie-chart-fill text-success me-2"></i>Resumo Financeiro</h5>
                <small class="text-muted"><?= date('F Y') ?></small>
            </div>
            <div class="card-body">
                <div class="finance-summary mb-3">
                    <div class="finance-item receita">
                        <div class="fi-label">Receitas</div>
                        <div class="fi-value">Kz <?= number_format($receitas['t'], 0, ',', '.') ?></div>
                    </div>
                    <div class="finance-item despesa">
                        <div class="fi-label">Despesas</div>
                        <div class="fi-value">Kz <?= number_format($despesas['t'], 0, ',', '.') ?></div>
                    </div>
                </div>
                <canvas id="financeChart" height="160"></canvas>
            </div>
        </div>
    </div>

    <!-- Últimas Vendas -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-receipt me-2"></i>Últimas Vendas</h5>
                <a href="<?= APP_URL ?>/admin/vendas.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr>
                            <th>Nº</th><th>Cliente</th><th>Total</th><th>Pagamento</th><th>Hora</th><th>Estado</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($ultimas_vendas as $v): ?>
                        <tr>
                            <td><code class="text-primary"><?= $v['numero'] ?></code></td>
                            <td><?= htmlspecialchars($v['cliente_nome']) ?></td>
                            <td class="fw-semibold">Kz <?= number_format($v['total'], 2, ',', '.') ?></td>
                            <td><?= ucfirst(str_replace('_', ' ', $v['forma_pagamento'])) ?></td>
                            <td class="text-muted"><?= date('H:i', strtotime($v['criado_em'])) ?></td>
                            <td><span class="badge badge-status-<?= $v['status'] ?>"><?= ucfirst($v['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ultimas_vendas)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma venda registada</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Produtos -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="bi bi-star-fill text-warning me-2"></i>Top Produtos do Mês</h5>
            </div>
            <div class="card-body">
                <?php if (empty($top_produtos)): ?>
                <p class="text-muted text-center py-3">Sem dados disponíveis</p>
                <?php else: ?>
                <?php $max = max(array_column($top_produtos, 'total_qtd')); ?>
                <?php foreach ($top_produtos as $i => $p): ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold" style="font-size:13px"><?= htmlspecialchars($p['nome']) ?></span>
                        <span class="badge bg-light text-dark"><?= $p['total_qtd'] ?> un.</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-primary" style="width:<?= ($p['total_qtd']/$max)*100 ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($days) ?>,
        datasets: [{
            label: 'Vendas (Kz)',
            data: <?= json_encode($sales) ?>,
            backgroundColor: 'rgba(0,176,155,.15)',
            borderColor: '#00B09B',
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,.05)' },
                ticks: { callback: v => 'Kz ' + v.toLocaleString('pt-AO') }
            },
            x: { grid: { display: false } }
        }
    }
});

// Finance Doughnut
const finCtx = document.getElementById('financeChart').getContext('2d');
new Chart(finCtx, {
    type: 'doughnut',
    data: {
        labels: ['Receitas', 'Despesas'],
        datasets: [{
            data: [<?= $receitas['t'] ?>, <?= $despesas['t'] ?>],
            backgroundColor: ['#10B981', '#EF4444'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        cutout: '70%',
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 12 } } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
