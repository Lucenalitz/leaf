<?php
include '../includes/auth.php';
include '../includes/db.php';

// Acesso restrito a estoquista e gerente
if (!isAdminLoggedIn() || !hasAccess('estoquista')) {
    header('Location: login.php');
    exit;
}

// Obter categorias
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar formulário de adição/edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $quantity = filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_INT);
    $category_id = filter_var($_POST['category_id'], FILTER_SANITIZE_NUMBER_INT);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

    // Validações
    if ($price <= 0) {
        $error = "O preço deve ser maior que zero.";
    } else if ($quantity < 0) {
        $error = "A quantidade não pode ser negativa.";
    } else {
        if (isset($_POST['product_id'])) {
            // Editar produto existente
            $product_id = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, category_id = ?, status = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $price, $quantity, $category_id, $status, $product_id])) {
                $success = "Produto atualizado com sucesso!";
            } else {
                $error = "Erro ao atualizar produto.";
            }
        } else {
            // Adicionar novo produto
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, quantity, category_id, status) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $description, $price, $quantity, $category_id, $status])) {
                $success = "Produto adicionado com sucesso!";
            } else {
                $error = "Erro ao adicionar produto.";
            }
        }
    }
}

// Processar exclusão
if (isset($_GET['delete'])) {
    $product_id = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$product_id])) {
        $success = "Produto excluído com sucesso!";
    } else {
        $error = "Erro ao excluir produto.";
    }
}

// Buscar produtos
$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaf Admin - Produtos</title>
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
                <a href="products.php" class="nav-link active">
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
                <h2 class="mb-4">Gerenciamento de Produtos</h2>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Adicionar/Editar Produto</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="productForm">
                                    <input type="hidden" name="product_id" id="product_id">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nome do Produto</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Descrição</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Preço (R$)</label>
                                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0.01" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Quantidade em Estoque</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" min="0" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Categoria</label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="">Selecione uma categoria</option>
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="disponivel">Disponível</option>
                                            <option value="pendente">Pendente</option>
                                            <option value="vendido">Vendido</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">Salvar Produto</button>
                                    <button type="button" id="cancelEdit" class="btn btn-outline-secondary w-100 mt-2" style="display: none;">Cancelar Edição</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Lista de Produtos</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Preço</th>
                                                <th>Estoque</th>
                                                <th>Categoria</th>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td>R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></td>
                                                <td class="<?php echo $product['quantity'] < 10 ? 'stock-low' : ''; ?>">
                                                    <?php echo $product['quantity']; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                    switch($product['status']) {
                                                        case 'disponivel': echo 'success'; break;
                                                        case 'pendente': echo 'warning'; break;
                                                        case 'vendido': echo 'danger'; break;
                                                    }
                                                    ?>"><?php echo $product['status']; ?></span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-product" 
                                                            data-id="<?php echo $product['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                            data-description="<?php echo htmlspecialchars($product['description']); ?>"
                                                            data-price="<?php echo $product['price']; ?>"
                                                            data-quantity="<?php echo $product['quantity']; ?>"
                                                            data-category="<?php echo $product['category_id']; ?>"
                                                            data-status="<?php echo $product['status']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Preencher formulário para edição
    document.querySelectorAll('.edit-product').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('product_id').value = this.dataset.id;
            document.getElementById('name').value = this.dataset.name;
            document.getElementById('description').value = this.dataset.description;
            document.getElementById('price').value = this.dataset.price;
            document.getElementById('quantity').value = this.dataset.quantity;
            document.getElementById('category_id').value = this.dataset.category;
            document.getElementById('status').value = this.dataset.status;
            
            document.getElementById('cancelEdit').style.display = 'block';
            document.getElementById('name').focus();
        });
    });

    // Cancelar edição
    document.getElementById('cancelEdit').addEventListener('click', function() {
        document.getElementById('productForm').reset();
        document.getElementById('product_id').value = '';
        this.style.display = 'none';
    });

    // Validação de preço em tempo real
    document.getElementById('price').addEventListener('input', function() {
        if (this.value <= 0) {
            this.setCustomValidity('O preço deve ser maior que zero.');
        } else {
            this.setCustomValidity('');
        }
    });

    // Validação de quantidade em tempo real
    document.getElementById('quantity').addEventListener('input', function() {
        if (this.value < 0) {
            this.setCustomValidity('A quantidade não pode ser negativa.');
        } else {
            this.setCustomValidity('');
        }
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>