<?php
require_once 'connexion.php';

// Récupération de la connexion avec les droits 'admin'
$conn = getConnexion('admin');

$concerts = $conn->query("SELECT * FROM concerts")->fetchAll(PDO::FETCH_ASSOC);
$reservations = $conn->query("SELECT * FROM reservations")->fetchAll(PDO::FETCH_ASSOC);
$utilisateurs = $conn->query("SELECT id, login, role FROM utilisateurs")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MetalStorm - Administration</title>
</head>
<body>
    <h1>Administration MetalStorm</h1>

    <h2>Concerts</h2>
    <ul>
        <?php foreach ($concerts as $concert): ?>
            <li>
                #<?= (int)$concert['id'] ?> : 
                <strong><?= htmlspecialchars($concert['nom_groupe']) ?></strong> 
                (<?= htmlspecialchars($concert['lieu']) ?> - <?= htmlspecialchars($concert['date_concert']) ?>) 
                — Places : <?= (int)$concert['places_restantes'] ?> / <?= (int)$concert['places_total'] ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>Réservations</h2>
    <ul>
        <?php foreach ($reservations as $res): ?>
            <li>
                Réservation #<?= (int)$res['id'] ?> — 
                Concert ID : <?= (int)$res['id_concert'] ?> — 
                Utilisateur ID : <?= (int)$res['id_utilisateur'] ?> — 
                Date : <?= htmlspecialchars($res['date_reservation']) ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>Utilisateurs</h2>
    <ul>
        <?php foreach ($utilisateurs as $user): ?>
            <li>
                #<?= (int)$user['id'] ?> : 
                <strong><?= htmlspecialchars($user['login']) ?></strong> 
                (Rôle : <?= htmlspecialchars($user['role']) ?>)
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>