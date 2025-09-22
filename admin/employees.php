<?php
include '../includes/auth.php';
include '../includes/db.php';

// Acesso restrito a gerentes
if (!isAdminLoggedIn() || !hasAccess('gerente')) {
    header('Location: login.php');
    exit;
}

// Processar formulário de adição/edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $role = filter_var($_POST['role'], FILTER_SANITIZE_STRING);
    
    // Verificar se é edição ou criação
    if (isset($_POST['employee_id'])) {
        $employee_id = filter_var($_POST['employee_id'], FILTER_SANITIZE_NUMBER_INT);
        
        // Verificar se a senha foi fornecida
        if (!empty($_POST['password'])) {
            $password = $_POST['password'];
            if (strlen($password) < 6) {
                $error = "A senha deve ter pelo menos 6 caracteres.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE employees SET name = ?, email = ?, role = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $email, $role, $hashed_password, $employee_id]);
                $success = "Funcionário atualizado com sucesso!";
            }
        } else {
            // Atualizar sem alterar a senha
            $stmt = $pdo->prepare("UPDATE employees SET name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $employee_id]);
            $success = "Funcionário atualizado com sucesso!";
        }
    } else {
        // Criar novo funcionário
        $password = $_POST['password'];
        if (strlen($password) < 6) {
            $error = "A senha deve ter pelo menos 6 caracteres.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Verificar se o email já existe
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = "Este email já está cadastrado.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO employees (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashed_password, $role]);
                $success = "Funcionário adicionado com sucesso!";
            }
        }
    }
}

// Processar exclusão
if (isset($_GET['delete'])) {
    $employee_id = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
    
    // Não permitir exclusão do próprio usuário
    if ($employee_id == $_SESSION['employee_id']) {
        $error = "Você não pode excluir sua própria conta.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$employee_id]);
        $success = "Funcionário excluído com sucesso!";
    }
}

// Buscar funcionários
$stmt = $pdo->prepare("SELECT * FROM employees ORDER BY name");
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaf Admin - Funcionários</title>
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
                <a href="employees.php" class="nav-link active">
                    <i class="fas fa-users me-2"></i>Funcionários
                </a>
                <?php endif; ?>
                <?php if (hasAccess('estoquista')): ?>
                <a href="products.php" class="nav-link">
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
                <h2 class="mb-4">Gerenciamento de Funcionários</h2>

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
                                <h5 class="mb-0" id="formTitle">Adicionar Funcionário</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="employeeForm">
                                    <input type="hidden" name="employee_id" id="employee_id">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nome completo</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="role" class="form-label">Cargo</label>
                                        <select class="form-select" id="role" name="role" required>
                                            <option value="funcionario">Funcionário</option>
                                            <option value="estoquista">Estoquista</option>
                                            <option value="gerente">Gerente</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Senha</label>
                                        <input type="password" class="form-control" id="password" name="password">
                                        <div class="password-strength mt-2">
                                            <div class="progress">
                                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Deixe em branco para manter a senha atual (em edição). Mínimo 6 caracteres.</small>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100">Salvar Funcionário</button>
                                    <button type="button" id="cancelEdit" class="btn btn-outline-secondary w-100 mt-2" style="display: none;">Cancelar Edição</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Lista de Funcionários</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Nome</th>
                                                <th>Email</th>
                                                <th>Cargo</th>
                                                <th>Data de Cadastro</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($employees as $employee): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                                <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                                <td><span class="badge bg-info text-capitalize"><?php echo $employee['role']; ?></span></td>
                                                <td><?php echo date('d/m/Y', strtotime($employee['created_at'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary edit-employee" 
                                                            data-id="<?php echo $employee['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($employee['name']); ?>"
                                                            data-email="<?php echo htmlspecialchars($employee['email']); ?>"
                                                            data-role="<?php echo $employee['role']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($employee['id'] != $_SESSION['employee_id']): ?>
                                                    <a href="?delete=<?php echo $employee['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja excluir este funcionário?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <?php endif; ?>
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
    document.querySelectorAll('.edit-employee').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('employee_id').value = this.dataset.id;
            document.getElementById('name').value = this.dataset.name;
            document.getElementById('email').value = this.dataset.email;
            document.getElementById('role').value = this.dataset.role;
            
            document.getElementById('formTitle').textContent = 'Editar Funcionário';
            document.getElementById('cancelEdit').style.display = 'block';
            document.getElementById('password').required = false;
            document.getElementById('name').focus();
        });
    });

    // Cancelar edição
    document.getElementById('cancelEdit').addEventListener('click', function() {
        document.getElementById('employeeForm').reset();
        document.getElementById('employee_id').value = '';
        document.getElementById('formTitle').textContent = 'Adicionar Funcionário';
        document.getElementById('password').required = true;
        this.style.display = 'none';
    });

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
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>