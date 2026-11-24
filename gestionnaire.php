<?php
session_start();

// Seuls le gestionnaire et l'admin peuvent accéder à cette page
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'gestionnaire' && $_SESSION['role'] !== 'admin')) {
    header('Location: connexion.php');
    exit;
}

try {
    $conn = new PDO('mysql:host=localhost;dbname=MetalStorm;charset=utf8', 'gestion', 'Gest@2024');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $concerts = $conn->query("SELECT * FROM concerts")->fetchAll(PDO::FETCH_ASSOC);
    $reservations = $conn->query("SELECT * FROM reservations")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MetalStorm - Espace Gestionnaire</title>
</head>
<body>
    <p>Connecté en tant que : <strong><?= htmlspecialchars($_SESSION['login']) ?></strong> (Rôle : <?= htmlspecialchars($_SESSION['role']) ?>) | <a href="deconnexion.php">Déconnexion</a></p>
    <h1>Gestion des Concerts et Réservations</h1>

    <h2>Liste des concerts</h2>
    <ul>
        <?php foreach ($concerts as $concert): ?>
            <li>
                #<?= (int)$concert['id'] ?> : <strong><?= htmlspecialchars($concert['nom_groupe']) ?></strong> 
                (<?= htmlspecialchars($concert['lieu']) ?> - <?= htmlspecialchars($concert['date_concert']) ?>) 
                — Places : <?= (int)$concert['places_restantes'] ?> / <?= (int)$concert['places_total'] ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>Liste des réservations</h2>
    <ul>
        <?php foreach ($reservations as $res): ?>
            <li>
                Réservation #<?= (int)$res['id'] ?> — Concert ID : <?= (int)$res['id_concert'] ?> — Utilisateur ID : <?= (int)$res['id_utilisateur'] ?> — Date : <?= htmlspecialchars($res['date_reservation']) ?>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>