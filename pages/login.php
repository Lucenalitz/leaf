<?php
include '../includes/header.php';
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    // Verificar se é login de cliente
    if (isset($_POST['user_login'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            loginUser($user['id'], $user['name']);
            header('Location: ../index.php');
            exit;
        } else {
            $error = "Email ou senha incorretos.";
        }
    } 
    // Verificar se é login de funcionário
    else if (isset($_POST['employee_login'])) {
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE email = ?");
        $stmt->execute([$email]);
        $employee = $stmt->fetch();
        
        if ($employee && password_verify($password, $employee['password'])) {
            loginEmployee($employee['id'], $employee['name'], $employee['role']);
            header('Location: ../admin/');
            exit;
        } else {
            $error = "Email ou senha incorretos.";
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Entrar na sua conta</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Senha</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="user_login" class="btn btn-success">Entrar como Cliente</button>
                            <button type="submit" name="employee_login" class="btn btn-outline-success">Entrar como Funcionário</button>
                        </div>
                    </form>
                    
                    <hr>
                    <div class="text-center">
                        <p>Não tem uma conta? <a href="register.php">Cadastre-se aqui</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>