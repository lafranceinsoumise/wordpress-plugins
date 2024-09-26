<?php

namespace LFI\WPPlugins\EnvoiParlementaires;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function objet_lettre($expediteur)
{
    return ucfirst($expediteur["nom"] . " " . ucfirst($expediteur["prenom"])) . " souhaite vous interpeller.";
}

function mail_contenu($expediteur)
{
    return [
        "Madame la Députée, Monsieur le Député,",

        "La proposition de résolution visant à destituer le président de la République Emmanuel Macron, prévue à l’article 68 de la Constitution, sera étudiée ce mercredi 2 octobre.",
        "Le président de la République a été lourdement sanctionné lors des élections européennes et législatives de 2024. Or il a nommé Michel Barnier Premier ministre pour continuer la politique menée depuis 2017.\nC’est un affront démocratique, d’autant plus que Michel Barnier est membre d’un parti (LR) ayant recueilli à peine 5% des voix en 2024.",
        "Ce choix constitue un manquement grave aux devoirs du président de la République. En démocratie, seul le Peuple et souverain.\nPour en finir avec ces manquements d’Emmanuel Macron et respecter le vote du peuple, il est nécessaire d’enclencher la procédure de destitution du président de la République.",
        "Par ce courriel, je vous demande solennellement de voter pour la destitution du président de la République au sein de la commission de Lois dans laquelle vous siégez.",
        "Je vous prie d'agréer, Madame la Députée, Monsieur le Député, l'expression de mes salutations distinguées.",

        ucfirst($expediteur["nom"]) . " " . ucfirst($expediteur["prenom"]),
    ];
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

    $email_concat = "";
    foreach ($parlementaires as &$parlementaire) {
        $email_concat = $parlementaire["email"] . ';' . $email_concat;
    }


    $lien_email = htmlspecialchars(
        "mailto:?bcc=$email_concat?subject=$objet_lettre_email&body=$texte_lettre_email",
        ENT_QUOTES | ENT_HTML5,
    );

    $result = <<<EOD
      <p>
        Voici le texte généré à partir de vos informations, qui sera adressé à <strong>tou·tes les députés membres de la commission des lois qui n'ont pas signés la motion de destitution d'Emmanuel Macron</strong>.
      <p>

      <blockquote>
        $texte_lettre_html
      </blockquote>

      <div>
        <form class="envoi-parlementaires-comission-lois" id="envoi-parlementaires">
          <input type="hidden" name="action" value="envoi-senateurs">
          <input type="hidden" name="email" value="$expediteur[email]">
          <input type="hidden" name="nom" value="$expediteur[nom]">
          <input type="hidden" name="prenom" value="$expediteur[prenom]">
          <input type="hidden" name="campaign" value="envoi-destitution-2024-comission-lois">
          <a onclick="mailto()" href="$lien_email">Je l'envoie moi-même</a>
          <button style="text-wrap: auto;" type="submit">Envoyez-le pour moi</button>
      </div>
      EOD;

    return $result;
}
