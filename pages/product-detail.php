<?php
include '../includes/header.php';
include '../includes/db.php';

if (!isset($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$product_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// Buscar informações do produto
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE p.id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

// Adicionar ao carrinho
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart']) && isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $quantity = filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_INT);
    
    // Verificar se o usuário já tem um carrinho
    $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch();
    
    if (!$cart) {
        // Criar carrinho se não existir
        $stmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
        $stmt->execute([$user_id]);
        $cart_id = $pdo->lastInsertId();
    } else {
        $cart_id = $cart['id'];
    }
    
    // Verificar se o produto já está no carrinho
    $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
    $stmt->execute([$cart_id, $product_id]);
    $existing_item = $stmt->fetch();
    
    if ($existing_item) {
        // Atualizar quantidade se já existir
        $new_quantity = $existing_item['quantity'] + $quantity;
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->execute([$new_quantity, $existing_item['id']]);
    } else {
        // Adicionar novo item ao carrinho
        $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$cart_id, $product_id, $quantity]);
    }
    
    $success = "Produto adicionado ao carrinho!";
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-6">
            <img src="../uploads/products/<?php echo $product['image_url'] ?: 'default.jpg'; ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        <div class="col-md-6">
            <span class="badge bg-success mb-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
            <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="lead"><?php echo htmlspecialchars($product['description']); ?></p>
            
            <div class="d-flex align-items-center mb-3">
                <span class="price fs-3 me-3">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></span>
                <?php if ($product['quantity'] > 0): ?>
                <span class="badge bg-success">Em estoque</span>
                <?php else: ?>
                <span class="badge bg-danger">Esgotado</span>
                <?php endif; ?>
            </div>
            
            <?php if (isLoggedIn()): ?>
                <?php if ($product['quantity'] > 0): ?>
                <form method="POST">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <label for="quantity" class="form-label">Quantidade:</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['quantity']; ?>" style="width: 80px;">
                        </div>
                        <div class="col-auto">
                            <button type="submit" name="add_to_cart" class="btn btn-success">
                                <i class="fas fa-shopping-cart me-2"></i>Adicionar ao Carrinho
                            </button>
                        </div>
                    </div>
                </form>
                <?php else: ?>
                <button class="btn btn-secondary" disabled>Produto Indisponível</button>
                <?php endif; ?>
            <?php else: ?>
            <div class="alert alert-info">
                <a href="login.php">Faça login</a> para adicionar produtos ao carrinho.
            </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
            <div class="alert alert-success mt-3"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <hr class="my-4">
            
            <h5>Informações do Produto</h5>
            <ul class="list-unstyled">
                <li><strong>Categoria:</strong> <?php echo htmlspecialchars($product['category_name']); ?></li>
                <li><strong>Disponibilidade:</strong> <?php echo $product['quantity'] > 0 ? 'Em estoque' : 'Esgotado'; ?></li>
                <li><strong>Quantidade em estoque:</strong> <?php echo $product['quantity']; ?></li>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>