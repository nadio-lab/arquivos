<?php
$page_title = 'Ponto de Venda';
require_once __DIR__ . '/header.php';
requireRole(['admin','farmaceutico','atendente']);

// Process sale
$msg = ''; $msg_type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkout') {
    $items      = json_decode($_POST['cart_data'] ?? '[]', true);
    $total      = (float)$_POST['cart_total'];
    $desconto   = (float)($_POST['desconto'] ?? 0);
    $pagamento  = sanitize($_POST['forma_pagamento']);
    $cliente_id = $_POST['cliente_id'] ? (int)$_POST['cliente_id'] : null;
    $obs        = sanitize($_POST['observacoes'] ?? '');
    $user_id    = currentUser()['id'];

    if (!empty($items) && $total > 0) {
        $numero = 'VD' . date('Ymd') . str_pad(rand(1,999), 3, '0', STR_PAD_LEFT);
        $total_final = $total - $desconto;

        $venda_id = db()->insert(
            "INSERT INTO vendas (numero, cliente_id, usuario_id, subtotal, desconto, total, forma_pagamento, observacoes, status) VALUES (?,?,?,?,?,?,?,?,'concluida')",
            [$numero, $cliente_id, $user_id, $total, $desconto, $total_final, $pagamento, $obs]
        );

        foreach ($items as $item) {
            $sub = $item['preco_venda'] * $item['qty'];
            db()->execute("INSERT INTO venda_itens (venda_id,produto_id,quantidade,preco_unitario,subtotal) VALUES (?,?,?,?,?)",
                [$venda_id, $item['id'], $item['qty'], $item['preco_venda'], $sub]);
            db()->execute("UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id=?", [$item['qty'], $item['id']]);
        }
        // Register in caixa
        db()->insert("INSERT INTO caixa (tipo,descricao,valor,forma_pagamento,venda_id,usuario_id) VALUES ('entrada',?,?,?,?,?)",
            ["Venda $numero", $total_final, $pagamento, $venda_id, $user_id]);

        $msg = "Venda <strong>$numero</strong> registada com sucesso! Total: Kz " . number_format($total_final, 2, ',', '.');
    } else {
        $msg = 'Carrinho vazio ou valor inválido.'; $msg_type = 'danger';
    }
}

$produtos  = db()->fetchAll("SELECT id, codigo, nome, preco_venda, estoque_atual FROM produtos WHERE ativo=1 AND estoque_atual>0 ORDER BY nome");
$clientes  = db()->fetchAll("SELECT id, nome FROM clientes ORDER BY nome");
$cat_filter = $_GET['cat'] ?? '';
$search    = sanitize($_GET['q'] ?? '');
$categorias = db()->fetchAll("SELECT * FROM categorias ORDER BY nome");

$where = "WHERE p.ativo=1 AND p.estoque_atual>0";
$params = [];
if ($cat_filter) { $where .= " AND p.categoria_id=?"; $params[] = $cat_filter; }
if ($search) { $where .= " AND p.nome LIKE ?"; $params[] = "%$search%"; }
$produtos_filtrados = db()->fetchAll("SELECT p.*, c.nome cat FROM produtos p LEFT JOIN categorias c ON c.id=p.categoria_id $where ORDER BY p.nome", $params);
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $msg_type ?> alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-3">
    <!-- Products Panel -->
    <div class="col-lg-8">
        <!-- Search / Filter -->
        <div class="card mb-3">
            <div class="card-body py-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-sm-5">
                        <input type="text" name="q" class="form-control" placeholder="🔍 Pesquisar produto..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-sm-4">
                        <select name="cat" class="form-select">
                            <option value="">Todas as categorias</option>
                            <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $cat_filter == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="row g-2">
            <?php foreach ($produtos_filtrados as $p): ?>
            <div class="col-6 col-md-4 col-xl-3">
                <div class="pos-product-card" onclick="Cart.add(<?= json_encode(['id'=>$p['id'],'nome'=>$p['nome'],'preco_venda'=>(float)$p['preco_venda']]) ?>)">
                    <div style="font-size:28px;margin-bottom:6px">💊</div>
                    <div class="fw-semibold" style="font-size:12.5px;line-height:1.3"><?= htmlspecialchars($p['nome']) ?></div>
                    <div class="text-primary fw-bold mt-1" style="font-size:13px">Kz <?= number_format($p['preco_venda'], 2, ',', '.') ?></div>
                    <div class="text-muted" style="font-size:11px">Stock: <?= $p['estoque_atual'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($produtos_filtrados)): ?>
            <div class="col-12"><div class="text-center text-muted py-4"><i class="bi bi-inbox display-4 d-block mb-2"></i>Nenhum produto encontrado</div></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cart Panel -->
    <div class="col-lg-4">
        <div class="pos-cart">
            <div class="pos-cart-title"><i class="bi bi-cart3 me-2"></i>Carrinho de Compras</div>
            <div id="cart-items" style="max-height:300px;overflow-y:auto">
                <p class="text-muted text-center small mt-3">Carrinho vazio</p>
            </div>
            <div class="pos-total mt-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span style="font-size:13px;opacity:.8">TOTAL A PAGAR</span>
                    <div class="pos-total-value" id="cart-total-value">Kz 0,00</div>
                </div>
            </div>

            <form method="POST" id="checkout-form" class="mt-3">
                <input type="hidden" name="action" value="checkout">
                <input type="hidden" name="cart_data" id="cart-data">
                <input type="hidden" name="cart_total" id="cart-total">
                <div class="mb-2">
                    <select name="cliente_id" class="form-select form-select-sm" style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff">
                        <option value="">Cliente (opcional)</option>
                        <?php foreach ($clientes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <select name="forma_pagamento" class="form-select form-select-sm" required style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff">
                        <option value="dinheiro">💵 Dinheiro</option>
                        <option value="pix">📱 Transferência/MB Way</option>
                        <option value="cartao_credito">💳 Cartão Crédito</option>
                        <option value="cartao_debito">💳 Cartão Débito</option>
                        <option value="fiado">📝 Fiado</option>
                    </select>
                </div>
                <div class="mb-3">
                    <input type="number" name="desconto" placeholder="Desconto (Kz)" step="0.01" min="0" class="form-control form-control-sm" style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff">
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" id="btn-checkout" class="btn btn-success fw-bold" disabled>
                        <i class="bi bi-check-circle me-2"></i>Finalizar Venda
                    </button>
                    <button type="button" class="btn btn-outline-light btn-sm" onclick="Cart.clear()">
                        <i class="bi bi-trash me-1"></i>Limpar Carrinho
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
