<?php
session_start();
$_SESSION['role'] = 'admin';

require_once 'connexion.php';
$conn = getConnexion('admin');

$message = '';
$typeMessage = '';

// Traitement du formulaire d'ajout d'un nouveau concert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_concert'])) {
    $nomGroupe = trim($_POST['nom_groupe'] ?? '');
    $lieu = trim($_POST['lieu'] ?? '');
    $dateConcert = $_POST['date_concert'] ?? '';
    $placesTotal = (int)($_POST['places_total'] ?? 0);

    if ($nomGroupe === '' || $lieu === '' || $dateConcert === '' || $placesTotal <= 0) {
        $message = "Tous les champs sont obligatoires et le nombre de places doit être supérieur à 0.";
        $typeMessage = "error";
    } else {
        try {
            $stmt = $conn->prepare("
                INSERT INTO concerts (nom_groupe, lieu, date_concert, places_total, places_restantes)
                VALUES (:nom_groupe, :lieu, :date_concert, :places_total, :places_restantes)
            ");
            $stmt->execute([
                'nom_groupe' => $nomGroupe,
                'lieu' => $lieu,
                'date_concert' => $dateConcert,
                'places_total' => $placesTotal,
                'places_restantes' => $placesTotal, // Au départ, toutes les places sont disponibles
            ]);

            $message = "Le concert de « $nomGroupe » a bien été ajouté.";
            $typeMessage = "success";
        } catch (PDOException $e) {
            $message = "Erreur lors de l'ajout : " . $e->getMessage();
            $typeMessage = "error";
        }
    }
}

// Récupération de TOUS les utilisateurs et des concerts
$utilisateurs = $conn->query("SELECT id, login, role FROM utilisateurs ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$concerts = $conn->query("SELECT * FROM concerts ORDER BY date_concert ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MetalStorm - Administration</title>
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="topbar">
            <div>
                <span class="role-badge">MySQL : admin</span>
                <span class="meta">Administration complète de <code>MetalStorm</code></span>
            </div>
            <div class="nav-actions">
                <a href="deconnexion.php" class="btn btn-danger">Déconnexion</a>
            </div>
        </div>

        <h1>Administration MetalStorm</h1>

        <?php if ($message !== ''): ?>
            <div class="card alert <?= $typeMessage === 'success' ? 'alert-success' : 'alert-error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Ajouter un nouveau concert</h2>
            <form method="POST" action="admin.php" class="form-grid">
                <div>
                    <label for="nom_groupe">Nom du groupe</label>
                    <input type="text" id="nom_groupe" name="nom_groupe" placeholder="Ex : Gojira" required>
                </div>
                <div>
                    <label for="lieu">Lieu / Scène</label>
                    <input type="text" id="lieu" name="lieu" placeholder="Ex : Mainstage 1" required>
                </div>
                <div>
                    <label for="date_concert">Date du concert</label>
                    <input type="date" id="date_concert" name="date_concert" required>
                </div>
                <div>
                    <label for="places_total">Nombre total de places</label>
                    <input type="number" id="places_total" name="places_total" min="1" placeholder="Ex : 1500" required>
                </div>
                <div class="form-actions">
                    <button type="submit" name="ajouter_concert" class="btn btn-primary">Ajouter le concert</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Liste de tous les utilisateurs</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Login</th>
                        <th>Rôle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $user): ?>
                        <tr>
                            <td>#<?= (int)$user['id'] ?></td>
                            <td><strong><?= htmlspecialchars($user['login']) ?></strong></td>
                            <td><span class="pill"><?= htmlspecialchars($user['role']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2>Concerts enregistrés</h2>
            <table>
                <thead>
                    <tr>
                        <th>Groupe</th>
                        <th>Lieu</th>
                        <th>Date</th>
                        <th>Places restantes / Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($concerts as $concert): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($concert['nom_groupe']) ?></strong></td>
                            <td><?= htmlspecialchars($concert['lieu']) ?></td>
                            <td><?= htmlspecialchars($concert['date_concert']) ?></td>
                            <td><?= (int)$concert['places_restantes'] ?> / <?= (int)$concert['places_total'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="/assets/script.js"></script>
</body>
</html>