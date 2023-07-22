# Dealabs

### La grille d'évalutation se trouve dans le dossier rendu

## Docker

---

- Copier l'ensemble des fichiers relatifs à Docker à la racine de votre projet Symfony

- Builder l'image : `docker-compose build`
- Lancer les conteneurs : `make start_dev`
- Arrêter les conteneurs : `make stop_dev`
- Accéder au container PHP en tant que root : `docker exec -it -u root lpa_sf6_php bash`

- Apache : `http://localhost:8081`
- PhpMyAdmin : `http://localhost:8090`
- Mailcatcher - Interface web : `http://localhost:1081`, Port SMTP : `1026`
- MySQL : Port `3310`
- API : `http://localhost:8081/api`

## Installation

- `docker-compose up -d`
- `docker exec -it -u root lpa_sf6_php bash`
- `composer install`
- `npm install`
- `npm run dev`
- `php bin/console doctrine:database:create`
- `php bin/console doctrine:migrations:migrate`
- `php bin/console doctrine:fixtures:load`
- `php bin/console lexik:jwt:generate-keypair`

## Commande utile

- Hasher manuellement un mot de passe: `php bin/console security:hash-password`


## Debug

Si les mail n'arrive pas éxécuter cette commande :
- Consuming Messages (Running the Worker): `php bin/console messenger:consume async`

Si lors de l'upload cela ne fonctionne pas avec un message d'erreur disant qu'il n'arrive pas a créer le repertoireil faut créer les répertoire suivant:
- `/public/images/upload/deals`
- `/public/images/upload/categories`
