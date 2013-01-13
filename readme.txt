=== AvatarPlus ===

Contributors: Ralf Albert
Donate link: https://github.com/RalfAlbert/AvatarPlus
Tags: comments, avatar
Requires at least: 3.5
Tested up to: 3.5
Stable tag: 0.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

AvatarPlus allows users to use their profile image from Google+, Facebook or Twitter as avatar in the comment section of WordPress.
AvatarPlus requires PHP5.3 or greater! 

== Description ==

AvatarPlus erlaubt die Einbindung der Profilbilder von Google+, Facebook und Twitter in den Kommentarbereich. Da immer mehr Benutzer auf die Angabe einer E-Mail Adresse verzichten und stattdessen lieber nur die URL zu ihren Profil in einem sozialen Netzwerk angeben möchten, fehlt in WordPress die Möglichkeit den Kommentarbereich mit Avataren bzw. Profilbildern auszustatten. Hier springt AvatarPlus ein und ermöglicht es auf die zwingende Angabe einer E-Mail Adresse zu verzichten und dennoch ein Avatar bzw. Profilbild einzubinden.

AvatarPlus ist möglichst flexibel gestaltet und erlaubt es die Angabe einer Profil-URL entweder in einem zusätzlichen Eingabefeld oder im Standardeingabefeld für die Homepage entgegen zu nehmen. Zusätzlich ist AvatarPlus in der Lage Umleitungen zu erkennen und diese aufzulösen. Dadurch können Benutzer nicht nur die meist lange URL zu ihren Profilen in sozialen Netzwerken verwenden, sondern auch Kurz-URLs wie z.B. `http://www.example.org/+` als Kurz-URL zu einem Google+ Profil.

AvatarPlus geht möglichst schonend mit den Ressourcen um und speichert die URLs zu den Profilbildern (einfacher Caching Mechanismus). Dadurch wird die Anzahl an HTTP-Anfragen möglichst gering gehalten und eine schnelle Darstellung des Kommentarbereiches gewährleistet.

AvatarPlus ist nach den "Best Practices" für WordPress Plugins geschrieben und nutzt alle Möglichkeiten um hochwertigen PHP-Code zu gewährleisten.

== Installation ==

1. Install the plugin within the plugin page in the backend of your blog
2. If needed, adjust the settings under Settings - AvatarPlus in the backend of your blog

If you want to install the plugin manually:

1. Download AvatarPlus
2. Unpack the archive
3. Upload the unpacked archive folder to your plugins folder
4. Activate the plugin
5. If needed, adjust the settings under Settings - AvatarPlus in the backend of your blog

== Frequently Asked Questions ==

 - No known questions yet

== Screenshots ==

 - No screenshots available yet
 
== Changelog ==

= 0.2 =

* First public version

= 0.1 =

* First developer version

== Upgrade Notice ==

 - No upgrades available
 
== Arbitrary section ==

AvatarPlus use a simple caching mechanism. In some countries webmaster have to declare if the webpage stores personal data about the user. AvatarPlus stores these data which can maybe personal data:
 - The url which the users inserted into the comment form
 - The profile url of the social network profile if the url which the user inserted redirect to the profile url
 - The url of the profile image from the social network
 
 - These data will be stored until an expiration is set in the backend.
 - The expiration can diversify depending on the settings.
 - If there are any data to be deleted will be checked once a day.
 