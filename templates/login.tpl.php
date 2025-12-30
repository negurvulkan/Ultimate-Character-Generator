<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container" style="max-width:480px; margin-top:80px;">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-3">Admin Login</h1>
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= \Ucg\Util::esc($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= \Ucg\Util::esc($csrf) ?>">
                <div class="mb-3">
                    <label class="form-label">E-Mail</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Passwort</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button class="btn btn-primary w-100" type="submit">Login</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
