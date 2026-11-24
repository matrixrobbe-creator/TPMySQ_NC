<?php
session_start();
$_SESSION['role'] = 'site';

require_once 'connexion.php';
$conn = getConnexion('site');

$concerts = $conn->query("SELECT * FROM concerts WHERE places_restantes > 0 ORDER BY date_concert ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MetalStorm - Concerts disponibles</title>
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="topbar">
            <div>
                <span class="role-badge">MySQL : site</span>
                <span class="meta">Lecture seule sur <code>concerts</code></span>
            </div>
            <div class="nav-actions">
                <a href="deconnexion.php" class="btn btn-danger">Déconnexion</a>
            </div>
        </div>

        <div class="card">
            <h1>Concerts disponibles</h1>
            <ul>
                <?php foreach ($concerts as $concert): ?>
                    <li>
                        <div>
                            <strong><?= htmlspecialchars($concert['nom_groupe']) ?></strong>
                            <span class="meta"> — <?= htmlspecialchars($concert['date_concert']) ?></span>
                        </div>
                        <span class="pill"><?= (int)$concert['places_restantes'] ?> places restantes</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <script src="/assets/script.js"></script>
</body>
</html>