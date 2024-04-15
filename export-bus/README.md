# Export-Bus

Scripts to export bus order to a google sheets with a cron job.

## RSYNC

To sync the folder with the remote server:

```sh
$ rsync -azP --no-perms --no-owner --no-group --delete --exclude-from='.gitignore' ../export-bus/ wp_materiel_ap:/var/www/materiel/wordpress/export-bus
```