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

        "Je me permets de vous écrire en tant que citoyen(ne) préoccupé(e) par la situation politique actuelle et par certaines décisions récentes du Président de la République, M. Emmanuel Macron. Ces décisions, ainsi que la manière dont elles ont été prises, soulèvent de vives inquiétudes sur le respect des principes démocratiques et de l'intérêt général de notre pays.

         En tant que membre de la Commission des Lois, votre rôle est essentiel dans la protection de l'État de droit et dans le maintien d'un équilibre démocratique sain. Il est de plus en plus évident que le Président de la République a outrepassé les pouvoirs qui lui sont conférés, mettant en péril l'esprit de notre Constitution.

        C'est pourquoi je vous interpelle aujourd'hui pour envisager la mise en place d'une procédure de destitution, conformément à l'article 68 de la Constitution. Cette procédure, bien que rare et grave, est indispensable lorsque les actes d'un Président vont à l'encontre de la confiance que les citoyens placent dans les institutions de notre République.

        Je vous invite donc, en tant que représentant(e) de la nation, à envisager sérieusement cette possibilité et à soutenir cette démarche pour rétablir la confiance entre le peuple et ses institutions.

        Je vous remercie par avance pour l’attention que vous porterez à ma demande et reste à votre disposition pour échanger plus en détail sur ce sujet.",

        "Cordialement,",
        "$expediteur[nom] $expediteur[prenom]",
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
          <input type="hidden" name="prenom" value="$expediteur[prenom]">
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
