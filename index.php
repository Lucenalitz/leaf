<?php
include 'includes/header.php';
include 'includes/db.php';

// Buscar produtos em destaque
$stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'disponivel' ORDER BY created_at DESC LIMIT 6");
$stmt->execute();
$featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="hero-section">
    <div class="container">
        <h1 class="display-4 fw-bold">Bem-vindo ao Leaf Market</h1>
        <p class="lead">Seu mercado orgânico com os melhores produtos naturais e saudáveis</p>
        <a href="pages/products.php" class="btn btn-success btn-lg mt-3">Ver Produtos</a>
    </div>
</div>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto text-center">
            <h2 class="section-title">Por que escolher o Leaf?</h2>
            <p class="lead">Nossos produtos são cuidadosamente selecionados para oferecer o melhor em qualidade e sabor, sempre com respeito ao meio ambiente e aos produtores locais.</p>
        </div>
    </div>

    <div class="row mt-5">
        <div class="col-md-4 mb-4">
            <div class="text-center">
                <i class="fas fa-leaf fa-3x text-success mb-3"></i>
                <h4>100% Orgânico</h4>
                <p>Todos os produtos são cultivados sem agrotóxicos ou produtos químicos.</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="text-center">
                <i class="fas fa-truck fa-3x text-success mb-3"></i>
                <h4>Entrega Rápida</h4>
                <p>Entregamos em até 24h na região metropolitana.</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="text-center">
                <i class="fas fa-heart fa-3x text-success mb-3"></i>
                <h4>Saudável e Saboroso</h4>
                <p>Produtos frescos que fazem bem para sua saúde e para o planeta.</p>
            </div>
        </div>
    </div>
</div>

<div class="bg-light py-5">
    <div class="container">
        <h2 class="section-title">Produtos em Destaque</h2>
        <div class="row">
            <?php foreach ($featured_products as $product): ?>
            <div class="col-md-4 mb-4">
                <div class="card product-card h-100">
                    <img src="uploads/products/<?php echo $product['image_url'] ?: 'default.jpg'; ?>" class="card-img-top" alt="<?php echo $product['name']; ?>">
                    <div class="card-body">
                        <span class="badge bg-success mb-2">Orgânico</span>
                        <h5 class="card-title"><?php echo $product['name']; ?></h5>
                        <p class="card-text"><?php echo substr($product['description'], 0, 80) . '...'; ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="price">R$ <?php echo number_format($product['price'], 2, ',', '.'); ?></span>
                            <a href="pages/product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-success btn-sm">Ver Detalhes</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="pages/products.php" class="btn btn-success">Ver Todos os Produtos</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>