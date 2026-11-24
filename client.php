<?php
require_once 'connexion.php';

// Récupération de la connexion avec les droits 'site'
$conn = getConnexion('site');

$concerts = $conn->query("SELECT * FROM concerts WHERE places_restantes > 0")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MetalStorm - Concerts disponibles</title>
</head>
<body>
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