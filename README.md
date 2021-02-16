# Advolux LDAP Sync

Mit diesem Kommandozeilenprogramm lassen sich die Kontakte in Advolux einfach mit einem LDAP-Server synchronisieren:

![alt text](https://cloud.robincramer.de/s/oDG24pgyHQMn3z8/preview)

## Hintergrund

Advolux bietet standardmäßig leider nur eine Synchronisation von Kontakten mit Microsoft Exchange. Wer nicht auf
Exchange setzt, kann die Kontakte mit diesem Kommandozeilenprogramm mit einem LDAP-Server synchronisieren. Damit lassen
sich die Kontakte mit anderen Diensten verknüpfen, beispielsweise den snom Telefonen.

Derzeit werden nur Vorname, Nachname, Anzeigename und Telefonnummer synchronisiert. Adressen oder andere Kontaktangaben werden nicht an LDAP übertragen.

## Systemvoraussetzungen

* PHP 7.4 oder höher
* PHP-Erweiterungen: `php-xml`, `php-simplexml`, `php-ldap` und `php-mbstring`
* composer

## Installation


Repository klonen und Abhängigkeiten installieren:

```
sudo apt-get install php 8.0-fpm php8.0-xml php8.0-simplexml php8.0-ldap php8.0-mbstring
curl -sS https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer

git clone https://github.com/rocramer/advolux-ldap-sync.git
cd advolux-ldap-sync
composer install
```

Die LDAP-Konfiguration kann in `config/ldap.php` vorgenommen werden.

Advolux speichert im Ordner `AdvoluxData/sync/person/` XML-Dateien zu den Kontakten. Der Pfad zu diesem Ordner muss beim Start der
Synchronisation angegeben werden. Synchronisation starten:

```
php advolux-ldap-sync start /pfad/
```

## Cronjob

Zur täglichen Synchronisation kann ein Cronjob eingerichtet werden, der das Skript regelmäßig ausführt. Folgender Eintrag synchronisiert die Telefonnummern täglich um 02:00 Uhr (Pfadangaben anpassen!):

```
0 2 * * * php /home/advolux/advolux-ldap-sync/advolux-ldap-sync start /home/advolux/AdvoluxData/sync/person/
```

## Debugging

Zur Fehleranalyse lohnt ein Blick in die `storage/logs/advolux-ldap-sync.log`, dort werden Änderungen an den Daten
geloggt.
