<?php
session_start();

// Réservé strictement au rôle admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: connexion.php');
    exit;
}

try {
    $conn = new PDO('mysql:host=localhost;dbname=MetalStorm;charset=utf8', 'admin', 'Admin@1234');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $concerts = $conn->query("SELECT * FROM concerts")->fetchAll(PDO::FETCH_ASSOC);
    $reservations = $conn->query("SELECT * FROM reservations")->fetchAll(PDO::FETCH_ASSOC);
    $utilisateurs = $conn->query("SELECT id, login, role FROM utilisateurs")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MetalStorm - Administration</title>
</head>
<body>
    <p>Connecté en tant que : <strong><?= htmlspecialchars($_SESSION['login']) ?></strong> (Rôle : <?= htmlspecialchars($_SESSION['role']) ?>) | <a href="deconnexion.php">Déconnexion</a></p>
    <h1>Administration MetalStorm</h1>

    <h2>Concerts</h2>
    <ul>
        <?php foreach ($concerts as $concert): ?>
            <li>#<?= (int)$concert['id'] ?> : <strong><?= htmlspecialchars($concert['nom_groupe']) ?></strong> — <?= htmlspecialchars($concert['date_concert']) ?> (<?= (int)$concert['places_restantes'] ?> places restantes)</li>
        <?php endforeach; ?>
    </ul>

    <h2>Réservations</h2>
    <ul>
        <?php foreach ($reservations as $res): ?>
            <li>Réservation #<?= (int)$res['id'] ?> — Concert : <?= (int)$res['id_concert'] ?> — Utilisateur : <?= (int)$res['id_utilisateur'] ?> (<?= htmlspecialchars($res['date_reservation']) ?>)</li>
        <?php endforeach; ?>
    </ul>

    <h2>Utilisateurs</h2>
    <ul>
        <?php foreach ($utilisateurs as $user): ?>
            <li>#<?= (int)$user['id'] ?> : <strong><?= htmlspecialchars($user['login']) ?></strong> (Rôle : <?= htmlspecialchars($user['role']) ?>)</li>
        <?php endforeach; ?>
    </ul>
</body>
</html>