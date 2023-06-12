<?php

namespace LFI\WPPlugins\EnvoiDeputes;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function genrer( $terme, $sexe ) {
    $pos = mb_strpos( $terme, '/' );

    if ( $pos === false ) {
        return $terme;
    }

    $fem = mb_substr($terme, $pos + 1, null);

    if ( $sexe == 'F' && $fem ) {
        return $fem;
    }

    return mb_substr( $terme, 0, $pos );
}


function texte_lettre($depute, $expediteur) {
    $profession = $expediteur['profession'];
    if ($expediteur['civilite']) {
        $profession = genrer($profession, $expediteur['civilite']);
    }

    return [
        "À l'attention de $depute[civilite] $depute[nom_complet], $depute[fonction].",
        "$depute[adresse],",
        "En tant que $profession, je vous demande solennellement de voter le 4 mai prochain pour le retour aux tarifs réglementés de l’électricité pour les TPE et les PME. C’est un nouvel espoir, et peut-être le dernier, pour que nous puissions payer nos factures et survivre à 2023.",
        "Nous ne méritons pas ce qui nous arrive. Alors que nous travaillons plus de 70 heures par semaine, nous n’arrivons même plus à nous payer. Nos factures d’énergie ont été multipliées par 4, 5 voire 10.",
        "Par ailleurs, si cette loi venait à être adoptée pour les TPE et non pour les PME, les risques sont nombreux :",
        "1. Des petites PME, comme les boulangeries dépassant les 9 salariés, se verront contraintes de licencier pour pouvoir bénéficier des TRVe, réservés aux TPE.",
        "2. Un effondrement de l’industrie et des emplois, qui risque d’aggraver le phénomène de désindustrialisation en cours",
        "3. Les PME dans l’alimentaire se verront contrainte de répercuter sur les produits aux supermarchés leurs pertes, alors même que l’inflation alimentaire atteint un pic de 15%, atteint pour la dernière fois en… 1980 !",
        "Nous vous demandons de voter en faveur de cette loi et de tous les amendements qui pourraient pérenniser le retour des tarifs réglementés de l'électricité pour les entreprises. Nous comptons sur vous.",
        "Bien cordialement,\n$expediteur[nom_complet]",
    ];
}


function generer_lettre_html($depute, $expediteur) {
    if (is_null($depute) || is_null($expediteur)) {
        $result = <<<EOD
        <p>
            Aucun·e député·e des groupes <em>Les Républicains</em>, <em>Démocrate (MoDem et Indépendants)</em>, <em>Renaissance</em>, <em>LIOT</em>, <em>Rassemblement National</em> ou <em>Horizons</em> n'a été élu·e dans votre département.
        </p>
        EOD;

        return $result;
    }

    $texte = texte_lettre($depute, $expediteur);

    $texte_lettre_html = implode(
        "\n",
        array_map(function ( $paragraphe ) {
            $paragraphe = str_replace(
                "\n",
                "<br>",
                htmlspecialchars( $paragraphe, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5 )
            );
            return "<p>$paragraphe</p>";
        }, $texte ) );

    $texte_lettre_email = rawurlencode( implode( "\n\n", $texte ) );
    $sujet_lettre_email = rawurlencode( 'Protégez nos petites entreprises');
    $lien_email = htmlspecialchars(
        "mailto:$depute[email]?subject=$sujet_lettre_email&body=$texte_lettre_email",
        ENT_QUOTES | ENT_HTML5,
    );

    $result = <<<EOD
      <p>
        Voici le texte généré à partir de vos informations, adressé pour l'exemple $depute[recipient] <strong>$depute[nom_complet]</strong>.
      <p>

      <blockquote>
        $texte_lettre_html
      </blockquote>

      <div>
        <form class="envoi-deputes" id="envoi-deputes">
          <input type="hidden" name="action" value="envoi-deputes">
          <input type="hidden" name="departement" value="$depute[departement]">
          <input type="hidden" name="depute" value="$depute[id]">
          <input type="hidden" name="email" value="$expediteur[email]">
          <input type="hidden" name="nom" value="$expediteur[nom]">
          <input type="hidden" name="prenom" value="$expediteur[prenom]">
          <input type="hidden" name="profession" value="$expediteur[profession]">
          <input type="hidden" name="civilite" value="$expediteur[civilite]">
          <input type="hidden" name="campaign" value="envoi-deputes-0">
          <a href="$lien_email">Je l'envoie moi-même</a>
          <button type="submit">Envoyez-le pour moi</button>
      </div>
      <p>
        Si vous envoyez le texte vous-même, il sera expédié $depute[recipient] <strong>$depute[nom_complet]</strong>. En cliquant sur « Envoyez-le pour moi », nous l'expédierons par email de votre part à <strong>tou·tes les député·es</strong> de votre département.
      </p>
      EOD;

    return $result;
}
