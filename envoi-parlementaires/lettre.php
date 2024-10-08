<?php

namespace LFI\WPPlugins\EnvoiParlementaires;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function objet_lettre($expediteur)
{
    return ucfirst($expediteur["prenom"] . " " . ucfirst($expediteur["nom"])) . " souhaite vous interpeller.";
}

function mail_contenu($expediteur)
{
    return [
        "Madame la Députée, Monsieur le Député,",

        "Après son passage ce mercredi 2 octobre en commission des Lois, la proposition de résolution visant à destituer le président de la République Emmanuel Macron, prévue à l’article 68 de la Constitution, devrait prochainement être étudiée en Hémicycle.",
        "Le président de la République a en effet été lourdement sanctionné lors des élections européennes et législatives de 2024. Or, il a nommé comme Premier ministre Michel Barnier pour continuer la politique menée depuis 2017. C’est un affront démocratique, d’autant plus que Michel Barnier est membre d’un parti (LR) ayant recueilli à peine 5% des voix en 2024.",
        "Ce choix constitue un manquement grave aux devoirs du président de la République. En démocratie, seul le Peuple est souverain, comme le rappelle l’article 3 de la Déclaration des Droits de l’Homme et du Citoyen : « Le principe de toute souveraineté réside essentiellement dans la nation. Nul corps, nul individu ne peut exercer d'autorité qui n'en émane expressément ».",
        "Pour en finir avec ces manquements d’Emmanuel Macron et respecter le vote du peuple, il est nécessaire d’enclencher la procédure de destitution du président de la République.",
        "Par ce courriel, je vous demande solennellement de voter favorablement à cette motion de destitution du président de la République au sein de l'hémicycle de l'Assemblée nationale, afin de réunir le Parlement en Haute Cour et de juger des manquements d’Emmanuel Macron à la Constitution. ",
        "Je vous prie d'agréer, Madame la Députée, Monsieur le Député, l'expression de mes salutations républicaines.",

        ucfirst($expediteur["prenom"]) . " " . ucfirst($expediteur["nom"]),
    ];
}

function texte_intro($noms) {
    $nom_join = join(", ", $noms);
    if (count($noms) > 1) {
        return "Voici le texte généré à partir de vos informations,
    qui sera adressé à <strong>{$nom_join}</strong> qui n'ont pas signé la motion de destitution d'Emmanuel Macron,
    ni voté à la commission des lois mercredi 2 octobre..";
    }
    return "Voici le texte généré à partir de vos informations,
    qui sera adressé à <strong>{$nom_join}</strong> qui n'a pas signé la motion de destitution d'Emmanuel Macron,
    ni voté à la commission des lois mercredi 2 octobre..";
}

function generer_mail($parlementaires, $expediteur)
{
    if (is_null($parlementaires) || is_null($expediteur)) {
        $result = <<<EOD
        <p>Aucun député trouvé.</p>
        EOD;

        return $result;
    }

    $texte = mail_contenu($expediteur);

    $texte_lettre_html = implode(
        "\n",
        array_map(function ($paragraphe) {
            $paragraphe = str_replace(
                "\n",
                "<br>",
                htmlspecialchars($paragraphe, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5)
            );
            return "<p>$paragraphe</p>";
        }, $texte)
    );

    $texte_lettre_email = rawurlencode(implode("\n\n", $texte));
    $objet_lettre_email = rawurlencode(objet_lettre($expediteur));

    $noms = [];
    $emails = [];
    foreach ($parlementaires as &$parlementaire) {
        array_push($emails, $parlementaire["email"]);
        array_push($noms, $parlementaire["nom"]);
    }

    $texte_intro = texte_intro($noms);
    $email_concat = implode(",", $emails);

    $lien_email = htmlspecialchars(
        "mailto:?bcc=$email_concat&subject=$objet_lettre_email&body=$texte_lettre_email",
        ENT_QUOTES | ENT_HTML5,
    );

    $result = <<<EOD
      <p>$texte_intro<p>
      
      <blockquote>
        $texte_lettre_html
      </blockquote>

      <div>
        <form class="envoi-parlementaires-comission-lois" id="envoi-parlementaires">
          <input type="hidden" name="action" value="envoi-senateurs">
          <input type="hidden" name="email" value="$expediteur[email]">
          <input type="hidden" name="nom" value="$expediteur[nom]">
          <input type="hidden" name="prenom" value="$expediteur[prenom]">
          <input type="hidden" name="departement" value="$expediteur[departement]">
          <input type="hidden" name="campaign" value="envoi-destitution-2024-assemblee">
          <a onclick="mailto()" href="$lien_email">Je l'envoie moi-même</a>
          <button style="text-wrap: auto;" type="submit">Envoyez-le pour moi</button>
      </div>
      EOD;

    return $result;
}
