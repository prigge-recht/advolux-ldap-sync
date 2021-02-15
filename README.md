# Advolux LDAP Sync

Mit diesem Kommandozeolenprogramm lassen sich die Kontakte in Advolux einfach mit einem LDAP-Server synchronisieren:

![alt text](https://cloud.robincramer.de/s/oDG24pgyHQMn3z8/preview)

## Hintergrund

Advolux bietet standardmäßig leider nur eine Synchronisation von Kontakten mit Microsoft Exchange. Wer nicht auf
Exchange setzt, kann die Kontakte mit diesem Kommandozeilenprogramm mit einem LDAP-Server synchronisieren. Damit lassen
sich die Kontakte mit anderen Diensten verknüpfen, beispielsweise den snom Telefonen.

## Systemvoraussetzungen

* PHP 7.4 oder höher
* PHP extension: `php-xml`, `php-simplexml` und `php-ldap`
* composer

## Installation

Repository klonen und Dependencies installieren:

```
git clone https://github.com/rocramer/advolux-ldap-sync.git
cd advolux-ldap-sync
composer install
```

Die LDAP-Konfiguration kann in `config/ldap.php` vorgenommen werden.

Advolux speichert im Ordner `sync/personen` XML-Dateien zu den Kontakten. Der Pfad zu diesem Ordner muss beim Start der
Synchrinisation angegeben werden. Synchronisation starten:

```
php advolux-ldap-sync start /pfad/zur/advolux/installation
```

## Debugging

Zur Fehleranalyse lohnt ein Blick in die `storage/logs/advolux-ldap-sync.log`, dort werden Änderungen an den Daten
geloggt.
