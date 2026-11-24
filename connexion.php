<?php
session_start();
$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $mdp =$_POST['mot_de_passe'] ?? '';

    try {
        // Connexion avec un compte autorisé à lire la table 'utilisateurs' pour vérifier l'identifiant
        $conn = new PDO('mysql:host=localhost;dbname=MetalStorm;charset=utf8', 'admin', 'Admin@1234');$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Requête préparée (protection contre les injections SQL)
        $stmt =$conn->prepare("SELECT id, login, mot_de_passe, role FROM utilisateurs WHERE login = :login");
        $stmt->execute(['login' =>$login]);
        $user =$stmt->fetch(PDO::FETCH_ASSOC);

        // Vérification du mot de passe (compatible en clair pour ton TP ou haché avec password_hash)
        if ($user && ($mdp ===$user['mot_de_passe'] || password_verify($mdp,$user['mot_de_passe']))) {
            $_SESSION['id'] =$user['id'];
            $_SESSION['login'] =$user['login'];
            $_SESSION['role'] =$user['role'];

            // Redirection selon le rôle
            if ($user['role'] === 'admin') { header('Location: admin.php'); exit; } elseif ($user['role'] === 'gestionnaire') {
                header('Location: gestionnaire.php');
                exit;
            } else {
                header('Location: client.php');
                exit;
            }
        } else {
            $erreur = "Identifiant ou mot de passe incorrect.";
        }
    } catch (PDOException $e) {$erreur = "Erreur de base de données : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>MetalStorm - Connexion</title>
</head>
<body>
    <h1>Connexion à MetalStorm</h1>

    <?php if ($erreur): ?>
        <p style="color: red;"><?= htmlspecialchars($erreur) ?></p>
    <?php endif; ?>

    <form method="POST" action="connexion.php">
        <label>Identifiant :</label><br>
        <input type="text" name="login" required><br><br>

        <label>Mot de passe :</label><br>
        <input type="password" name="mot_de_passe" required><br><br>

        <button type="submit">Se connecter</button>
    </form>
</body>
</html>