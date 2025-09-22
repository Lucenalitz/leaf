<?php
include 'auth.php';
include 'config.php'; // Já define a função url()

// Logout handling
if (isset($_GET['logout'])) {
    logout();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaf - Mercado Orgânico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo url('styles.css'); ?>">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="<?php echo url('index.php'); ?>">
                <i class="fas fa-leaf me-2"></i>Leaf Market
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('index.php'); ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('pages/products.php'); ?>">Produtos</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('pages/cart.php'); ?>">
                            <i class="fas fa-shopping-cart"></i> Carrinho
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i> <?php echo $_SESSION['user_name']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo url('pages/profile.php'); ?>">Perfil</a></li>
                            <li><a class="dropdown-item" href="<?php echo url('admin/orders.php'); ?>">Meus Pedidos</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="?logout=true">Sair</a></li>
                        </ul>
                    </li>
                    <?php elseif (isAdminLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('admin/'); ?>">Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?logout=true">Sair</a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('pages/login.php'); ?>">Entrar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('pages/register.php'); ?>">Cadastrar</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>