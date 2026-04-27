<?php
require_once __DIR__ . '/config.php';

session_start();

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect(APP_URL . '/login.php');
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['user_perfil'] !== 'admin') {
        redirect(APP_URL . '/admin/dashboard.php?error=acesso_negado');
    }
}

function requireRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['user_perfil'], $roles)) {
        redirect(APP_URL . '/admin/dashboard.php?error=acesso_negado');
    }
}

function login(string $email, string $senha): bool {
    $user = db()->fetch(
        "SELECT * FROM usuarios WHERE email = ? AND ativo = 1",
        [$email]
    );
    if ($user && password_verify($senha, $user['senha'])) {
        $_SESSION['user_id']     = $user['id'];
        $_SESSION['user_nome']   = $user['nome'];
        $_SESSION['user_email']  = $user['email'];
        $_SESSION['user_perfil'] = $user['perfil'];
        $_SESSION['user_foto']   = $user['foto'];
        return true;
    }
    return false;
}

function logout(): void {
    session_destroy();
    redirect(APP_URL . '/login.php');
}

function currentUser(): array {
    return [
        'id'     => $_SESSION['user_id'] ?? 0,
        'nome'   => $_SESSION['user_nome'] ?? '',
        'email'  => $_SESSION['user_email'] ?? '',
        'perfil' => $_SESSION['user_perfil'] ?? '',
        'foto'   => $_SESSION['user_foto'] ?? null,
    ];
}

function hasPermission(string $permission): bool {
    $perfil = $_SESSION['user_perfil'] ?? '';
    $permissions = [
        'admin'         => ['dashboard','produtos','vendas','clientes','financas','relatorios','estoque','funcionarios','configuracoes'],
        'farmaceutico'  => ['dashboard','produtos','vendas','clientes','estoque'],
        'atendente'     => ['dashboard','vendas','clientes'],
        'financeiro'    => ['dashboard','financas','relatorios'],
    ];
    return in_array($permission, $permissions[$perfil] ?? []);
}
