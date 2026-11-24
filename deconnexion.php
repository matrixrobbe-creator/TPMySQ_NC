<?php
session_start();
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}

session_destroy();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MetalStorm - Déconnexion</title>
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="card center-box">
            <span class="role-badge">Session fermée</span>
            <h1>Vous êtes déconnecté</h1>
            <p class="meta">Choisissez un profil MySQL pour tester les permissions de <strong>MetalStorm</strong> :</p>

            <div class="role-grid">
                <a href="client.php" class="btn btn-primary">Accès Public (Rôle : site)</a>
                <a href="gestionnaire.php" class="btn">Accès Gestionnaire (Rôle : gestion)</a>
                <a href="admin.php" class="btn">Accès Administrateur (Rôle : admin)</a>
            </div>
        </div>
    </div>

    <script src="/assets/script.js"></script>
</body>
</html>