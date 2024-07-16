# LFI Wordpress plugins

## Environnement de développement

Un environnement de développement [`lando`](https://docs.lando.dev/install/linux.html) est disponbile et utilisable en lançant le script `dev-setup.sh` pour installer wordpress et les plugins :

```sh
$ ./dev-setup.sh

Here are some vitals:

NAME      wordpress-plugins
LOCATION  /home/dr27/code/lfi/wordpress/htdocs/wp-content/plugins/lfi
SERVICES  appserver, database
URLS
✔ APPSERVER URLS
✔ https://localhost:32779 [404]
✔ http://localhost:32778 [404]
✔ http://wordpress-plugins.lndo.site:8000/ [404]
✔ https://wordpress-plugins.lndo.site/ [404]
```

Une fois le script terminé, le site wordpress sera accessible via les URLS ci-dessus. L'admin sera accessible au chemin `/wp-admin/` avec les informations de connexion suivantes :

```yml
Username: admin
Password: password
```

Les plugins seront automatiquement ajoutés au site wordpress, mais ils devront être activés manuellement.

## Les plugins

### envoi-deputes

Ce plugin permet d'ajouter à Wordpress le shortcode `[envoi-deputes]` pour afficher une page d'interpellation de député·es.

La liste des député·es actif·ves aver leurs adresses email est sauvegardée dans le fichier `deputes.json`.

Au chargement de la page contenant le shortcode (ex. suite à l'envoi d'un formulaire avec une methode GET), les informations nécessaires sont récupérées via les paramètres de l'URL et un député ou une députée est sélectionné·e de manière aléatoire parmi celles et ceux du département choisi. À partir des informations de l'élu·e et de l'expéditeur, le shortcode génère le texte d'une lettre d'interpellation et ajoute deux boutons : un lien `mailto` pour que la personne envoie elle même le message et un autre bouton qui lui permet de demander un envoi de sa part à tous·tes les déput·es de son département.

Les paramétres à ajouter à l'URL sont les suivants :
- *email* : l'email de l'expéditeur·rice
- *nom* : le nom de famille de l'expéditeur·rice
- *prenom* : le prénom de l'expéditeur·rice
- *profession* : la profession de l'expéditeur·rice
- *civilite* : la civilité de l'expéditeur·rice (F pour femme, M pour homme)
- *departement* : le code du département de l'expéditeur·rice

Le plugin ajoute également un endpoint API qui sera appelé lorsque la personne clique sur le bouton "Envoyez-le pour moi" et qui enregistre les données de la demande dans une table dédiée de la base de données. Le plugin ne contient aucune logique pour envoyer automatiquement ces messages : il sera donc nécessaire de récuperer ces données et envoyer ces emails manuellement.


### envoi-senateurs

Ce plugin permet d'ajouter le shortcode `[lettre_senateurs]` permettant d'afficher une page d'interpellation de sénateur·ices.

La liste des sénateur·ices actif·ves aver leurs adresses email et, pour certain·es, leur nom d'utilisateur·ice Twitter est sauvegardée dans le fichier `senateurs.json`. Le script JavaScript `generateJsonData.mjs` peut être utilisé pour mettre
automatiquement à jour le fichier json à partir des données du Sénat accessibles en open data.

Au chargement de la page contenant le shortcode (ex. suite à l'envoi d'un formulaire avec une methode GET), les informations nécessaires sont récupérées via les paramètres de l'URL et un député ou une députée est sélectionné·e de manière aléatoire parmi celles et ceux du département choisi. À partir des informations de l'élu·e et de l'expéditeur, le shortcode génère le texte d'une lettre d'interpellation et ajoute deux boutons : un lien `mailto` pour que la personne envoie elle même le message et un autre bouton qui lui permet de demander un envoi de sa part à tous·tes les déput·es de son département.

Les paramétres à ajouter à l'URL sont les suivants :
- *email* : l'email de l'expéditeur·rice
- *nom* : le nom de famille de l'expéditeur·rice
- *prenom* : le prénom de l'expéditeur·rice
- *profession* : la profession de l'expéditeur·rice
- *civilite* : la civilité de l'expéditeur·rice (F pour femme, M pour homme)
- *departement* : le code du département de l'expéditeur·rice

Le plugin ajoute également un endpoint API qui sera appelé lorsque la personne clique sur le bouton "Envoyez-le pour moi" et qui enregistre les données de la demande dans une table dédiée de la base de données. Le plugin n'envoie pas automatiquement ces messages, mais une commande wordpress est disponible pour cela et peut être ajouter à une tache cron :

```sh
$ wp senateurs-scheduled [identifiant de la campagne]
```
L'identifiant de la campagne est défini dans l'HTML généré :
```php
// lettre.php
// ...
<input type="hidden" name="campaign" value="envoi-senateurs-06.2023">
```


### export-bus

Ce plugin permet de générer des commandes wordpress pour exporter les commandes de bus du [site matériel](https://materiel.actionpopulaire.fr) vers un feuille Google sheets. Les commandes peuvent être ajoutées à une tache cron pour mettre à jour automatiquement le tableur.

Pour générer une nouvelle commande, il suffit de créer un fichier php à la racine contenant le code suivant :

```php
<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

require_once(dirname(__FILE__) . '/WC_Bus_Order_Exporter.php');

(new WC_Bus_Order_Exporter(
    // L'ID du produit woocommerce correspondant au bus
    69215,
    // L'ID du tableur Google sheets ciblé
    "1kczfXHj-rfQNQBZmOw5JT9lC9Q5yUOq6VM6hqJCmo_c",
    // Optionnel, le nom de la feuille Google sheets ciblée
    // (par défaut, "_export")
    "Feuille 1"
))->export();

```

Pour que le script puisse envoyer des données à Google Sheets, l'utilisateur `action-populaire@action-populaire.iam.gserviceaccount.com` doit être ajouté aux éditeurs du tableur.


### lfi-agir-events

Ce plugin ajoute un bloc à Elementor qui permet d'afficher des événements Action populaire (mais il n'a jamais encore été utilisé).


### lfi-agir-registration

Ce plugin ajoute :
- une action aux formulaires Elementor qui permet de s'inscrire sur la plateforme Action populaire (cf. [lafranceinsoumise.fr](https://lafranceinsoumise.fr)).
- le shortcode `[agir_cagnotte]` pour récuperer le montant d'une cagnotte Action populaire (cf. [ici](https://lafranceinsoumise.fr/un-local-dans-chaque-departement/))
- le shortcode `[agir_signatures]` pour récuperer le nombre de signataires Mélenchon 2022
- le shortcode `[agir_eu24_dons]` pour récuperer le montant des dons et prêts pour la campagne des européennes 2024 (cf. [ici](https://lafranceinsoumise.fr/europeennes-2024/))


### lfi-djan-stats

Ce plugin ajoute le shortcode `[lfi_djan_stats]` qui permet de récuperer les statistiques d'utilisation d'un lien court créé dans djan (cf. [ici](https://onvoteinsoumis.fr/verifier-votre-situation-electorale/)).


### lfi-page-categories

Ce plugin ajoute catégories et tags aux pages


### lfi-settings

Ajustements divers à mettre en place sur tous nos wordpress


### rss-aggregator-link-to-source

Plugin ultrabasique pour lier les titres aux articles