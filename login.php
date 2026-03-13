<?php
require_once __DIR__ . '/includes/functions.php';

session_start();
if (!empty($_SESSION['user'])) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = db()->prepare('SELECT * FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($senha, $user['password_hash'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'email' => $user['email'],
            'nivel' => $user['nivel'],
        ];
        redirect('index.php');
    }

    $error = 'Credenciais inválidas.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - <?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="mb-3">Entrar no sistema</h4>
          <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
          <form method="post">
            <div class="mb-3"><label class="form-label">E-mail</label><input name="email" type="email" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Senha</label><input name="senha" type="password" class="form-control" required></div>
            <button class="btn btn-primary w-100">Entrar</button>
          </form>
          <small class="text-muted d-block mt-2">admin@sistema.local / 123456</small>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
