<?php

namespace LFI\WPPlugins\EnvoiSenateurs;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function genrer($terme, $sexe)
{
    $pos = mb_strpos($terme, '/');

    if ($pos === false) {
        return $terme;
    }

    $fem = mb_substr($terme, $pos + 1, null);

    if ($sexe == 'F' && $fem) {
        return $fem;
    }

    return mb_substr($terme, 0, $pos);
}


function texte_lettre($senateur, $expediteur) {
    $profession = $expediteur['profession'];
    if ($expediteur['civilite']) {
        $profession = genrer($profession, $expediteur['civilite']);
    }

    return [
        "À l'attention de $senateur[civilite] $senateur[nom_complet], $senateur[fonction].",
        "$senateur[adresse],",
        "En tant que $profession, je vous demande solennellement d’inscrire la PPL visant à protéger le groupe EDF d’un démembrement à l’ordre du jour au Sénat au mois de juin ou de juillet, pour le retour aux tarifs réglementés de l’électricité pour les TPE et les PME. C’est un nouvel espoir, et peut-être le dernier, pour que nous puissions payer nos factures et survivre à la crise de l’énergie qui rend notre activité de plus en plus difficile, nous oblige souvent à licencier et nous interdit de nous projeter.",
        "Nous ne méritons pas ce qui nous arrive. Alors que nous travaillons plus de 70 heures par semaine, nous n’arrivons même plus à nous payer. Nos factures d’énergie ont été multipliées par 4, 5 voire 10. Les aides mises en place par le Gouvernement sont totalement insuffisantes : 80% des boulangeries n’ont pas accès au bouclier tarifaire, et l’amortisseur électricité pour les PME ne prend en compte que 10 à 20% des factures. Pour beaucoup d’entre nous, il ne nous reste que quelques mois à vivre.",
        "Les communes, concernées par le retour aux TRVe dans ce texte, comptent également énormément sur ce texte. Elles sont aujourd’hui contraintes de faire des choix entre les services publics, ou d'augmenter les impôts !",
        "Cette situation n’est pas viable, surtout quand on sait qu’en France, c’est EDF qui produit 80% de notre électricité et que le coût de production en France est de 60€/MWh.",
        "Je compte sur votre soutien, en inscrivant cette PPL urgente et vitale à l’ordre du jour, et en la votant conforme à son adoption à l’Assemblée nationale. Nous, PME, y serons vigilants.",
        "Nous comptons sur vous.",
        "Bien cordialement,",
        "",
        "$expediteur[nom_complet]",
    ];
}


function generer_lettre_html($senateur, $expediteur)
{
    if (is_null($senateur) || is_null($expediteur)) {
        $result = <<<EOD
        <p>Aucun·e sénateur·ice des groupes opposé·e n'a été élu·e dans votre département.</p>
        EOD;

        return $result;
    }

    $texte = texte_lettre($senateur, $expediteur);

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
    $sujet_lettre_email = rawurlencode('Protégez nos petites entreprises');
    $lien_email = htmlspecialchars(
        "mailto:$senateur[email]?subject=$sujet_lettre_email&body=$texte_lettre_email",
        ENT_QUOTES | ENT_HTML5,
    );

    $result = <<<EOD
      <p>
        Voici le texte généré à partir de vos informations, adressé pour l'exemple $senateur[recipient] <strong>$senateur[nom_complet]</strong>.
      <p>

      <blockquote>
        $texte_lettre_html
      </blockquote>

      <div>
        <form class="envoi-senateurs" id="envoi-senateurs">
          <input type="hidden" name="action" value="envoi-senateurs">
          <input type="hidden" name="departement" value="$senateur[departement]">
          <input type="hidden" name="senateur" value="$senateur[id]">
          <input type="hidden" name="email" value="$expediteur[email]">
          <input type="hidden" name="nom" value="$expediteur[nom]">
          <input type="hidden" name="prenom" value="$expediteur[prenom]">
          <input type="hidden" name="profession" value="$expediteur[profession]">
          <input type="hidden" name="campaign" value="envoi-senateurs-06.2023">
          <a href="$lien_email">Je l'envoie moi-même</a>
          <button type="submit">Envoyez-le pour moi</button>
      </div>
      <p>
        Si vous envoyez le texte vous-même, il sera expédié $senateur[recipient] <strong>$senateur[nom_complet]</strong>. En cliquant sur « Envoyez-le pour moi », nous l'expédierons par email de votre part à <strong>tou·tes les sénateur·ices</strong> de votre département.
      </p>
      EOD;

    return $result;
}
