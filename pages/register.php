<?php
include '../includes/header.php';
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    
    // Validações
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Por favor, preencha todos os campos obrigatórios.";
    } else if ($password !== $confirm_password) {
        $error = "As senhas não coincidem.";
    } else if (strlen($password) < 6) {
        $error = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        // Verificar se o email já existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "Este email já está cadastrado.";
        } else {
            // Inserir novo usuário
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, address, phone) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $email, $hashed_password, $address, $phone])) {
                $success = "Conta criada com sucesso! Você já pode fazer login.";
            } else {
                $error = "Erro ao criar conta. Tente novamente.";
            }
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Criar uma conta</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php else: ?>
                    
                    <form method="POST" id="registerForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nome completo *</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Senha *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="password-strength mt-2">
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                                <small class="form-text text-muted">A senha deve ter pelo menos 6 caracteres.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirmar senha *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <div id="passwordMatch" class="mt-2"></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Endereço</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">Criar conta</button>
                        </div>
                    </form>
                    
                    <?php endif; ?>
                    
                    <hr>
                    <div class="text-center">
                        <p>Já tem uma conta? <a href="login.php">Faça login aqui</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validação de força da senha em tempo real
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const progressBar = document.querySelector('.progress-bar');
    let strength = 0;
    
    if (password.length >= 6) strength += 25;
    if (password.match(/[a-z]+/)) strength += 25;
    if (password.match(/[A-Z]+/)) strength += 25;
    if (password.match(/[0-9]+/)) strength += 25;
    
    progressBar.style.width = strength + '%';
    
    if (strength < 50) {
        progressBar.className = 'progress-bar bg-danger';
    } else if (strength < 75) {
        progressBar.className = 'progress-bar bg-warning';
    } else {
        progressBar.className = 'progress-bar bg-success';
    }
});

// Validação de confirmação de senha em tempo real
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    const matchDiv = document.getElementById('passwordMatch');
    
    if (confirmPassword === '') {
        matchDiv.innerHTML = '';
    } else if (password === confirmPassword) {
        matchDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle"></i> As senhas coincidem</span>';
    } else {
        matchDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> As senhas não coincidem</span>';
    }
});
</script>

<?php include '../includes/footer.php'; ?>