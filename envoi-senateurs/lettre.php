<?php

namespace LFI\WPPlugins\EnvoiSenateurs;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function texte_lettre($senateur, $expediteur) {
    return [
        "À l'attention de $senateur[civilite] $senateur[nom_complet], $senateur[fonction].",
        "$senateur[adresse],",
        "Le jeudi 9 février dernier, l’Assemblée nationale a voté le retour pour un an aux tarifs réglementés de l’électricité pour les entreprises (TPE, PME, ETI), dans le cadre de l’adoption de la PPL de Philippe Brun visant à renationaliser EDF. En tant que $expediteur[profession] c’est un nouvel espoir, et peut-être le dernier, pour que nous puissions payer nos factures et survivre à 2023.",
        "Nous ne méritons pas ce qui nous arrive. Alors que nous travaillons plus de 70 heures par semaine, nous n’arrivons même plus à nous payer. Nos factures d’énergie ont été multipliées par 4, 5 voire parfois 10. Les aides mises en place par le Gouvernement sont totalement insuffisantes : 80% des boulangeries n’ont pas accès au bouclier tarifaire, et l’amortisseur électricité pour les PME ne prend en compte que 10 à 20% des factures. Les ETI n’ont le droit à rien. Pour beaucoup d’entre nous ces hausses des tarifs de l’énergie mettent en péril nos activités: il ne nous reste que quelques mois à vivre.",
        "Nous vous demandons d’inscrire le plus rapidement possible à l’ordre du jour du Sénat l’examen de la Proposition de loi Visant à la renationalisation du groupe EDF, adoptée par l’Assemblée, comprenant l’article 3bis portant le retour des TRVE pour nous.",
        "Nous aimerions que ce retour au TRVE devienne pérenne, mais le temps presse. Il faut que la PPL soit adoptée conforme. En effet, un retour à l’Assemblée nationale pour une 2ème lecture, permettrait à la majorité de bloquer son examen et nous serait fatal. Nous espérons que de nouvelles délibérations pourraient par la suite pérenniser le dispositif.",
        "Nous vous remercions $senateur[adresse]. Nous comptons sur votre soutien aux artisans, commerçants et petites entreprises qui font travailler des millions de salariés dans le pays.",
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
