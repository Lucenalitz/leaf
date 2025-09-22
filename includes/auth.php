<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['employee_id']);
}

function loginUser($user_id, $name) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $name;
}

function loginEmployee($employee_id, $name, $role) {
    $_SESSION['employee_id'] = $employee_id;
    $_SESSION['employee_name'] = $name;
    $_SESSION['employee_role'] = $role;
}

function logout() {
    session_destroy();
    header('Location: ../index.php');
    exit;
}

function hasAccess($required_role) {
    if (!isset($_SESSION['employee_role'])) {
        return false;
    }
    $roles = ['funcionario' => 1, 'estoquista' => 2, 'gerente' => 3];
    return $roles[$_SESSION['employee_role']] >= $roles[$required_role];
}
?>