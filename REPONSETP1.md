# Réponse à la partie d'auto évaluation de la série de 3 TP en format SQL !

## TP1 : Création de la base de donnée

* Dans le TP 1 il n'y a pas de question auxquels ils faut répondre.

## TP2 : Autorisations et mise en place

### Mission 1 :

#### 1. Sur les clés primaires :

* **Risque de doublons et impossibilité d'insertion (Unicité) :** Une clé primaire doit identifier chaque enregistrement de manière unique. Si `nom_groupe` était la clé primaire, il serait impossible d'enregistrer deux concerts d'un même groupe (par exemple sur deux dates différentes) ou deux groupes portant le même nom (homonymes, groupe original et *cover band*). Le SGBD rejetterait la seconde insertion avec une erreur de contrainte d'unicité (`Duplicate entry`).
* **Garantie de stabilité et d'unicité :** Une bonne clé primaire doit être **immuable** (ne jamais changer dans le temps) et **unique**. Un nom de groupe peut être modifié ou mal orthographié lors de la saisie : le corriger obligerait à modifier en cascade toutes les clés étrangères associées dans la table `reservations`, ce qui est risqué et coûteux. Un identifiant technique (`id`) reste fixe même si les informations du concert changent.
* **Performance d'un entier auto-incrémenté (`INT AUTO_INCREMENT`) :** Un entier occupe beaucoup moins d'espace mémoire (4 octets) qu'une chaîne de caractères (`VARCHAR`). Les indexation et les jointures (`JOIN` entre `concerts` et `reservations`) sont nettement plus rapides sur des nombres entiers que sur du texte, et l'auto-incrémentation délègue au SGBD la garantie qu'aucun doublon ne sera jamais généré.

#### 2. Sur les clés étrangères :

