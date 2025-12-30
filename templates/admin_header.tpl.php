<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= \Ucg\Util::esc($title ?? 'Admin') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Smarty CDN placeholder to align with requirement -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Smarty/3.1.33/smarty.min.js"></script>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="/admin/dashboard.php">UCG Admin</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="/admin/species.php">Taxonomie</a></li>
        <li class="nav-item"><a class="nav-link" href="/admin/life_stages.php">Lebensphasen</a></li>
        <li class="nav-item"><a class="nav-link" href="/admin/computed_keys.php">Computed Keys</a></li>
        <li class="nav-item"><a class="nav-link" href="/admin/rules.php">Regeln</a></li>
        <li class="nav-item"><a class="nav-link" href="/admin/base_profiles.php">Basisprofile</a></li>
        <li class="nav-item"><a class="nav-link" href="/admin/preview.php">Preview</a></li>
      </ul>
      <span class="navbar-text text-white"><?= \Ucg\Util::esc($user['email'] ?? '') ?></span>
      <a class="btn btn-outline-light ms-3" href="/admin/logout.php">Logout</a>
    </div>
  </div>
</nav>
<div class="container">
