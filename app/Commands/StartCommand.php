<?php

namespace App\Commands;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class StartCommand extends Command
{
    protected array $files;

    protected int $newEntriesCount;
    protected int $editEntriesCount;
    protected int $noPhoneNumberCount;
    protected int $multiplePhoneNumberCount;
    protected Collection $contacts;

    protected $ldapConnection;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'start
                            {path : Path of XML files (required)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Start synchronisation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->files = [];
        $this->newEntriesCount = 0;
        $this->editEntriesCount = 0;
        $this->noPhoneNumberCount = 0;
        $this->multiplePhoneNumberCount = 0;
        $this->contacts = collect();

        print("\nSynchronisation starten...\n\n");
        Log::info("Synchronisation starten");

        $this->collectFiles();

        $this->connectLDAP();

        print("\n");

        $bar = $this->output->createProgressBar(count($this->files));

        $bar->start();

        foreach ($this->files as $file) {

            $xml = simplexml_load_file($this->argument('path').$file);

            $this->syncWithLDAP($xml);

            $bar->advance();
        }

        $bar->finish();

        $this->showInfo();

        ldap_close($this->ldapConnection);
    }

    protected function collectFiles(): void
    {
        $this->task("XML-Dateien einlesen", function () {
            $this->files = array_diff(scandir($this->argument('path')), array('..', '.'));

            foreach ($this->files as $key => $file) {
                if (! Str::endsWith($file, '.xml')) {
                    unset($this->files[$key]);
                }
            }
            if (count($this->files) === 0) {
                return false;
            }

            return true;
        });
    }

    protected function connectLDAP(): void
    {
        $this->task("LDAP-Verbindung aufbauen", function () {
            $this->ldapConnection = ldap_connect(config('ldap.server'));
            ldap_set_option($this->ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);

            $ldapBind = ldap_bind($this->ldapConnection, "cn=".config('ldap.login-cn').",dc=".config('ldap.dc'), config('ldap.login-password'));

            if (! $this->ldapConnection || ! $ldapBind) {
                return false;
            }

            return true;
        });
    }

    private function collectContact($xml): array
    {
        $phoneNumber = trim("{$xml->referenz->tefax->vorwahl} {$xml->referenz->tefax->anschluss} {$xml->referenz->tefax->app}");

        return array_filter([
            'uid' => trim($xml->person->uid),
            'cn' => trim($xml->info->bezeichnung),
            'displayName' => trim($xml->info->bezeichnung),
            'givenName' => trim($xml->person->vorname),
            'sn' => trim($xml->person->name1." ".$xml->person->name2." ".$xml->person->name3),
            'objectclass' => "inetOrgPerson",
            'telephoneNumber' => $phoneNumber,
        ]);
    }

    private function syncWithLDAP($xml)
    {
        $contact = $this->collectContact($xml);

        $dn = "uid={$contact['uid']},ou=".config('ldap.ou').",dc=".config('ldap.dc');
        $searchDn = "ou=".config('ldap.ou').",dc=".config('ldap.dc');

        if (! $contact['cn']) {
            return;
        }

        if (config('ldap.ignore-without-phone') && ! array_key_exists('telephoneNumber', $contact) && empty($contact['telephoneNumber'])) {
            $this->noPhoneNumberCount = $this->noPhoneNumberCount + 1;
            return;
        }


        // Update if Entry exist
        $search = ldap_search($this->ldapConnection, $searchDn, "(uid={$contact['uid']})");
        $searchCount = ldap_count_entries($this->ldapConnection, $search);

        if ($searchCount) {
            $entry = ldap_get_entries($this->ldapConnection, $search);

            // If phone number and name not changed, return
            if (
                ($entry[0]['telephonenumber'][0] === $contact['telephoneNumber']) &&
                ($entry[0]['cn'][0] === $contact['cn'])
            ) {
                return;
            }

            ldap_mod_replace($this->ldapConnection, $dn, $contact);
            $this->editEntriesCount = $this->editEntriesCount + 1;
        }


        // Check if phone number already exist
        $number  = str_replace('(', '', $contact['telephoneNumber']);
        $number  = str_replace(')', '', $number);

        $search = ldap_search($this->ldapConnection, $searchDn, "(telephoneNumber=$number)");
        $searchCount = ldap_count_entries($this->ldapConnection, $search);

        if ($searchCount) {
            return;
        }


        // Add Entry
        try {
            ldap_add($this->ldapConnection, $dn, $contact);
            $this->newEntriesCount = $this->newEntriesCount + 1;
        } catch (Exception $ex) {
            Log::error($ex);
        }
    }

    protected function showInfo(): void
    {
        if (config('ldap.ignore-without-phone')) {
            $this->info("\n\nKontakte ohne Telefonnummer, daher nicht importiert: $this->noPhoneNumberCount");
            Log::info("Kontakte ohne Telefonnummer, daher nicht importiert: $this->noPhoneNumberCount");
        }

        $this->info("Telefonnummer bereits anderem Kontakt zugeordnet, daher nicht importiert: $this->multiplePhoneNumberCount");
        Log::info("Telefonnummer bereits anderem Kontakt zugeordnet, daher nicht importiert: $this->multiplePhoneNumberCount");


        $this->info("Neue Kontakte importiert: $this->newEntriesCount");
        Log::info("Neue Kontakte importiert: $this->newEntriesCount");

        $this->info("Bestehende Kontakte aktualisiert: $this->editEntriesCount");
        Log::info("Bestehende Kontakte aktualisiert: $this->editEntriesCount");


        $dn = 'ou='.config('ldap.ou').',dc='.config('ldap.dc');
        $filter = '(|(sn=*)(givenname=*))';

        $allContacts = ldap_search($this->ldapConnection, $dn, $filter);
        $allContactsCount = ldap_count_entries($this->ldapConnection, $allContacts);

        $this->info("Alle in LDAP gespeicherten Kontakte: $allContactsCount");
        Log::info("Alle in LDAP gespeicherten Kontakte: $allContactsCount");

        print("\n");
    }
}