* **Rôle et impact de leur suppression :** Les clés étrangères `id_utilisateur` et `id_concert` permettent de relier chaque réservation à un compte client et à un événement précis (matérialisant la relation entre les deux tables). Si l'on supprimait ces colonnes, il serait impossible de savoir qui a réservé quoi. Si l'on supprimait uniquement les **contraintes** `FOREIGN KEY`, la base accepterait des données incohérentes (réservations pointant vers des ID inexistants).
* **Garantie de l'intégrité référentielle par MySQL :** Grâce aux contraintes `FOREIGN KEY` (avec le moteur InnoDB), MySQL vérifie systématiquement avant toute insertion ou modification (`INSERT` / `UPDATE`) que les valeurs de `id_utilisateur` et `id_concert` existent bien dans les tables parentes `utilisateurs` et `concerts`. Dans le cas contraire, la requête est rejetée.
* **Gestion de la suppression d'un concert :** Si un concert est supprimé, les réservations associées ne doivent pas devenir orphelines. On gère cela via les règles d'action de la clé étrangère :
  * `ON DELETE RESTRICT` (par défaut) : MySQL bloque la suppression du concert tant que des réservations y sont rattachées (idéal pour éviter d'annuler des billets par erreur).
  * `ON DELETE CASCADE` : MySQL supprime automatiquement toutes les réservations liées à ce concert (utile si l'événement est définitivement annulé et purgé).
* **Problème d'une réservation non liée (orpheline) :** Une réservation sans utilisateur ou sans concert (valeur `NULL` ou ID inexistant) n'a aucun sens métier : un billet appartient forcément à quelqu'un pour un événement donné. Techniquement, cela fausserait le décompte des `places_restantes` et provoquerait des erreurs lors des jointures (`INNER JOIN`) sur l'application. C'est pourquoi ces deux colonnes doivent être déclarées en `NOT NULL` avec des contraintes de clés étrangères strictes.

#### 3. Sur les relations entre tables :

* **Représentation en BDD :** Sur le MCD, la relation a des cardinalités `0,n` côté `UTILISATEURS` et `1,1` côté `RESERVATIONS`. En SQL, cela se traduit par la migration de la clé primaire `id` de `utilisateurs` vers `reservations` sous forme de clé étrangère (`id_utilisateur`) .
* **Placement de la clé étrangère :** La clé étrangère va toujours du côté `1,1` (`reservations`). L'inverse obligerait à dupliquer la ligne de l'utilisateur à chaque nouvelle réservation, car un champ ne peut stocker qu'un seul identifiant.
* **Suppression automatique d'un compte :** Oui, pour éviter les réservations orphelines et respecter le RGPD. On l'implémente directement avec l'option `ON DELETE CASCADE` sur la contrainte `FOREIGN KEY (id_utilisateur)`.
* **Modélisation plusieurs-à-plusieurs (`N,N`) :** On crée une **table de liaison** intermédiaire (ex. `programmation`) contenant les deux clés étrangères (`id_concert` et `id_artiste`), qui forment ensemble une clé primaire composite.

#### 4. Sur la normalisation :

* **Création d'une nouvelle table** : Oui, il faut créer une nouvelle table `lieux` (liée par une clé étrangère `id_lieu`) si l'on veut y ajouter des informations (adresse, capacité) afin d'éviter la redondance des données.
* **Avantages / inconvénients** : Stocker le lieu dans `concerts` offre une **simplicité** des requêtes (pas de jointure `JOIN` nécessaire), mais rend la table lourde et oblige à répéter les mêmes infos à chaque concert dans la même salle.
* **Normalisation des données** : Elle consiste à découper les données en tables logiques pour **prévenir les anomalies** (insertion, modification, suppression), *réduire la redondance* et garantir l'intégrité des données.
* **La redondance** : C'est le fait de dupliquer une même information à plusieurs endroits. Elle augmente le volume de stockage et crée des **risques d'incohérence** (si l'adresse d'une salle change, il faut la modifier sur des dizaines de lignes au risque d'en oublier).

#### 5. Sur l'intégrité des données :

* **Risques et prévention :** Une modification manuelle fausse la cohérence entre le stock réel et les billets vendus (risque de surréservation ou de places bloquées). On prévient cela en retirant le droit `UPDATE` sur cette colonne au gestionnaire et en automatisant le calcul.
* **Triggers et transactions :** Un **trigger** (`AFTER INSERT` sur `reservations`) met à jour automatiquement `places_restantes` à chaque réservation. Les **transactions** (`START TRANSACTION` / `COMMIT` / `ROLLBACK`) sécurisent l'enchaînement des requêtes en cas d'erreur.
* **Importance de l'atomicité :** L'ajout de la réservation et la baisse du stock doivent être **atomiques** (principe du *tout ou rien*) : si l'une des deux opérations échoue (panne, coupure), la transaction annule tout (`ROLLBACK`) pour éviter un décalage de stock.
* **Interdire les valeurs négatives :** On ajoute une contrainte **`CHECK (places_restantes >= 0)`** (ou le type `INT UNSIGNED`) sur la colonne `places_restantes` afin que MySQL bloque toute réservation dès que le stock atteint 0.

### Mission 2 : Création des utilisateurs MySQL

#### 1. Sur le principe de moindre privilège :

* **Pourquoi retirer `DELETE` :** Selon le **principe de moindre privilège**, un utilisateur ne doit disposer que des droits strictement nécessaires à ses missions (consulter, ajouter et modifier via `SELECT`, `INSERT`, `UPDATE`). Il n'a pas vocation à effacer définitivement l'historique[cite: 30].
* **Risques encourus :** Accorder `DELETE` expose la base à des **suppressions accidentelles** (ex. un `DELETE` sans clause `WHERE`), à la perte irréversible de réservations clients (preuves d'achat/facturation), ou à un sabotage massif si le compte gestionnaire est compromis[cite: 30].
* **Conséquences d'un `DELETE FROM concerts` pour le festival :** La programmation disparaît totalement (site et billetterie inutilisables), toutes les réservations associées risquent d'être supprimées en cascade (`ON DELETE CASCADE`), et il devient impossible de contrôler les billets à l'entrée sans restaurer une sauvegarde[cite: 29].
* **Ne pas activer de `DELETE` brut (alternatives) :** Utiliser la **suppression logique (*Soft Delete*)** en passant le statut à « annulée » via un simple `UPDATE`, ou encadrer la suppression par une **procédure stockée** qui n'autorise que la suppression d'un doublon précis (`WHERE id = ...`) sur une commande non encore payée[cite: 29, 30].
* **Cas précis où `DELETE` est justifié :** On pourrait autoriser cette suppression uniquement pour effacer un brouillon de concert (sans aucune réservation associée) ou une réservation en double non payée[cite: 29, 30].
* **Implémentation sécurisée et conservation des données :** Mettre en place un **journal d'audit (logs)** et un déclencheur (**`TRIGGER BEFORE DELETE`**) qui sauvegarde automatiquement la ligne supprimée dans une table d'archives (conservée 30 jours par exemple) avec l'identité de l'utilisateur et la date de l'action[cite: 30].

#### 2. Sur la granularité des permissions :

* **Pourquoi restreindre l'accès du rôle `Site` à la table `concerts` :** Le rôle `Site` représente la vitrine publique du festival accessible à tous les internautes. Sa seule mission est d'afficher la programmation musicale (`SELECT` sur `concerts`). Il n'a aucun besoin métier d'accéder aux données sensibles des festivaliers (`reservations`) ni aux comptes d'administration (`utilisateurs`).
* **Importance cruciale pour la sécurité :** L'interface web publique est la surface la plus exposée aux attaques. En cas d'exploitation d'une faille (comme une injection SQL) ou de compromission de la chaîne de connexion utilisée par le site, le SGBD bloque physiquement tout accès aux tables sensibles. L'attaquant ne peut alors consulter que des informations déjà publiques.
* **Conséquences d'un accès en lecture à la table `utilisateurs` :** Si le site public pouvait lire `utilisateurs`, une injection SQL permettrait d'exfiltrer l'ensemble des identifiants et des mots de passe hachés. Un pirate pourrait ensuite les casser hors-ligne par force brute/dictionnaire, récupérer les accès des comptes privilégiés (administrateurs, gestionnaires) et prendre le contrôle total du back-office.
* **Protection de la vie privée des festivaliers :** La table `reservations` contient des données personnelles soumises au RGPD (noms, prénoms, adresses e-mail, historique d'achats, présence à un événement). Interdire au compte `Site` d'y accéder empêche toute fuite de données nominatives, limite les risques de phishing ciblé et évite le vol de numéros de billets.
* **Afficher les places restantes sans exposer `reservations` :**
  * **Créer une vue SQL (`VIEW`) :** Mettre en place une vue (ex. `vue_places_restantes`) qui calcule uniquement la différence entre la capacité du concert et le nombre de réservations (`COUNT(*)`), puis accorder au rôle `Site` uniquement le droit `SELECT` sur cette vue.
  * **Utiliser une colonne dédiée mise à jour par `TRIGGER` :** Ajouter une colonne `places_restantes` dans la table `concerts` et créer des déclencheurs (`AFTER INSERT`, `AFTER UPDATE`, `AFTER DELETE`) sur `reservations` pour mettre à jour ce compteur automatiquement.
  * **Passer par une procédure stockée :** Encapsuler le calcul dans une procédure (ex. `sp_places_restantes(id_concert)`) qui ne retourne qu'un entier, en n'accordant que le droit `EXECUTE` au rôle `Site`.

  #### 3. Sur la sécurité des mots de passe :

* **Conformité des mots de passe actuels (`Admin@1234`, `Gest@2024`, `Site#Public`) :** **Non**, ils ne respectent pas les bonnes pratiques de sécurité (recommandations ANSSI / CNIL). Bien qu'ils contiennent des majuscules, minuscules et caractères spéciaux (`@`, `#`), ils sont **trop courts** (10 à 11 caractères), **hautement prévisibles** (ils reprennent le nom même du rôle ou de l'utilisateur : `Admin`, `Gest`, `Site`), et utilisent des suites triviales (`1234`, année en cours `2024`) ou des mots du dictionnaire (`Public`). De plus, `Site#Public` ne contient aucun chiffre.
* **Critères d'un mot de passe MySQL robuste et amélioration :**
  * **Critères (ANSSI) :** Au moins **12 à 16 caractères**, une forte entropie (aléatoire, sans mot du dictionnaire ni lien avec le nom du compte ou du projet), et un mélange des **4 catégories de caractères** (majuscules, minuscules, chiffres, caractères spéciaux).
  * **Amélioration concrète :** Remplacer ces mots de passe prévisibles par des chaînes générées aléatoirement via un gestionnaire de mots de passe ou un générateur cryptographique (ex. : `v9$Kp#7mQ!2xL@8wR4` au lieu de `Admin@1234`).
* **Danger des mots de passe simples (`password`, `1234`) :** Ils sont cassés en quelques millisecondes par des **attaques par dictionnaire**, par **force brute** ou via des **tables arc-en-ciel (*rainbow tables*)**. Si un port MySQL est exposé ou si un attaquant récupère les hashs de la table système `mysql.user`, il prend immédiatement le contrôle total de la base de données (lecture, vol, chiffrement/rançongiciel ou suppression des données).
* **Stockage sécurisé en environnement de production :**
  * **Variables d'environnement / fichier `.env` :** Stocker les identifiants dans des variables d'environnement du système ou un fichier `.env` placé hors du répertoire web public et explicitement exclu du dépôt Git (`.gitignore`).
  * **Permissions système strictes :** Restreindre les droits de lecture du fichier de configuration uniquement à l'utilisateur système qui exécute l'application (ex. `chmod 600` sous Linux).
  * **Gestionnaire de secrets (*Secrets Manager*) :** En production avancée, utiliser un coffre-fort dédié (HashiCorp Vault, AWS Secrets Manager, Azure Key Vault) ou le fichier chiffré natif de MySQL (`mysql_config_editor`) plutôt que des identifiants en clair dans les scripts SQL ou le code source.

#### 4. Sur les implications des permissions `ALL PRIVILEGES` :

* **Dangers du `ALL PRIVILEGES` (même pour un administrateur) :** Même un administrateur n'est pas à l'abri d'une erreur humaine (ex. : exécuter un `DROP TABLE` ou `TRUNCATE` sur la base de production `metalstorm` au lieu d'un environnement de test). De plus, si le compte `admin` est compromis, l'attaquant hérite instantanément des pleins pouvoirs sans aucune barrière de sécurité.
* **Actions malveillantes en cas de compromission du compte `admin` :** Un pirate ayant accès à ce compte peut exfiltrer l'intégralité des données sensibles (utilisateurs, réservations) pour les revendre, chiffrer ou détruire définitivement la base (`DROP DATABASE`), altérer les données (prix, billets) ou encore révoquer les accès des administrateurs légitimes (`REVOKE` / `DROP USER`) pour s'approprier le serveur.
* **Limiter les risques tout en permettant le travail d'administration :**
  * **Remplacer `ALL PRIVILEGES` par des `GRANT` explicites :** Accorder uniquement les droits réellement utiles au quotidien (ex. : `SELECT`, `INSERT`, `UPDATE`, `DELETE`, `CREATE`, `ALTER`, `INDEX`, `EXECUTE`) et retirer les privilèges les plus destructeurs ou critiques comme `DROP` ou `GRANT OPTION`.
  * **Séparer les comptes selon l'usage :** Utiliser un compte de consultation/maintenance courante avec des droits limités au quotidien, et ne se connecter au compte super-administrateur que ponctuellement lors des opérations de structure (migrations, mises à jour).
* **Gestion des comptes administrateurs en contexte réel (production) :**
  * **Restriction réseau (par IP / hôte) :** Créer le compte avec une adresse précise (ex. `'admin'@'192.168.1.50'` ou `'admin'@'localhost'` via tunnel SSH/VPN) plutôt que `'admin'@'%'`, afin d'interdire toute connexion depuis Internet.
  * **Rotation régulière des mots de passe et MFA :** Renouveler périodiquement les mots de passe (ou utiliser des accès temporaires via un bastion/gestionnaire de secrets) et imposer l'authentification multifacteur (MFA) sur l'accès serveur.
  * **Traçabilité nominative (audit) :** Créer un compte nominatif par administrateur plutôt qu'un compte générique partagé `admin`, couplé à l'activation des logs d'audit MySQL pour savoir précisément qui a exécuté chaque action.

  #### 5. Sur la séparation des responsabilités

  * **Compte distinct :** Le fait de faire plusieurs compte chacun pour gérer son milieu et très utile pour savoir qui fais quoi tout en s'assurant que chacun puisse uniquement modifier ce sue quoi il travaille et rien d'autre. Cela permet de rajouter une couche de sécurité ainsi que de pouvoir rajouter une simplicité de maintenance.

  * **Bénéfice des séparations :** Cette séparation nous permet de faire un journal des logs plus précis afin de savoir quel utilisateurs à fait tel modifications cela sera beaucoup plus simple pour remonter à la personne si jamais il s'agit d'une tentative d'attaque.
  * **Repérage d'une faille sur un compte :** Si une faille de sécurité est repérer sur le compte `site`, alors l'attaque pourra uniquement se dérouler sur le contenu autorisé par l'utilisateur `site` et ne pourra rien faire de plus. Cela est du à la séparation des comptes car site ne possède pas les droits de travailler sur la gestion ou sur le coté administration car il en possèd pas les droits.
  * **En quoi cela reflète l'avantage ?** Cela nous permet de nous rendre compte que séparer chaque type de compte avec chacun son propre domaine de travail et ses propres droits pour empêcher les autres comptes de travailler sur votres projet. De plus en cas d'attaque, uniquement le département ciblé sera impacté mais les autre pourront eux continuer de travailler. Cela permet de pouvoir faire des remparts supplémentaires sur la cybersécurité.

  #### 6. Bonus : Sur les permissions implicites[cite: 12]

* **Permissions implicites attribuées par défaut par MySQL et vérification :**
  * **Le privilège `USAGE` :** Lorsqu'un utilisateur est créé dans MySQL, il reçoit automatiquement le privilège global `USAGE ON *.*`. Ce droit ne lui donne accès à aucune donnée, mais l'autorise à **se connecter au serveur MySQL** et à exécuter des commandes basiques (comme `SHOW DATABASES`, qui n'affiche que les bases accessibles ou `information_schema`).
  * **Accès implicite à `information_schema` et bases de test :** Tout utilisateur connecté peut interroger en lecture une partie de la base système `information_schema` (selon ses droits sur les objets correspondants) et, sur d'anciennes installations non sécurisées, accéder à d'éventuelles bases `test` ou `test_%` ou hériter des droits d'un utilisateur anonyme (`''@'localhost'`).
  * **Comment les vérifier :** En exécutant la requête `SHOW GRANTS FOR 'utilisateur'@'hote';` ou en consultant directement les tables système de la base `mysql` (`SELECT * FROM mysql.user;`, `mysql.db`, `mysql.tables_priv`).
* **Lister les permissions d'un utilisateur (`SHOW GRANTS`) et ce qu'elles révèlent :**
  * **Commande SQL :** `SHOW GRANTS FOR 'gestion'@'localhost';` (ou `SHOW GRANTS;` pour l'utilisateur actuellement connecté).
  * **Ce que cela révèle :** Cette commande affiche toutes les instructions `GRANT` actives pour ce compte précis (à la fois le privilège global par défaut `GRANT USAGE ON *.*` et les privilèges explicites accordés sur les bases ou tables comme `metalstorm.*`), ainsi que les rôles éventuels qui lui sont attribués. Elle permet de vérifier instantanément si le principe de moindre privilège est respecté et depuis quel hôte (`localhost`, `%`, IP) le compte est autorisé à se connecter.
* **Importance de vérifier régulièrement les permissions après la création initiale :**
  * **Éviter la dérive des privilèges (*Privilege Creep*) :** Au fil de la vie du projet (débogage, tests, évolutions), des administrateurs ajoutent souvent des droits temporaires (ex. `ALL PRIVILEGES` ou `DROP`) qui sont oubliés et jamais révoqués.
  * **Détecter des modifications non autorisées ou comptes orphelins :** Un audit régulier permet de repérer rapidement si un compte d'un ancien développeur/prestataire est resté actif, si un rôle a été modifié par erreur, ou si un attaquant ayant compromis un compte privilégié s'est octroyé de nouveaux droits ou créé un compte dérobé (*backdoor*).
* **Droits par défaut d'un nouvel utilisateur sans `GRANT` et dangers associés :**
  * **Droits par défaut :** Un compte créé uniquement avec `CREATE USER 'nouveau'@'localhost' IDENTIFIED BY '...';` possède uniquement le droit `USAGE ON *.*` (aucun droit de lecture, d'écriture ou de modification sur les bases métiers).
  * **Pourquoi cela reste dangereux :**
    * **Surface d'attaque et énumération :** Même sans droits sur les tables métiers, le compte peut s'authentifier sur le serveur MySQL, consommer des ressources (attaques par déni de service en ouvrant des milliers de connexions ou de requêtes lourdes) et interroger les métadonnées accessibles dans `information_schema` (version du serveur, variables système, liste des jeux de caractères/collations) pour préparer une attaque ciblée.
    * **Risque d'escalade ou d'erreur future :** Laisser des comptes inutilisés ou sans droits définis encombre la gestion des accès et augmente le risque qu'un administrateur leur attribue plus tard un rôle trop permissif par inadvertance ou que le compte soit exploité via une vulnérabilité du moteur MySQL.

    ### Mission 3 : Test des accès 

    #### Création des fichiers de connexion à la BDD pour chaque compte :

    * Le lien pour accéder au client.php :**http://172.16.199.254/B1/TP1MySQL/client.php**
    * Le lien pour accéder au admin.php : **http://172.16.199.254/B1/TP1MySQL/admin.php**
    * Le lien pour accéder au gestionnaire.php : **http://172.16.199.254/B1/TP1MySQL/gestionnaire.php**