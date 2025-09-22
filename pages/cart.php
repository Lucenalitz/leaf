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

// Calcular total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Atualizar quantidade ou remover item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        $item_id = filter_var($_POST['item_id'], FILTER_SANITIZE_NUMBER_INT);
        $quantity = filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_INT);
        
        // Verificar se a quantidade é válida
        $stmt = $pdo->prepare("SELECT p.quantity as stock FROM cart_items ci 
                              JOIN products p ON ci.product_id = p.id 
                              WHERE ci.id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        
        if ($item && $quantity > 0 && $quantity <= $item['stock']) {
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $stmt->execute([$quantity, $item_id]);
            header('Location: cart.php');
            exit;
        }
    } else if (isset($_POST['remove_item'])) {
        $item_id = filter_var($_POST['item_id'], FILTER_SANITIZE_NUMBER_INT);
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ?");
        $stmt->execute([$item_id]);
        header('Location: cart.php');
        exit;
    }
}
?>

<div class="container py-5">
    <h2 class="mb-4">Meu Carrinho</h2>
    
    <?php if (empty($cart_items)): ?>
    <div class="alert alert-info">
        Seu carrinho está vazio. <a href="products.php">Continue comprando</a>.
    </div>
    <?php else: ?>
    
    <div class="row">
        <div class="col-md-8">
            <?php foreach ($cart_items as $item): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <img src="../uploads/products/<?php echo $item['image_url'] ?: 'default.jpg'; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </div>
                        <div class="col-md-4">
                            <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                            <p class="price">R$ <?php echo number_format($item['price'], 2, ',', '.'); ?></p>
                        </div>
                        <div class="col-md-3">
                            <form method="POST" class="d-flex align-items-center">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="form-control me-2" style="width: 80px;">
                                <button type="submit" name="update_quantity" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </form>
                        </div>
                        <div class="col-md-2">
                            <p class="fw-bold">R$ <?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?></p>
                        </div>
                        <div class="col-md-1">
                            <form method="POST">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_item" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Resumo do Pedido</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo htmlspecialchars($item['name']); ?> x<?php echo $item['quantity']; ?></span>
                        <span>R$ <?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total:</span>
                        <span class="cart-total">R$ <?php echo number_format($total, 2, ',', '.'); ?></span>
                    </div>
                    
                    <a href="checkout.php" class="btn btn-success w-100 mt-3">Finalizar Compra</a>
                    <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">Continuar Comprando</a>
                </div>
            </div>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>