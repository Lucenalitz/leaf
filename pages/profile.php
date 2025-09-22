<?php
include '../includes/header.php';
include '../includes/db.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Buscar informações do usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Processar atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    
    // Verificar se o email já existe (exceto para o usuário atual)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user_id]);
    
    if ($stmt->fetch()) {
        $error = "Este email já está cadastrado.";
    } else {
        // Atualizar informações do usuário
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, address = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $email, $address, $phone, $user_id]);
        $_SESSION['user_name'] = $name;
        $success = "Perfil atualizado com sucesso!";
        
        // Atualizar dados do usuário na sessão
        $user['name'] = $name;
        $user['email'] = $email;
        $user['address'] = $address;
        $user['phone'] = $phone;
    }
}

// Processar alteração de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verificar senha atual
    if (!password_verify($current_password, $user['password'])) {
        $password_error = "Senha atual incorreta.";
    } else if ($new_password !== $confirm_password) {
        $password_error = "As novas senhas não coincidem.";
    } else if (strlen($new_password) < 6) {
        $password_error = "A nova senha deve ter pelo menos 6 caracteres.";
    } else {
        // Atualizar senha
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);
        $password_success = "Senha alterada com sucesso!";
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="mb-4">Meu Perfil</h2>

            <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Informações Pessoais</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
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
                            <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Telefone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        <button type="submit" class="btn btn-success">Atualizar Perfil</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Alterar Senha</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($password_error)): ?>
                    <div class="alert alert-danger"><?php echo $password_error; ?></div>
                    <?php endif; ?>

                    <?php if (isset($password_success)): ?>
                    <div class="alert alert-success"><?php echo $password_success; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Senha Atual</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="password-strength mt-2">
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                            <small class="form-text text-muted">A senha deve ter pelo menos 6 caracteres.</small>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <div id="passwordMatch" class="mt-2"></div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-success">Alterar Senha</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validação de força da senha em tempo real
document.getElementById('new_password').addEventListener('input', function() {
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
    const password = document.getElementById('new_password').value;
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