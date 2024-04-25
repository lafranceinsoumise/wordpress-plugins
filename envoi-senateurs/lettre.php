<?php

namespace LFI\WPPlugins\EnvoiSenateurs;

if (!defined('ABSPATH')) {
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

function objet_lettre($senateur, $expediteur)
{
    return 'Inscription à l’ordre du jour du Sénat de la proposition de loi transpartisane pour la réouverture des accueils physiques des services publics';;
}

function texte_lettre($senateur, $expediteur)
{
    $profession = $expediteur['profession'];
    if ($expediteur['civilite']) {
        $profession = genrer($profession, $expediteur['civilite']);
    }

    return [
        "À l'attention de $senateur[civilite] $senateur[nom_complet], $senateur[fonction].",

        "",

        "$senateur[adresse],",

        "",

        "Le 30 novembre 2023, l’Assemblée nationale a adopté une proposition de loi transpartisane tendant à la réouverture des accueils physiques dans les services publics.",

        "Par ce vote, s’est ouvert l’espoir d’améliorer significativement l’accès effectif aux droits de millions de nos concitoyennes et concitoyens pour qui la dématérialisation tous azimuts des démarches administratives représente un obstacle majeur.",

        "J’ai moi-même et plusieurs de mes proches ont déjà fait l’expérience des difficultés que provoquent les procédures systématiquement dématérialisées pour accéder à des services ou faire valoir des droits",

        "Voilà pourquoi je vous demande de tout mettre en œuvre pour que cette proposition de loi soit mise à l’agenda et votée au Sénat",

        "Bien cordialement,",

        "",

        "$expediteur[nom_complet]",

        "",

        "PS. Si vous êtes favorable à la proposition de loi transpartisane tendant à la réouverture des accueils physiques dans les services publics vous pouvez le signaler en écrivant à l'adresse contact@rouvreznosservicespublics.fr."
    ];
}

function campaign_id()
{
    return "rouvreznosservicespublics_senateurs_2024-05";
}

function texte_rs($senateur)
{
    return "Face aux effets délétères d'une dématérialisation tous azimuts, soutenez vous l'inscription et le vote au Sénat de la proposition de loi votée à l'Assemblée pour rouvrir les accueils physiques des services publics ⁇";
}

function tracking_attrs($piece, $senateur)
{
    $campaign_id = campaign_id();
    $target = "$senateur[nom_complet] [$senateur[groupe]]";
    return "data-track-content data-content-name='$campaign_id' data-content-piece='$piece' data-content-target='$target'";
}

function lien_twitter($senateur)
{
    if (!$senateur["twitter"]) {
        return "";
    }

    $url = "https://twitter.com/intent/tweet";
    $text = texte_rs($senateur);
    $params = [
        'text' => "$senateur[civilite] @$senateur[twitter]. $text",
        'url' => 'https://RouvrezNosServicesPublics.fr'
    ];

    $url .= "?" . http_build_query($params);
    $tracking_attrs = tracking_attrs("twitter", $senateur);

    return "<div class='envoi' id='twitter'><a href='$url' target='_blank' rel='noopener noreferrer' $tracking_attrs>Interpellez $senateur[nom_complet] sur twitter</a></div>";
}

function lien_facebook($senateur)
{
    if (!$senateur["facebook"]) {
        return "";
    }

    $params = [
        'text' => texte_rs($senateur) . " https://RouvrezNosServicesPublics.fr"
    ];
    $url = "https://m.me/$senateur[facebook]";
    $url .= "?" . http_build_query($params);
    $tracking_attrs = tracking_attrs("facebook", $senateur);

    return "<div class='envoi' id='facebook'><a href='$url' target='_blank' rel='noopener noreferrer' $tracking_attrs>Interpellez $senateur[nom_complet] via Messenger</a></div>";
}

function interpellation_rs($senateur)
{
    $texte_rs = texte_rs($senateur);
    $lien_twitter = lien_twitter($senateur);
    $lien_facebook = lien_facebook($senateur);

    if (!$lien_twitter && !$lien_facebook) {
        return "";
    }

    return <<<EOD
      <h5>Interpellez $senateur[nom_complet] sur les réseaux sociaux</h5>

      <p>
        Voici le texte à utiliser pour interpeller <strong>$senateur[nom_complet]</strong> ($senateur[groupe]) sur les réseaux sociaux.
      <p>

      <blockquote>$texte_rs</blockquote>

      $lien_twitter
      $lien_facebook
    EOD;
}

function generer_interpellation($senateur, $twitters, $facebooks, $expediteur)
{
    if (is_null($senateur) || is_null($expediteur)) {
        $result = <<<EOD
        <p>🎉 🎉 🎉<br />Aucun·e des sénateur·ices élu·es dans votre département n'est opposé·e à cette proposition de loi !<br />🎉 🎉 🎉</p>
        EOD;

        return $result;
    }

    $campaign_id = campaign_id();
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
    $objet_lettre_email = rawurlencode(objet_lettre($senateur, $expediteur));

    $lien_email = htmlspecialchars(
        "mailto:$senateur[email]?subject=$objet_lettre_email&body=$texte_lettre_email",
        ENT_QUOTES | ENT_HTML5,
    );
    $tracking_attrs = tracking_attrs("email", $senateur);

    $interpellation_rs = interpellation_rs($senateur);

    $result = <<<EOD

      $interpellation_rs

      <h5>Interpellez $senateur[nom_complet] par e-mail</h5>

      <p>
        Voici le texte généré à partir de vos informations, adressé pour l'exemple $senateur[recipient] <strong>$senateur[nom_complet]</strong> ($senateur[groupe]).
      <p>

      <blockquote>$texte_lettre_html</blockquote>

      <p>
        Si vous envoyez le texte vous-même, il sera expédié $senateur[recipient] <strong>$senateur[nom_complet]</strong>.
        <br/>
        En cliquant sur « Envoyez-le pour moi », nous l'expédierons par email de votre part à <strong><u>tou·tes les sénateur·ices</u></strong> de votre département.
      </p>

      <div>
        <form class="envoi" id="envoi-senateurs">
          <input type="hidden" name="action" value="envoi-senateurs">
          <input type="hidden" name="departement" value="$senateur[departement]">
          <input type="hidden" name="senateur" value="$senateur[id]">
          <input type="hidden" name="email" value="$expediteur[email]">
          <input type="hidden" name="nom" value="$expediteur[nom]">
          <input type="hidden" name="prenom" value="$expediteur[prenom]">
          <input type="hidden" name="profession" value="$expediteur[profession]">
          <input type="hidden" name="civilite" value="$expediteur[civilite]">
          <input type="hidden" name="campaign" value="$campaign_id">
          <a target='_blank' rel='noopener noreferrer' href="$lien_email" $tracking_attrs>Je l'envoie moi-même</a>
          <button type="submit">Envoyez-le pour moi</button>
      </div>

      <p id="success-message">
        Merci, votre email va être envoyé automatiquement. Vous en recevrez une copie sur l’adresse email que vous avez indiquée.
      </p>
      EOD;

    return $result;
}
