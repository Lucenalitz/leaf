<?php
include '../includes/header.php';
include '../includes/db.php';

// Obter parâmetros de busca e filtro
$search = isset($_GET['search']) ? filter_var($_GET['search'], FILTER_SANITIZE_STRING) : '';
$category_id = isset($_GET['category']) ? filter_var($_GET['category'], FILTER_SANITIZE_NUMBER_INT) : '';

// Construir query base
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.status = 'disponivel'";
$params = [];

// Adicionar filtros
if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category_id)) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
}

$query .= " ORDER BY p.created_at DESC";

// Executar query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obter categorias para o filtro
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-3">
            <div class="filter-section mb-4">
                <h5>Filtrar Produtos</h5>
                <form method="GET" action="products.php">
                    <div class="mb-3">
                        <label for="search" class="form-label">Buscar</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Categoria</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">Todas as categorias</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Aplicar Filtros</button>
                    <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">Limpar Filtros</a>
                </form>
            </div>
        </div>
        
        <div class="col-md-9">
            <h2 class="mb-4">Nossos Produtos</h2>
            
            <?php if (empty($products)): ?>
            <div class="alert alert-info">
                Nenhum produto encontrado com os filtros aplicados.
            </div>
            <?php else: ?>
            
            <div class="row">
                <?php foreach ($products as $product): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card product-card h-100">
                        <img src="../uploads/products/<?php echo $product['image_url'] ?: 'default.jpg'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="card-body">
                            <span class="badge bg-success mb-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text"><?php echo substr($product['description'], 0, 80) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="price">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></span>
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-success btn-sm">Ver Detalhes</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>