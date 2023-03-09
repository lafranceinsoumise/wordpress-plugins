<?php

namespace LFI\WPPlugins\EnvoiSenateurs;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function genrer( $terme, $sexe ) {
    $pos = mb_strpos( $terme, '·' );

    if ( $pos === false ) {
        return $terme;
    }


    $masc = mb_substr( $terme, 0, $pos );

    if ( $sexe == 'M' ) {
        return $masc;
    }

    $ext = mb_substr( $terme, $pos + 1, null);

    $fem = mb_substr( $terme, 0, 1 + $pos - mb_strlen($ext)) . $ext;
    return $fem;
}


function texte_lettre($senateur, $expediteur) {
    $profession = implode(
        ' ',
        array_map(
            function ( $terme ) { return genrer( $terme, $expediteur['civilite'] ); },
            explode( ' ', $expediteur['profession'] )
        )
    );

    return [
        "À l'attention de $senateur[civilite] $senateur[nom_complet], $senateur[fonction].",
        "$senateur[adresse],",
        "En tant que $profession c’est un nouvel espoir, et peut-être le dernier, pour que nous puissions payer nos factures et survivre à 2023.",
        "Nous ne méritons pas ce qui nous arrive. Alors que nous travaillons plus de 70 heures par semaine, nous n’arrivons même plus à nous payer. Nos factures d’énergie ont été multipliées par 4, 5 voire 10.",
        "Les aides mises en place par le Gouvernement sont totalement insuffisantes : 80% des boulangeries n’ont pas accès au bouclier tarifaire TPE, et l’amortisseur électricité pour les PME ne prend en compte que 10 à 20% des factures. Les ETI n’ont le droit à rien. Ces hausses des tarifs de l’énergie mettent en péril la survie de notre activité et l'emploi de nos salariés.",
        "L'examen de la proposition de loi de renationalisation du groupe EDF, adoptée par l’Assemblée, et notamment son article 3bis portant le retour des Tarif réglementé de vente de l'électricité (TRVE) pour toutes les entreprises de moins de 5000 salariés, sera examiné le 6 avril au Sénat.",
        "Nous vous demandons de voter en faveur de cette loi et de tous les amendements qui pourraient pérenniser le retour des tarifs réglementés de l'électricité pour les entreprises.",
        "Bien cordialement,\n$expediteur[nom_complet]",
    ];
}


function generer_lettre_html($senateur, $expediteur) {
    $texte = texte_lettre($senateur, $expediteur);

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
        "mailto:$senateur[email]?subject=$sujet_lettre_email&body=$texte_lettre_email",
        ENT_QUOTES | ENT_HTML5,
    );

    $result = <<<EOD
      <p>Voici le texte généré à partir de vos informations, adressé à un des sénateurs de votre département.</p>

      <blockquote>
        $texte_lettre_html
      </blockquote>

      <div>
        <form class="envoi-senateurs">
          <input type="hidden" name="action" value="envoi-senateurs">
          <input type="hidden" name="departement" value="$senateur[departement]">
          <input type="hidden" name="senateur" value="$senateur[id]">
          <input type="hidden" name="email" value="$expediteur[email]">
          <input type="hidden" name="nom" value="$expediteur[nom]">
          <input type="hidden" name="prenom" value="$expediteur[prenom]">
          <input type="hidden" name="profession" value="$expediteur[profession]">
          <a href="$lien_email">Je l'envoie moi-même</a>
          <button type="submit">Envoyez-le pour moi</button>
      </div>
      EOD;

    return $result;
}
