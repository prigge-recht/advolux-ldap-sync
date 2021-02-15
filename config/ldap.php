<?php

return [

    /**
     * Adresse des LDAP-Servers
     */
    'server' => 'ldap://localhost:389',

    /**
     * Login des ausführenden LDAP-Benutzers
     */
    'login-cn' => 'admin',

    /**
     * Passwort des ausführenden LDAP-Benutzers
     */
    'login-password' => 'password',

    /**
     * Domain Controller der LDAP-Datenbank
     */
    'dc' => 'nodomain',

    /**
     * Ordner, in denen Kontakte gespeichert werden sollen
     */
    'ou' => 'Kontakte',

    /**
     * Wenn true: Kontakte ohne Telefonnummer(n) werden nicht synchronisiert
     */
    'ignore-without-phone' => true

];
