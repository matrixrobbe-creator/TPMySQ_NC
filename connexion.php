<?php
function getConnexion(string $role): PDO
{
    $host = 'localhost';
    $dbname = 'MetalStorm';

    // Sélection des identifiants MySQL selon le rôle
    switch ($role) {
        case 'admin':
            $user = 'admin';
            $pass = 'Admin@1234';
            break;
        case 'gestion':
            $user = 'gestion';
            $pass = 'Gest@2024';
            break;
        case 'site':
        default:
            $user = 'site';
            $pass = 'Site#Public';
            break;
    }

    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données ($role) : " . $e->getMessage());
    }
}