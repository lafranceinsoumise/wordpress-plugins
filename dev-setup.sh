#!/bin/sh
set -eu


lando start

lando wp core download \
      --path=wordpress \
      --locale=fr_FR

lando wp config create \
      --path=wordpress \
      --dbname=wordpress \
      --dbuser=wordpress \
      --dbpass=wordpress \
      --dbhost=database

lando wp core install \
      --path=wordpress \
      --url=https://wordpress-plugins.lndo.site \
      --title="Wordpress Plugins LFI" \
      --admin_user=admin \
      --admin_password=password \
      --admin_email=admin@wordpress-plugins.lndo.site

for plugin in lfi-settings lfi-agir-registration lfi-djan-stats lfi-compteur; do
    ln -s "../../../$plugin" "wordpress/wp-content/plugins/$plugin"
done
