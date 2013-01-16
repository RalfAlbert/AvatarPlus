# AvatarPlus #

**Contributors:** Ralf Albert
**Donate link:** https://github.com/RalfAlbert/AvatarPlus
**Tags:** comments, avatar
**Requires at least:** 3.5
**Tested up to:** 3.5
**Stable tag:** 0.2
**License:** GPLv3
**License URI:** http://www.gnu.org/licenses/gpl-3.0.html

AvatarPlus erlaubt es Benutzern ein Profilbild von einem der sozialen Netzwerke Google+, Facebook oder Twitter anstatt des Standardavatars von Gravatar zu verwenden.
AvatarPlus setzt zwingend PHP ab der Version 5.3 voraus!

## Beschreibung ##

AvatarPlus erlaubt die Einbindung der Profilbilder von Google+, Facebook und Twitter in den Kommentarbereich. Da immer mehr Benutzer auf die Angabe einer E-Mail Adresse verzichten und stattdessen lieber nur die URL zu ihren Profil in einem sozialen Netzwerk angeben möchten, fehlt in WordPress die Möglichkeit den Kommentarbereich mit Avataren bzw. Profilbildern auszustatten. Hier springt AvatarPlus ein und ermöglicht es auf die zwingende Angabe einer E-Mail Adresse zu verzichten und dennoch ein Avatar bzw. Profilbild einzubinden.

AvatarPlus ist möglichst flexibel gestaltet und erlaubt es die Angabe einer Profil-URL entweder in einem zusätzlichen Eingabefeld oder im Standardeingabefeld für die Homepage entgegen zu nehmen. Zusätzlich ist AvatarPlus in der Lage Umleitungen zu erkennen und diese aufzulösen. Dadurch können Benutzer nicht nur die meist lange URL zu ihren Profilen in sozialen Netzwerken verwenden, sondern auch Kurz-URLs wie z.B. `http://www.example.org/+` als Kurz-URL zu einem Google+ Profil.

AvatarPlus geht möglichst schonend mit den Ressourcen um und speichert die URLs zu den Profilbildern (einfacher Caching Mechanismus). Dadurch wird die Anzahl an HTTP-Anfragen möglichst gering gehalten und eine schnelle Darstellung des Kommentarbereiches gewährleistet.

AvatarPlus ist nach den "Best Practices" für WordPress Plugins geschrieben und nutzt alle Möglichkeiten um hochwertigen PHP-Code zu gewährleisten.

## Installation ##

1. Installieren und aktivieren Sie AvatarPlus über den WordPress Admin-Bereich
2. Nehmen Sie ggf. die Einstellungen für AvatarPlus im Admin-Bereich unter den Menüpunkt Einstellungen - AvatarPlus vor

Alternativ können Sie das Plugin auch manuell installieren:

1. Laden Sie AvatarPlus herunter
2. Entpacken Sie das Archiv
3. Laden Sie das entpackte Archiv in ihr Plugin-Verzeichnis des Blogs hoch
4. Aktivieren Sie das Plugin im Admin-Bereich des Blogs
5. Nehmen Sie ggf. die Einstellungen für AvatarPlus im Admin-Bereich unter den Menüpunkt Einstellungen - AvatarPlus vor

## Regelmäßig gestellte Fragen ##

 - Bisher sind keine Fragen bekannt.

## Screenshots ##


## Changelog ##

### 0.2 ###

* Erste öffentliche Version

### 0.1 ###

* erste Entwicklerversion

## Upgrade Notice ##


## Zusätzlicher Abschnitt ##

**Datenschutzhinweis**

AvatarPlus nutzt einen einfachen Caching Mechanismus (Zwischenspeicher). In einigen Ländern sind Webseitenbetreiber dazu verpflichtet ihre Besucher über die Speicherung von Daten zu informieren. AvatarPlus speichert folgende Daten die ggf. Personenbezogen sein können:

 - Die URL (Unique Ressource Location) die der Benutzer eingegeben hat
 - Die Profil-URL des sozialen Netzwerkes sofern die angegebene URL auf diese umleitet
 - Die URL des Profilbildes des entsprechenden sozialen Netzwerkes

 - Die Prüfung ob zwischengespeicherte Daten zur Löschung bereit stehen erfolgt einmal täglich
 - Das minimale Alter von zwischengespeicherten Daten die gelöscht werden sollen, wird im Admin-Bereich festgelegt und kann variieren