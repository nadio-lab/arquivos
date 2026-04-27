<?php
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();
$user = currentUser();
$page_title = $page_title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="pt-AO">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> | <?= $page_title ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/assets/css/main.css" rel="stylesheet">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="bi bi-capsule-pill"></i>
        </div>
        <div class="brand-text">
            <span class="brand-name"><?= APP_NAME ?></span>
            <span class="brand-tagline">Sistema de Gestão</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-label">Principal</div>
        <a href="<?= APP_URL ?>/admin/dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
        </a>

        <?php if (hasPermission('vendas')): ?>
        <div class="nav-section-label">Operações</div>
        <a href="<?= APP_URL ?>/admin/vendas.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'vendas.php' ? 'active' : '' ?>">
            <i class="bi bi-cart3"></i><span>Ponto de Venda</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('produtos')): ?>
        <a href="<?= APP_URL ?>/admin/produtos.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'produtos.php' ? 'active' : '' ?>">
            <i class="bi bi-box-seam-fill"></i><span>Produtos</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('estoque')): ?>
        <a href="<?= APP_URL ?>/admin/estoque.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'estoque.php' ? 'active' : '' ?>">
            <i class="bi bi-archive-fill"></i><span>Estoque</span>
            <?php
            $alertas = db()->fetch("SELECT COUNT(*) as total FROM produtos WHERE estoque_atual <= estoque_minimo AND ativo = 1");
            if ($alertas['total'] > 0): ?>
            <span class="badge bg-danger ms-auto"><?= $alertas['total'] ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('clientes')): ?>
        <a href="<?= APP_URL ?>/admin/clientes.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'clientes.php' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i><span>Clientes</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('financas')): ?>
        <div class="nav-section-label">Financeiro</div>
        <a href="<?= APP_URL ?>/admin/financas.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'financas.php' ? 'active' : '' ?>">
            <i class="bi bi-wallet2"></i><span>Finanças</span>
        </a>
        <a href="<?= APP_URL ?>/admin/caixa.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'caixa.php' ? 'active' : '' ?>">
            <i class="bi bi-cash-stack"></i><span>Caixa</span>
        </a>
        <a href="<?= APP_URL ?>/admin/contas_pagar.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'contas_pagar.php' ? 'active' : '' ?>">
            <i class="bi bi-arrow-up-circle-fill"></i><span>Contas a Pagar</span>
        </a>
        <a href="<?= APP_URL ?>/admin/contas_receber.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'contas_receber.php' ? 'active' : '' ?>">
            <i class="bi bi-arrow-down-circle-fill"></i><span>Contas a Receber</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('relatorios')): ?>
        <div class="nav-section-label">Análise</div>
        <a href="<?= APP_URL ?>/admin/relatorios.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'relatorios.php' ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line-fill"></i><span>Relatórios</span>
        </a>
        <?php endif; ?>

        <?php if (hasPermission('funcionarios')): ?>
        <div class="nav-section-label">Gestão</div>
        <a href="<?= APP_URL ?>/admin/funcionarios.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'funcionarios.php' ? 'active' : '' ?>">
            <i class="bi bi-person-badge-fill"></i><span>Funcionários</span>
        </a>
        <a href="<?= APP_URL ?>/admin/fornecedores.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'fornecedores.php' ? 'active' : '' ?>">
            <i class="bi bi-truck"></i><span>Fornecedores</span>
        </a>
        <?php endif; ?>

        <?php if ($user['perfil'] === 'admin'): ?>
        <a href="<?= APP_URL ?>/admin/configuracoes.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'configuracoes.php' ? 'active' : '' ?>">
            <i class="bi bi-gear-fill"></i><span>Configurações</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($user['nome'], 0, 2)) ?></div>
            <div class="user-details">
                <span class="user-name"><?= explode(' ', $user['nome'])[0] ?></span>
                <span class="user-role"><?= ucfirst($user['perfil']) ?></span>
            </div>
        </div>
        <a href="<?= APP_URL ?>/logout.php" class="btn-logout" title="Sair">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="main-content" id="main-content">
    <!-- Top Navbar -->
    <header class="top-header">
        <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= APP_URL ?>/admin/dashboard.php">Início</a></li>
                    <li class="breadcrumb-item active"><?= $page_title ?></li>
                </ol>
            </nav>
        </div>
        <div class="header-right">
            <div class="header-date">
                <i class="bi bi-calendar3"></i>
                <span><?= date('d/m/Y') ?></span>
            </div>
            <div class="dropdown">
                <button class="btn-header-user dropdown-toggle" data-bs-toggle="dropdown">
                    <div class="user-avatar-sm"><?= strtoupper(substr($user['nome'], 0, 2)) ?></div>
                    <span><?= explode(' ', $user['nome'])[0] ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-person-circle me-2"></i>Perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= APP_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="page-content">
        <div class="page-header">
            <h1 class="page-title"><?= $page_title ?></h1>
        </div>
