<?php
include '../includes/header.php';
include '../includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Buscar carrinho do usuário
$stmt = $pdo->prepare("SELECT ci.*, p.name, p.price, p.image_url, p.quantity as stock 
                      FROM cart c 
                      JOIN cart_items ci ON c.id = ci.cart_id 
                      JOIN products p ON ci.product_id = p.id 
                      WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Se o carrinho estiver vazio, redirecionar
if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

// Calcular total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Buscar informações do usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Processar checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se há estoque suficiente para todos os itens
    $valid = true;
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            $valid = false;
            $error = "Desculpe, o produto '{$item['name']}' não tem estoque suficiente.";
            break;
        }
    }

    if ($valid) {
        // Iniciar transação
        $pdo->beginTransaction();

        try {
            // Criar pedido
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'pendente')");
            $stmt->execute([$user_id, $total]);
            $order_id = $pdo->lastInsertId();

            // Adicionar itens ao pedido e atualizar estoque
            foreach ($cart_items as $item) {
                // Inserir item do pedido
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);

                // Atualizar estoque
                $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }

            // Limpar carrinho
            $stmt = $pdo->prepare("DELETE cart_items FROM cart_items JOIN cart ON cart_items.cart_id = cart.id WHERE cart.user_id = ?");
            $stmt->execute([$user_id]);

            $pdo->commit();

            $success = "Pedido realizado com sucesso! Número do pedido: #" . str_pad($order_id, 6, '0', STR_PAD_LEFT);
            // Redirecionar para página de sucesso ou mostrar mensagem
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erro ao processar pedido. Tente novamente.";
        }
    }
}
?>

<div class="container py-5">
    <h2 class="mb-4">Finalizar Compra</h2>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <div class="text-center mt-4">
        <a href="products.php" class="btn btn-success">Continuar Comprando</a>
    </div>
    <?php else: ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Informações de Entrega</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="checkoutForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nome completo</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Endereço</label>
                            <textarea class="form-control" id="address" name="address" rows="2" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Itens do Pedido</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                        <div>
                            <h6 class="my-0"><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?></h6>
                        </div>
                        <span class="text-muted">R$ <?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <div class="d-flex justify-content-between mt-3">
                        <strong>Total</strong>
                        <strong class="cart-total">R$ <?php echo number_format($total, 2, ',', '.'); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Resumo do Pedido</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <span>Subtotal</span>
                        <span>R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Frete</span>
                        <span>Grátis</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total</span>
                        <span class="cart-total">R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
                    </div>

                    <button type="submit" form="checkoutForm" class="btn btn-success w-100 mt-3">Confirmar Pedido</button>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>