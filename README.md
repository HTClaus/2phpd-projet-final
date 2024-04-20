# Gestionnaire de Tournois de Jeux Vidéo avec Symfony

Ce projet a été réalisé dans le cadre d'un cours PHPD de SUPINFO sur la programmation web avec Symfony à l'école. 

L'objectif était de développer un système de gestion de tournois de jeux vidéo en utilisant le framework Symfony.

## Prérequis

Avant de commencer, assurez-vous d'avoir installé les éléments suivants sur votre système :

- PHP >= 7.4
- Composer (pour installer les dépendances Symfony)
- Un serveur local type Wamp ou Mamp (pour la base de donnée)

## Installation

### 1. Clonez ce dépôt sur votre machine locale :

  git clone https://github.com/HTClaus/2phpd-projet-final.git

### 2. Accédez au répertoire du projet :
   
  cd 2PHPD

### 3. Installez les dépendances Symfony via Composer :
   
  composer install

### 4. Configurez votre base de données dans le fichier .env en renseignant les informations de connexion.
   
  Pour un serveur SQL prenez la ligne :
  
  DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8&charset=utf8mb4"

  A modifier :
  - Le premier "app" : le nom d'utilisateur de la base de données du serveur
  - "!ChangeMe!" : le mot de passe
  - Le second "app" : le nom de la table de la base de données, ici : 2phpd
  - Le chiffre "8" après la version : indiquez la version de votre serveur 

### 5. Créez la base de données et exécutez les migrations :
   
  php bin/console doctrine:database:create
  
  php bin/console doctrine:migrations:migrate

### 6. Chargez des données de test (fixtures) :
   
   php bin/console doctrine:fixtures:load


# Utilisation
  Pour lancer l'application sur le port 8000, exécutez la commande suivante depuis la racine du projet :
  
  php -S 127.0.0.8000 -t public
  
  Ensuite, accédez à l'URL fournie par Symfony pour visualiser l'application dans votre navigateur.

# Fonctionnalités
  Création, édition et suppression de tournois
  
  Inscription des joueurs à des tournois
  
  Gestion des matchs et résultats
  
  Classement des joueurs et des équipes
  
  Génération de rapports et statistiques


