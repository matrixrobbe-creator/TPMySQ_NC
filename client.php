<?php
session_start();

// Vérification que l'utilisateur est bien connecté
if (!isset($_SESSION['role'])) {
    header('Location: connexion.php');
    exit;
}

try {
    $conn = new PDO('mysql:host=localhost;dbname=MetalStorm;charset=utf8', 'site', 'Site#Public');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $concerts = $conn->query("SELECT * FROM concerts WHERE places_restantes > 0")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MetalStorm - Espace Client</title>
</head>
<body>
    <p>Connecté en tant que : <strong><?= htmlspecialchars($_SESSION['login']) ?></strong> (Rôle : <?= htmlspecialchars($_SESSION['role']) ?>) | <a href="deconnexion.php">Déconnexion</a></p>
    <h1>Concerts disponibles</h1>

    <ul>
        <?php foreach ($concerts as $concert): ?>
            <li>
                <?= htmlspecialchars($concert['nom_groupe']) ?> - 
                <?= htmlspecialchars($concert['date_concert']) ?> - 
                <?= (int)$concert['places_restantes'] ?> places restantes
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>