<?php
include '../includes/auth.php';
include '../includes/db.php';

// Acesso restrito a funcionários
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Estatísticas para o dashboard
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products");
$stmt->execute();
$total_products = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE status = 'pendente'");
$stmt->execute();
$pending_orders = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$total_users = $stmt->fetch()['total'];

// Últimos pedidos
$stmt = $pdo->prepare("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Produtos com estoque baixo
$stmt = $pdo->prepare("SELECT * FROM products WHERE quantity < 10 ORDER BY quantity ASC LIMIT 5");
$stmt->execute();
$low_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaf Admin - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="p-3 text-center border-bottom">
                <h4 class="mb-0">Leaf Admin</h4>
                <p class="text-muted mb-0"><?php echo $_SESSION['employee_name']; ?></p>
                <small class="text-capitalize"><?php echo $_SESSION['employee_role']; ?></small>
            </div>
            <nav class="nav flex-column">
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <?php if (hasAccess('gerente')): ?>
                <a href="employees.php" class="nav-link">
                    <i class="fas fa-users me-2"></i>Funcionários
                </a>
                <?php endif; ?>
                <?php if (hasAccess('estoquista')): ?>
                <a href="products.php" class="nav-link">
                    <i class="fas fa-box me-2"></i>Produtos
                </a>
                <?php endif; ?>
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart me-2"></i>Pedidos
                </a>
                <a href="?logout=true" class="nav-link">
                    <i class="fas fa-sign-out-alt me-2"></i>Sair
                </a>
            </nav>
        </div>

        <!-- Conteúdo -->
        <div class="flex-grow-1">
            <div class="admin-content">
                <h2 class="mb-4">Dashboard</h2>

                <!-- Estatísticas -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card dashboard-card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title">Total de Produtos</h5>
                                        <h2 class="mb-0"><?php echo $total_products; ?></h2>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-box fa-3x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card bg-warning text-dark">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title">Pedidos Pendentes</h5>
                                        <h2 class="mb-0"><?php echo $pending_orders; ?></h2>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-shopping-cart fa-3x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title">Total de Clientes</h5>
                                        <h2 class="mb-0"><?php echo $total_users; ?></h2>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-users fa-3x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Pedidos Recentes -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Pedidos Recentes</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_orders)): ?>
                                <p class="text-muted">Nenhum pedido recente.</p>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Cliente</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td><?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                                <td>R$ <?php echo number_format($order['total'], 2, ',', '.'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                    switch($order['status']) {
                                                        case 'pendente': echo 'warning'; break;
                                                        case 'processando': echo 'info'; break;
                                                        case 'enviado': echo 'primary'; break;
                                                        case 'entregue': echo 'success'; break;
                                                        case 'cancelado': echo 'danger'; break;
                                                    }
                                                    ?>"><?php echo $order['status']; ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Estoque Baixo -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">Estoque Baixo</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($low_stock)): ?>
                                <p class="text-muted">Nenhum produto com estoque baixo.</p>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Produto</th>
                                                <th>Estoque</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($low_stock as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td class="<?php echo $product['quantity'] < 5 ? 'stock-low' : ''; ?>">
                                                    <?php echo $product['quantity']; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                    switch($product['status']) {
                                                        case 'disponivel': echo 'success'; break;
                                                        case 'pendente': echo 'warning'; break;
                                                        case 'vendido': echo 'danger'; break;
                                                    }
                                                    ?>"><?php echo $product['status']; ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>