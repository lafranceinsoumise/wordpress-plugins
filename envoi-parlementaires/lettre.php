<?php

namespace LFI\WPPlugins\EnvoiParlementaires;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function objet_lettre($senateur, $expediteur)
{
    return '';
}

function mail_contenu($parlementaire, $expediteur)
{
    return [
        "À l'attention de $parlementaire[nom].",

        "",

        "$parlementaire[email],",
        "TEXTE",
        "Cordialement,",
        "",
        "$expediteur[nom]",
    ];
}


function generer_mail($parlementaire, $expediteur)
{
    if (is_null($parlementaire) || is_null($expediteur)) {
        $result = <<<EOD
        <p>Aucun député trouvé.</p>
        EOD;

        return $result;
    }

    $texte = mail_contenu($parlementaire, $expediteur);

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
    $objet_lettre_email = rawurlencode(objet_lettre($parlementaire, $expediteur));

    $lien_email = htmlspecialchars(
        "mailto:$parlementaire[email]?subject=$objet_lettre_email&body=$texte_lettre_email",
        ENT_QUOTES | ENT_HTML5,
    );

    $result = <<<EOD
      <p>
        Voici le texte généré à partir de vos informations, adressé pour l'exemple $parlementaire[email] <strong>$parlementaire[nom]</strong>.
      <p>

      <blockquote>
        $texte_lettre_html
      </blockquote>

      <div>
        <form class="envoi-parlementaires-comission-lois" id="envoi-parlementaires">
          <input type="hidden" name="action" value="envoi-senateurs">
          <input type="hidden" name="email" value="$expediteur[email]">
          <input type="hidden" name="nom" value="$expediteur[nom]">
          <input type="hidden" name="campaign" value="envoi-destitution-2024-comission-lois">
          <a href="$lien_email">Je l'envoie moi-même</a>
          <button type="submit">Envoyez-le pour moi</button>
      </div>
      <p>
        Si vous envoyez le texte vous-même, il sera expédié à $parlementaire[email] <strong>$parlementaire[nom]</strong>. En cliquant sur « Envoyez-le pour moi », nous l'expédierons par email de votre part à <strong>tou·tes les députés membres de la comission des lois qui n'ont pas signés la motion de destitution d'Emmanuel Macron</strong>.
      </p>
      EOD;

    return $result;
}
