<?php
session_start();
$_SESSION['role'] = 'gestion';

require_once 'connexion.php';
$conn = getConnexion('gestion');

$message = '';
$typeMessage = '';

// Traitement du formulaire de réservation en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_concert'], $_POST['nb_places'])) {
    $idConcert = (int)$_POST['id_concert'];
    $nbPlaces = (int)$_POST['nb_places'];
    $idUtilisateur = 1; // Consigne TP : utilisateur fixe avec l'id 1

    if ($nbPlaces <= 0) {
        $message = "Veuillez saisir un nombre de places supérieur à 0.";
        $typeMessage = "error";
    } else {
        try {
            $conn->beginTransaction();

            // 1. Vérifier le nombre de places restantes pour ce concert
            $stmt = $conn->prepare("SELECT nom_groupe, places_restantes FROM concerts WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $idConcert]);
            $concert = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$concert) {
                throw new Exception("Concert introuvable.");
            }

            if ($nbPlaces > (int)$concert['places_restantes']) {
                throw new Exception("Impossible de réserver $nbPlaces place(s) pour {$concert['nom_groupe']} : il n'en reste que {$concert['places_restantes']}.");
            }

            // 2. Mettre à jour le nombre de places restantes dans la table concerts
            $update = $conn->prepare("UPDATE concerts SET places_restantes = places_restantes - :nb WHERE id = :id");
            $update->execute([
                'nb' => $nbPlaces,
                'id' => $idConcert,
            ]);

            // 3. Insérer la nouvelle réservation dans la table reservations (une ligne par place réservée)
            $insert = $conn->prepare("INSERT INTO reservations (date_reservation, id_concert, id_utilisateur) VALUES (CURDATE(), :id_concert, :id_utilisateur)");
            for ($i = 0; $i < $nbPlaces; $i++) {
                $insert->execute([
                    'id_concert' => $idConcert,
                    'id_utilisateur' => $idUtilisateur,
                ]);
            }

            $conn->commit();
            $message = "Réservation validée : $nbPlaces place(s) réservée(s) pour {$concert['nom_groupe']}.";
            $typeMessage = "success";
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $message = $e->getMessage();
            $typeMessage = "error";
        }
    }
}

// Récupération de tous les concerts pour l'affichage du tableau
$concerts = $conn->query("SELECT * FROM concerts ORDER BY date_concert ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MetalStorm - Gestion des Réservations</title>
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="topbar">
            <div>
                <span class="role-badge">MySQL : gestion</span>
                <span class="meta">Gestion des concerts et réservations</span>
            </div>
            <div class="nav-actions">
                <a href="deconnexion.php" class="btn btn-danger">Déconnexion</a>
            </div>
        </div>

        <h1>Gestion des Réservations</h1>

        <?php if ($message !== ''): ?>
            <div class="card alert <?= $typeMessage === 'success' ? 'alert-success' : 'alert-error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Groupe</th>
                        <th>Date</th>
                        <th>Places restantes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($concerts as $concert): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($concert['nom_groupe']) ?></strong></td>
                            <td><?= htmlspecialchars($concert['date_concert']) ?></td>
                            <td><?= (int)$concert['places_restantes'] ?></td>
                            <td>
                                <form method="POST" action="gestionnaire.php" class="inline-form">
                                    <input type="hidden" name="id_concert" value="<?= (int)$concert['id'] ?>">
                                    <input
                                        type="number"
                                        name="nb_places"
                                        min="1"
                                        max="<?= (int)$concert['places_restantes'] ?>"
                                        placeholder="Nombre de places"
                                        <?= (int)$concert['places_restantes'] === 0 ? 'disabled' : 'required' ?>
                                    >
                                    <button type="submit" class="btn btn-primary" <?= (int)$concert['places_restantes'] === 0 ? 'disabled' : '' ?>>
                                        Réserver
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="/assets/script.js"></script>
</body>
</html>