<?php
include '../includes/auth.php';
include '../includes/db.php';

// Acesso restrito a funcionários
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Processar atualização de status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = filter_var($_POST['order_id'], FILTER_SANITIZE_NUMBER_INT);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    $success = "Status do pedido atualizado com sucesso!";
}

// Buscar pedidos
$query = "SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaf Admin - Pedidos</title>
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
                <a href="index.php" class="nav-link">
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
                <a href="orders.php" class="nav-link active">
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
                <h2 class="mb-4">Gerenciamento de Pedidos</h2>

                <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Todos os Pedidos</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nº Pedido</th>
                                        <th>Cliente</th>
                                        <th>Total</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                        <td>R$ <?php echo number_format($order['total'], 2, ',', '.'); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                            switch($order['status']) {
                                                case 'pendente': echo 'warning'; break;
                                                case 'processando': echo 'info'; break;
                                                case 'enviado': echo 'primary'; break;
                                                case 'entregue': echo 'success'; break;
                                                case 'cancelado': echo 'danger'; break;
                                            }
                                            ?> text-capitalize"><?php echo $order['status']; ?></span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-eye"></i> Detalhes
                                            </button>
                                            <?php if (hasAccess('gerente') || hasAccess('estoquista')): ?>
                                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $order['id']; ?>">
                                                <i class="fas fa-edit"></i> Status
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- Modal de Detalhes -->
                                    <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detalhes do Pedido #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php
                                                    // Buscar itens do pedido
                                                    $stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                                                    $stmt->execute([$order['id']]);
                                                    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                    ?>
                                                    <h6>Itens do Pedido:</h6>
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Produto</th>
                                                                <th>Quantidade</th>
                                                                <th>Preço Unitário</th>
                                                                <th>Subtotal</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($items as $item): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                                <td><?php echo $item['quantity']; ?></td>
                                                                <td>R$ <?php echo number_format($item['price'], 2, ',', '.'); ?></td>
                                                                <td>R$ <?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                    <div class="d-flex justify-content-end">
                                                        <strong>Total: R$ <?php echo number_format($order['total'], 2, ',', '.'); ?></strong>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal de Status -->
                                    <div class="modal fade" id="statusModal<?php echo $order['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Alterar Status do Pedido</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <div class="mb-3">
                                                            <label for="status<?php echo $order['id']; ?>" class="form-label">Status</label>
                                                            <select class="form-select" id="status<?php echo $order['id']; ?>" name="status" required>
                                                                <option value="pendente" <?php echo $order['status'] == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                                                <option value="processando" <?php echo $order['status'] == 'processando' ? 'selected' : ''; ?>>Processando</option>
                                                                <option value="enviado" <?php echo $order['status'] == 'enviado' ? 'selected' : ''; ?>>Enviado</option>
                                                                <option value="entregue" <?php echo $order['status'] == 'entregue' ? 'selected' : ''; ?>>Entregue</option>
                                                                <option value="cancelado" <?php echo $order['status'] == 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" name="update_status" class="btn btn-success">Atualizar Status</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>