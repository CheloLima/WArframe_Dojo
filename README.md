# Endo Reserve Bank Community-Plattform

Willkommen bei der Endo Reserve Bank Community-Plattform! Dieses Projekt stellt eine Webseite für den Warframe-Clan "Endo Reserve Bank" bereit, inklusive eines Mitgliederbereichs und eines Discord-Verifizierungs-Bots.
Die Plattform bietet Funktionen wie Benutzerprofile (mit Discord-Avatar-Synchronisierung), eine Mitgliederliste, ein überarbeitetes MOTD-System (mit Verlauf, Entwurfsmodus und Admin-Verwaltung), einen Plattform-Changelog, ein System für Custom Titles, einen Build-Kosten-Rechner und ein Admin-Panel. <!-- Erweitert -->

## Inhaltsverzeichnis

1.  [Teil 1: Installation der PHP Web-Anwendung auf Plesk](#teil-1-installation-der-php-web-anwendung-auf-plesk)
    *   [Vorbereitung](#vorbereitung-php)
    *   [Dateien hochladen](#dateien-hochladen)
    *   [Datenbank einrichten](#datenbank-einrichten-plesk)
    *   [Konfiguration (PHP)](#konfiguration-php)
    *   [Discord App einrichten](#discord-app-einrichten)
2.  [Teil 2: Installation des Python-Bots auf einem Linux-Server (VPS)](#teil-2-installation-des-python-bots-auf-einem-linux-server-vps)
    *   [Voraussetzungen (Python-Bot)](#voraussetzungen-python-bot)
    *   [Code herunterladen (Python-Bot)](#code-herunterladen-python-bot)
    *   [Python-Umgebung einrichten](#python-umgebung-einrichten)
    *   [Abhängigkeiten installieren (Python-Bot)](#abhängigkeiten-installieren-python-bot)
    *   [Konfiguration (Python-Bot)](#konfiguration-python-bot)
    *   [Bot starten](#bot-starten)
    *   [Als Service einrichten (systemd)](#als-service-einrichten-systemd)
3.  [Zusammenfassung aller IDs & Daten](#zusammenfassung-aller-ids--daten)

---

## Teil 1: Installation der PHP Web-Anwendung auf Plesk

Dieser Abschnitt beschreibt die Schritte zur Installation der PHP-basierten Web-Anwendung auf einem Plesk-Webhosting-Server.

### Vorbereitung (PHP)

Bevor du mit der Installation beginnst, stelle sicher, dass du folgende Informationen und Zugänge griffbereit hast:

*   **Domain-Name:** Die Domain, auf der die Plattform laufen soll (z.B. `dojo.chelo.lat`).
*   **Plesk-Zugang:** Zugangsdaten zu deinem Plesk Panel.
*   **FTP-Zugang (optional):** Zugangsdaten für einen FTP-Client wie FileZilla, falls du Dateien nicht über den Plesk File Manager hochladen möchtest.
*   **MySQL-Datenbank Zugangsdaten:**
    *   **Datenbank-Host:** Meist `localhost` oder `127.0.0.1`. Kann bei manchen Hostern abweichen (z.B. `mysql.deinedomain.tld`). Notiere dir den Wert, den Plesk anzeigt.
    *   **Datenbank-Name:** Wähle einen Namen für die Datenbank (z.B. `chelo_prime_dojo` oder `usr_webXXX_1`).
    *   **Datenbank-Benutzername:** Ein Benutzername für den Datenbankzugriff (z.B. `chelo_dojo_user` oder `webXXX_1`).
    *   **Datenbank-Passwort:** Ein starkes Passwort für den Datenbankbenutzer.
    *(Diese Daten wirst du im Schritt "Datenbank einrichten" erstellen bzw. festlegen.)*
*   **Discord Application Credentials:**
    *   **Client ID:** Erhältst du vom Discord Developer Portal.
    *   **Client Secret:** Erhältst du vom Discord Developer Portal.
    *(Diese Daten wirst du im Schritt "Discord App einrichten" erstellen.)*
*   **URL zum Python Verifizierungs-Bot:** Die vollständige öffentliche URL, unter der dein separat installierter Python-Bot erreichbar sein wird (z.B. `http://DEINE_BOT_SERVER_IP:8000/verify-user/` oder `https://bot.deinedomain.tld/verify-user/`).
*   **API Secret Key für den Python-Bot:** Ein starkes, geheimes Passwort, das du selbst wählst. Es dient zur Absicherung der Kommunikation zwischen der PHP-Anwendung und dem Python-Bot. **Dieser Key muss in der PHP-Konfiguration und in der Bot-Konfiguration identisch sein!**

**Benötigte PHP-Erweiterungen:**
Stelle sicher, dass auf deinem Plesk-Webspace die folgenden PHP-Erweiterungen aktiviert sind (normalerweise Standard):
*   `pdo_mysql` (für Datenbankverbindungen mit PDO) oder `mysqli` (falls du dich entscheidest, die `db.php` entsprechend anzupassen)
*   `curl` (für HTTP-Anfragen an Discord und den Python-Bot)
*   `json` (für die Verarbeitung von JSON-Daten)
*   `mbstring` (für korrekte Zeichenkettenverarbeitung)
Du kannst dies in den PHP-Einstellungen deiner Domain im Plesk Panel überprüfen und ggf. aktivieren.

### Dateien hochladen

1.  **Code herunterladen/vorbereiten:**
    Stelle sicher, dass du alle Projektdateien (den Inhalt dieses Git-Repositorys oder des bereitgestellten ZIP-Archivs) auf deinem lokalen Computer hast.
2.  **Verzeichnis `web_app`:**
    Die für die Web-Anwendung relevanten Dateien befinden sich im Ordner `web_app/`.
3.  **Hochladen auf den Server:**
    Lade **alle Inhalte** des Ordners `web_app/` (also alle darin enthaltenen Dateien und Unterordner) in das Stammverzeichnis (Web Root) deiner Zieldomain auf deinem Plesk-Server. Dieses Verzeichnis heißt üblicherweise:
    *   `httpdocs/`
    *   `public_html/`
    *   Oder ein anderer Name, den dein Hoster für das Web-Stammverzeichnis verwendet.

    Du kannst hierfür verwenden:
    *   **Plesk File Manager:** Logge dich in Plesk ein, navigiere zum "File Manager" deiner Domain und lade die Dateien und Ordner hoch (oft als ZIP-Datei, die du dann auf dem Server entpackst).
    *   **FTP-Client (z.B. FileZilla):** Verbinde dich per FTP mit deinem Server und übertrage die Dateien.

    Nach dem Hochladen sollte die Struktur in deinem Web-Stammverzeichnis auf dem Server wie folgt aussehen:

    ```
    / (z.B. httpdocs/)
    ├── assets/                 (Ordner für Bilder wie discord_logo.svg, platinum.png, default_avatar.png)
    │   └── images/
    │       ├── discord_logo.svg  (Du musst dieses Logo ggf. selbst hinzufügen)
    │       ├── default_avatar.png (Ein Platzhalter-Avatar)
    │       └── platinum.png       (Ein Platin-Icon)
    ├── css/
    │   └── style.css
    ├── includes/
    │   ├── db.php
    │   ├── footer.php
    │   ├── header.php
    │   └── user_functions.php
    ├── admin.php
    ├── callback.php
    ├── config.example.php      (Wird zu config.php kopiert und angepasst)
    ├── dashboard.php
    ├── error_no_role.php
    ├── error_not_on_server.php
    ├── index.php
    ├── logout.php
    ├── mitglieder.php
    ├── profil-vervollstaendigen.php
    ├── profil.php
    ├── rechner.php
    └── schema.sql              (Wird für den Datenbank-Import benötigt)
    ```
    **Wichtig:** Die Datei `config.example.php` ist eine Vorlage. Du wirst sie später in `config.php` umbenennen/kopieren und deine spezifischen Daten eintragen. Die Datei `schema.sql` wird für die Erstellung der Datenbankstruktur benötigt.
    Die Bilder im `assets/images/` Ordner (`discord_logo.svg`, `default_avatar.png`, `platinum.png`) sollten idealerweise von dir bereitgestellt werden. Falls sie fehlen, wird die Seite trotzdem funktionieren, aber Icons/Bilder könnten fehlen.

### Datenbank einrichten (Plesk)

Die Web-Anwendung benötigt eine MySQL-Datenbank, um Benutzerdaten, MOTD, Kommentare etc. zu speichern.

1.  **Neue Datenbank in Plesk erstellen:**
    *   Logge dich in dein Plesk Panel ein.
    *   Navigiere im Menü zu **"Websites & Domains"** und wähle die Domain aus, auf der du die Plattform installierst.
    *   Klicke auf der rechten Seite oder im Dashboard der Domain auf **"Datenbanken"**.
    *   Klicke auf den Button **"Datenbank hinzufügen"**.
    *   **Datenbankname:** Gib einen Namen für deine Datenbank ein (z.B. `endo_reserve_bank_db` oder den von Plesk vorgeschlagenen Namen wie `webXX_db1`). Notiere dir diesen Namen. <!-- Angepasst -->
    *   **Zugehörige Website:** Stelle sicher, dass die korrekte Domain ausgewählt ist.
    *   **Datenbankserver:** Wähle den MySQL-Server aus (meist ist nur einer verfügbar, z.B. `localhost:3306`).
    *   **Benutzer erstellen:**
        *   Aktiviere die Option **"Datenbankbenutzer erstellen"**.
        *   **Datenbankbenutzername:** Gib einen Benutzernamen ein (z.B. `endo_bank_user` oder den von Plesk vorgeschlagenen Namen). Notiere dir diesen Namen. <!-- Angepasst -->
        *   **Passwort:** Generiere ein starkes Passwort oder gib ein eigenes ein. **Notiere dir dieses Passwort sicher!**
    *   **Benutzer hat Zugriff auf alle Datenbanken innerhalb des ausgewählten Abonnements (optional):** Diese Option ist meist nicht notwendig und sollte für bessere Sicherheit deaktiviert bleiben, es sei denn, du hast einen spezifischen Grund dafür. Der Benutzer sollte nur Zugriff auf die gerade erstellte Datenbank haben. Plesk konfiguriert dies normalerweise korrekt.
    *   Klicke auf **"OK"**, um die Datenbank und den Benutzer zu erstellen.

2.  **`schema.sql` importieren über phpMyAdmin:**
    Nachdem die Datenbank erstellt wurde, musst du die Tabellenstruktur importieren.
    *   Du befindest dich nun wieder in der Datenbankübersicht in Plesk.
    *   Suche deine neu erstellte Datenbank in der Liste.
    *   Klicke in der Zeile deiner Datenbank auf den Link **"phpMyAdmin"** (oder manchmal "Webadmin"). Ein neues Fenster/Tab mit phpMyAdmin öffnet sich.
    *   In phpMyAdmin:
        *   Stelle sicher, dass in der linken Navigationsleiste deine neu erstellte Datenbank ausgewählt ist (klicke ggf. darauf).
        *   Klicke oben im Menü auf den Tab **"Importieren"**.
        *   Im Abschnitt **"Zu importierende Datei"**:
            *   Klicke auf den Button **"Datei auswählen"** (oder "Durchsuchen...").
            *   Navigiere zu dem Ort, an dem du die Projektdateien gespeichert hast (lokal) und wähle die Datei `schema.sql` aus dem `web_app/`-Verzeichnis aus.
            *   **Wichtig:** Wenn du die `schema.sql` bereits mit den anderen Dateien auf den Server hochgeladen hast, könntest du alternativ den Pfad zur Datei auf dem Server angeben, aber der Upload über "Datei auswählen" ist meist einfacher.
        *   **Zeichensatz der Datei:** Sollte auf `utf-8` stehen (ist Standard in `schema.sql`).
        *   Alle anderen Einstellungen können normalerweise auf ihren Standardwerten belassen werden.
        *   Scrolle nach unten und klicke auf den Button **"Importieren"** (oder "OK" / "Go").
    *   Wenn der Import erfolgreich war, siehst du eine Erfolgsmeldung und in der linken Navigationsleiste unter deiner Datenbank die neu erstellten Tabellen (`users`, `motd`, `custom_titles`, `profile_comments`, `user_syndicates`).

    Deine Datenbank ist nun eingerichtet und bereit für die Konfiguration der PHP-Anwendung.

    **Für bestehende Installationen (Updates für MOTD-System):**
    Falls du von einer früheren Version aktualisierst, die das neue MOTD-System mit Verlauf noch nicht hatte, führe bitte folgende SQL-Befehle in phpMyAdmin aus:
    ```sql
    -- Benenne die alte 'motd' Tabelle um, falls sie existiert (als Backup)
    RENAME TABLE IF EXISTS `motd` TO `motd_old_backup`;

    -- Erstelle die neue 'motds' Tabelle (Struktur siehe aktuelle schema.sql)
    CREATE TABLE `motds` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title` VARCHAR(255) DEFAULT NULL,
      `content` TEXT NOT NULL,
      `created_by_discord_id` VARCHAR(255) NOT NULL,
      `is_published` BOOLEAN DEFAULT TRUE,
      `version` VARCHAR(50) DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`created_by_discord_id`) REFERENCES `users`(`discord_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    -- Optional: Migriere die letzte MOTD aus dem Backup, falls gewünscht
    -- INSERT INTO `motds` (title, content, created_by_discord_id, created_at, updated_at)
    -- SELECT NULL, content, created_by_discord_id, created_at, updated_at FROM `motd_old_backup` ORDER BY updated_at DESC LIMIT 1;
    ```
    Alternativ, wenn du die `motd` Tabelle bereits hattest und nur erweitern willst (Daten bleiben erhalten):
    ```sql
    ALTER TABLE `motd` RENAME TO `motds`;
    ALTER TABLE `motds`
      ADD COLUMN `title` VARCHAR(255) DEFAULT NULL AFTER `id`,
      ADD COLUMN `is_published` BOOLEAN DEFAULT TRUE AFTER `created_by_discord_id`,
      ADD COLUMN `version` VARCHAR(50) DEFAULT NULL AFTER `is_published`;
    ```
    Wähle den für dich passenden Ansatz. Die erste Methode (RENAME TABLE, CREATE TABLE) ist sauberer, wenn die alte MOTD nicht unbedingt migriert werden muss. Die zweite Methode erhält die Historie, falls die Tabelle schon `motd` hieß und nicht `motds`. Da wir von `motd` zu `motds` wechseln, ist die RENAME+ALTER-Variante besser.

    **Für das Changelog-System (ab Version 6.9-Delta):**
    Führe bitte folgenden SQL-Befehl aus, um die `changelog`-Tabelle zu erstellen:
    ```sql
    CREATE TABLE `changelog` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `version_tag` VARCHAR(50) NOT NULL,
      `summary` TEXT NOT NULL,
      `created_by_discord_id` VARCHAR(255) NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`created_by_discord_id`) REFERENCES `users`(`discord_id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

    -- Beispiel-Einträge (optional):
    INSERT INTO `changelog` (`version_tag`, `summary`, `created_by_discord_id`, `created_at`) VALUES
    ('6.9-Alpha', 'Initialer Launch der Endo Reserve Bank Plattform.', 'DEINE_ADMIN_DISCORD_ID', NOW()),
    ('6.9-Delta', '- Footer-Text angepasst.\n- MOTD-System überarbeitet (Verlauf, Entwürfe, Löschen).\n- Button-Lesbarkeit für helle Themen verbessert.\n- Dynamische Akzentfarben für mehr UI-Elemente.', 'DEINE_ADMIN_DISCORD_ID', NOW());
    ```
    Ersetze `DEINE_ADMIN_DISCORD_ID` mit der tatsächlichen Discord-ID eines Admin-Benutzers.

### Konfiguration (PHP)

Die PHP-Anwendung benötigt eine Konfigurationsdatei (`config.php`), um Datenbankdetails, Discord-API-Schlüssel und andere Einstellungen zu speichern.

1.  **`config.php` erstellen:**
    *   Navigiere im Plesk File Manager (oder per FTP) zum Stammverzeichnis deiner Domain (z.B. `httpdocs/`), wo du die Projektdateien hochgeladen hast.
    *   Dort findest du die Datei `config.example.php`.
    *   Kopiere diese Datei und benenne die Kopie in `config.php` um.
        *   Im Plesk File Manager: Wähle `config.example.php` aus, klicke auf "Kopieren", gib als Ziel `config.php` im selben Verzeichnis an.
        *   Per FTP: Lade `config.example.php` herunter, benenne sie lokal in `config.php` um und lade sie wieder hoch. Oder nutze die Umbenennungsfunktion deines FTP-Clients, nachdem du sie kopiert hast.

2.  **`config.php` anpassen:**
    *   Öffne die neu erstellte Datei `config.php` mit dem Texteditor im Plesk File Manager (Rechtsklick -> "Als Text bearbeiten") oder lade sie herunter, bearbeite sie lokal und lade sie erneut hoch.
    *   Trage deine spezifischen Werte für die folgenden PHP-Konstanten ein. **Ersetze die Platzhalter-Werte!**

    ```php
    <?php

    // --- Datenbank Konfiguration ---
    define('DB_HOST', 'localhost'); // Dein Datenbank-Host (z.B. 'localhost' oder von Plesk angegeben)
    define('DB_NAME', 'dein_db_name');    // Der Name deiner Datenbank
    define('DB_USER', 'dein_db_benutzer');    // Dein Datenbankbenutzername
    define('DB_PASS', 'DEIN_MYSQL_PASSWORT'); // Dein Datenbankpasswort
    define('DB_CHARSET', 'utf8mb4');

    // --- Discord OAuth2 Konfiguration ---
    define('DISCORD_CLIENT_ID', 'DEINE_DISCORD_CLIENT_ID'); // Deine Discord App Client ID (aus dem Discord Developer Portal)
    define('DISCORD_CLIENT_SECRET', 'DEIN_DISCORD_CLIENT_SECRET'); // Dein Discord App Client Secret (aus dem Discord Developer Portal)
    define('DISCORD_REDIRECT_URI', 'httpsS://deinedomain.tld/callback.php'); // WICHTIG: Ersetze 'deinedomain.tld' durch deine tatsächliche Domain! Muss exakt mit der im Discord Developer Portal eingetragenen URI übereinstimmen. Achte auf httpS, falls du SSL nutzt.

    // --- Python Verifizierungs-Bot Konfiguration ---
    // Die vollständige URL zum /verify-user/ Endpunkt deines Python Bots
    // Beispiel: 'http://123.45.67.89:8000/verify-user/' oder 'httpsS://bot.deinedomain.tld/verify-user/'
    define('VERIFY_BOT_URL', 'http://IP_DEINES_BOT_SERVERS:PORT/verify-user/');

    // Das Secret API Key, das sowohl hier als auch in der .env Datei des Python Bots identisch sein muss.
    define('BOT_API_SECRET_KEY', 'DEIN_STARKES_GEHEIMES_API_KEY_DAS_DU_GEWAEHLT_HAST');


    // --- Allgemeine Seiteneinstellungen ---
    define('SITE_NAME', 'Endo Reserve Bank - Warframe Clan DE'); // Angepasst an neue Vorgabe
    define('BASE_URL', 'httpsS://dojo.chelo.lat'); // Beispiel-URL, anpassen!

    // --- Fehlerbehandlung (für Entwicklung true, für Produktion false) ---
    define('DEBUG_MODE', true); // Für Entwicklung true, für Produktion false

    if (DEBUG_MODE) {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    } else {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(0);
    }

    // --- Session Einstellungen ---
    define('SESSION_NAME', 'ENDORESERVERBANKSESSID'); // Angepasst an neue Vorgabe
    ini_set('session.name', SESSION_NAME);

    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
       ini_set('session.cookie_secure', 1);
    }
    // ... (weitere Konstanten wie SUPER_ADMIN_DISCORD_ID etc. bleiben wie in der Datei config.example.php)
    ?>
    ```
    **Wichtige Hinweise zur `config.php`:**
    *   **`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`:** Trage hier die Daten ein, die du beim Erstellen der Datenbank in Plesk erhalten/festgelegt hast (Beispielwerte: `localhost`, `Warframe_Dojo`, `Warframe_Dojo`, `76~Ktx4d8` - **ÄNDERN!**).
    *   **`DISCORD_CLIENT_ID`, `DISCORD_CLIENT_SECRET`:** Diese erhältst du im nächsten Schritt ("Discord App einrichten") (Beispielwerte: `1392357117142372436`, `YERKzJLoMyv6r2kRTWRhdXjoRBE8in9m` - **ÄNDERN!**).
    *   **`DISCORD_REDIRECT_URI`:** Muss **exakt** mit der URL übereinstimmen, die auch in deiner `config.php` unter `DISCORD_REDIRECT_URI` steht (Beispiel: `https://dojo.chelo.lat/callback.php` - Domain anpassen!).
    *   **`VERIFY_BOT_URL`:** Die öffentliche URL deines Python Verifizierungs-Bots (Beispiel: `http://45.13.225.40:8000/verify-user/` - anpassen!).
    *   **`BOT_API_SECRET_KEY`:** Das selbstgewählte, starke Passwort, das auch der Python-Bot in seiner `.env`-Datei verwendet (Beispiel: `CheloLima_Security_Dojo69420` - **ÄNDERN!**).
    *   **`SITE_NAME`**: Name deiner Webseite (Beispiel: `Endo Reserve Bank - Warframe Clan DE`).
    *   **`BASE_URL`:** Die Haupt-URL deiner Webseite (Beispiel: `https://dojo.chelo.lat` - Domain anpassen!). Ohne Slash am Ende.
    *   **`DEBUG_MODE`:** Setze dies für den Live-Betrieb unbedingt auf `false`.
    *   **`SESSION_NAME`**: Name für die PHP Session (Beispiel: `ENDORESERVERBANKSESSID`).

    Speichere die `config.php` nach dem Anpassen.

### Discord App einrichten

Damit sich Benutzer über Discord einloggen können, musst du eine "Application" im Discord Developer Portal erstellen.

1.  **Gehe zum Discord Developer Portal:**
    Öffne [https://discord.com/developers/applications](https://discord.com/developers/applications) in deinem Browser und logge dich ggf. mit deinem Discord-Account ein.

2.  **Neue Applikation erstellen:**
    *   Klicke oben rechts auf den Button **"New Application"**.
    *   Gib deiner App einen Namen (z.B. "Endo Reserve Bank Login" oder "Meine Clan Webseite"). Dieser Name wird den Benutzern beim Login angezeigt. <!-- Angepasst -->
    *   Akzeptiere die Discord Developer Terms of Service und klicke auf **"Create"**.

3.  **Allgemeine Informationen (optional):**
    *   Du kannst deiner App eine Beschreibung und ein Icon hinzufügen, dies ist aber für die Funktionalität nicht zwingend erforderlich.

4.  **OAuth2-Einstellungen konfigurieren:**
    *   Klicke im linken Menü deiner App auf **"OAuth2"** -> **"General"**.
    *   **Client Information:**
        *   Hier findest du deine **"CLIENT ID"**. Kopiere diese und füge sie in deine `config.php` bei `DISCORD_CLIENT_ID` ein.
        *   Unter **"CLIENT SECRET"** klicke auf **"Reset Secret"** (oder "View Secret", falls schon vorhanden). Bestätige ggf. mit deinem Discord-Passwort oder 2FA. Kopiere das angezeigte Secret **sofort** und füge es in deine `config.php` bei `DISCORD_CLIENT_SECRET` ein. **Dieses Secret wird nur einmal angezeigt! Speichere es sicher.**
    *   **Redirects:**
        *   Scrolle nach unten zum Abschnitt "Redirects".
        *   Klicke auf **"Add Redirect"**.
        *   Trage hier **exakt** die URL ein, die auch in deiner `config.php` unter `DISCORD_REDIRECT_URI` steht. Für die Domain `dojo.chelo.lat` wäre das:
            ```
            httpsS://dojo.chelo.lat/callback.php
            ```
            (Achte genau auf `http` vs. `https` und ob deine Domain mit `www.` oder ohne läuft. Es muss exakt übereinstimmen!)
        *   Klicke auf **"Save Changes"** unten auf der Seite.

5.  **Notwendige Scopes:**
    Die Anwendung verwendet die Scopes `identify` (um Basis-Benutzerinfos zu erhalten) und `guilds.members.read` (damit der Bot später Rollen auf dem Server prüfen kann, falls der Bot selbst diese Berechtigung benötigt und die PHP-Anwendung diese Info an den Bot weitergibt - für diese spezielle Implementierung wird `guilds.members.read` primär vom Bot verwendet, aber es schadet nicht, wenn die OAuth-App es auch anfragt, falls zukünftige Erweiterungen es benötigen). Diese Scopes werden im PHP-Code (`index.php` beim Generieren der OAuth-URL) bereits angefordert. Du musst hier im Developer Portal in der Regel nichts weiter für die Scopes einstellen, solange sie von der App angefordert werden.

    Deine Discord App ist nun für den OAuth2-Login eingerichtet. Die `Client ID` und das `Client Secret` in deiner `config.php` ermöglichen der PHP-Anwendung, mit Discord zu kommunizieren.

### Farbschema anpassen (Optional)

Die Webseite verfügt über eine Farbauswahl im Footer-Bereich. Benutzer können dort aus verschiedenen vordefinierten Akzentfarben wählen, um das Erscheinungsbild der Seite anzupassen. Die Auswahl wird lokal im Browser des Benutzers gespeichert.

---

## Teil 2: Installation des Python-Bots auf einem Linux-Server (VPS)

Dieser Abschnitt führt dich durch die Installation des Python-basierten Verifizierungs-Bots auf deinem eigenen Linux-Server (z.B. einem Virtual Private Server).

### Voraussetzungen (Python-Bot)

Stelle sicher, dass die folgenden Pakete auf deinem Server installiert sind. Für Debian/Ubuntu-basierte Systeme:

```bash
sudo apt update
sudo apt install python3 python3-pip python3-venv git -y
```
Diese Befehle aktualisieren deine Paketliste und installieren Python 3, den Paketmanager pip, das Modul für virtuelle Umgebungen (`venv`) und Git.

### Code herunterladen (Python-Bot)

1.  **Erstelle ein Verzeichnis für den Bot und navigiere hinein:**
    Es ist eine gute Praxis, Projekte in eigenen Verzeichnissen zu organisieren. Du kannst dies z.B. im Home-Verzeichnis deines Benutzers tun.
    ```bash
    mkdir ~/echosoldojo_platform
    cd ~/echosoldojo_platform
    ```
2.  **Klone das Repository (oder lade die Dateien hoch):**
    Wenn das Projekt in einem Git-Repository verfügbar ist (ersetze `<repository_url>` mit der tatsächlichen URL):
    ```bash
    git clone <repository_url>
    cd <repository_directory_name> # Dies ist der Name des Ordners, der durch git clone erstellt wird
    ```
    Stelle sicher, dass du dich danach im Hauptverzeichnis des Projekts befindest, wo die `discord_bot` und `web_app` Ordner liegen. Die Bot-spezifischen Dateien sind dann in `discord_bot/`.

    **Alternative (Manueller Upload):**
    Wenn du die Dateien manuell (z.B. per SCP oder SFTP) hochlädst:
    *   Erstelle auf deinem Server das Verzeichnis: `mkdir -p ~/endoreservebank_platform/discord_bot` <!-- Angepasst -->
    *   Lade alle Inhalte des lokalen `discord_bot`-Ordners (also `main.py`, `requirements.txt`, `.env.example`, `endoreservebank_bot.service`) in das Verzeichnis `~/endoreservebank_platform/discord_bot` auf dem Server. <!-- Angepasst -->
    *   Navigiere in das Bot-Verzeichnis auf dem Server:
        ```bash
        cd ~/endoreservebank_platform/discord_bot <!-- Angepasst -->
        ```
    Für die weiteren Schritte gehen wir davon aus, dass du dich im Verzeichnis `discord_bot` befindest, das alle Bot-Dateien enthält.

### Python-Umgebung einrichten

Es wird dringend empfohlen, eine virtuelle Umgebung (venv) zu verwenden, um Abhängigkeiten des Projekts von anderen Python-Projekten auf dem System zu isolieren.

```bash
# Stelle sicher, dass du im 'discord_bot' Verzeichnis bist
python3 -m venv venv
```
Dieser Befehl erstellt ein Unterverzeichnis namens `venv` im aktuellen Verzeichnis (`discord_bot`), das die Python-Installation für dieses Projekt enthält.

Aktiviere die virtuelle Umgebung:
```bash
source venv/bin/activate
```
Dein Kommandozeilen-Prompt sollte sich nun ändern und `(venv)` am Anfang anzeigen, was darauf hinweist, dass die virtuelle Umgebung aktiv ist. Alle `pip install`-Befehle werden nun Pakete in dieser Umgebung installieren.

### Abhängigkeiten installieren (Python-Bot)

Installiere die benötigten Python-Bibliotheken, die in `requirements.txt` aufgelistet sind:
```bash
# Stelle sicher, dass die venv aktiv ist und du im 'discord_bot' Verzeichnis bist
pip install -r requirements.txt
```
Dieser Befehl liest die Datei `requirements.txt` und installiert die dort spezifizierten Versionen von FastAPI, Uvicorn, python-dotenv und discord.py.

### Konfiguration (Python-Bot)

Der Bot benötigt einige Konfigurationswerte, um korrekt zu funktionieren. Diese werden über eine `.env`-Datei bereitgestellt.

1.  **`.env`-Datei erstellen:**
    *   Im `discord_bot`-Verzeichnis befindet sich eine Vorlagedatei namens `.env.example`. Kopiere diese, um deine eigene Konfigurationsdatei zu erstellen:
        ```bash
        cp .env.example .env
        ```
2.  **`.env`-Datei anpassen:**
    *   Öffne die neu erstellte `.env`-Datei mit einem Texteditor (z.B. `nano .env` oder `vim .env`).
    *   Trage die folgenden Werte ein und ersetze die Platzhalter:

        ```dotenv
        # .env Datei für den Discord Bot

        # Discord Bot Token
        # Erhältlich aus dem Discord Developer Portal -> Deine App -> Bot -> "Reset Token" oder "View Token"
        DISCORD_BOT_TOKEN="DEIN_DISCORD_BOT_TOKEN_HIER_EINFUEGEN"

        # API Secret Key
        # Ein starkes, zufälliges Passwort/Secret, das du selbst wählst.
        # Dieses Secret muss identisch sein mit dem Wert von BOT_API_SECRET_KEY in der web_app/config.php.
        API_SECRET_KEY="DEIN_STARKES_GEHEIMES_API_KEY_HIER_EINFUEGEN"

        # Discord Server (Guild) ID
        # Die ID des Discord-Servers, auf dem die Mitgliedschaft und Rolle geprüft werden soll.
        GUILD_ID="1212768310312042566" # Voreingestellt für Endo Reserve Bank (Server ID bleibt gleich)

        # Discord Rollen ID
        # Die ID der Rolle, die für eine erfolgreiche Verifizierung erforderlich ist.
        ROLE_ID="1391269160994209873" # Voreingestellt für Endo Reserve Bank (Rollen ID bleibt gleich)
        ```
    *   Speichere die Datei und schließe den Editor. (Für `nano`: `Ctrl+X`, dann `Y`, dann `Enter`).

    **Wichtig:**
    *   Der `DISCORD_BOT_TOKEN` ist sehr sensibel. Behandle ihn wie ein Passwort.
    *   Der `API_SECRET_KEY` muss exakt derselbe sein, den du später in der `config.php` der Web-Anwendung eintragen wirst. Wähle hierfür eine lange, zufällige Zeichenkette.

### Bot starten (Testlauf)

Bevor du den Bot als dauerhaften Service einrichtest, starte ihn testweise, um sicherzustellen, dass alles korrekt konfiguriert ist.

Stelle sicher, dass:
1.  Du dich im `discord_bot`-Verzeichnis befindest.
2.  Die virtuelle Umgebung `(venv)` aktiviert ist (`source venv/bin/activate`).
3.  Die `.env`-Datei korrekt ausgefüllt ist.

Starte den Bot mit Uvicorn:
```bash
uvicorn main:app --host 0.0.0.0 --port 8000 --reload
```
*   `main:app`: Weist Uvicorn an, das Objekt `app` (deine FastAPI-Instanz) in der Datei `main.py` zu laden.
*   `--host 0.0.0.0`: Lässt den Server auf allen verfügbaren Netzwerkschnittstellen lauschen (wichtig, damit er von außen erreichbar ist).
*   `--port 8000`: Gibt den Port an, auf dem der Server lauschen soll. Du kannst einen anderen Port wählen, falls 8000 belegt ist (stelle sicher, dass er in deiner Server-Firewall freigegeben ist).
*   `--reload`: (Optional, für die Entwicklung) Startet den Server automatisch neu, wenn Code-Änderungen erkannt werden. Für einen Produktionseinsatz später im systemd-Service wird dies nicht benötigt.

Du solltest nun Ausgaben in der Konsole sehen, die anzeigen, dass Uvicorn gestartet ist und der Discord-Bot versucht, sich zu verbinden. Achte auf Meldungen wie:
`INFO:     Uvicorn running on http://0.0.0.0:8000 (Press CTRL+C to quit)`
`INFO:     Started reloader process [xxxxx] using StatReload`
`INFO:     Started server process [xxxxx]`
`INFO:     Waiting for application startup.`
`FastAPI Startup: Versuche Discord Bot zu starten...`
`[DeinBotName] ist jetzt mit Discord verbunden und bereit!`
`INFO:     Application startup complete.`

**Testen der API:**
Öffne einen Browser oder ein Tool wie Postman/curl und versuche, den Endpunkt aufzurufen (ersetze `<DEINE_SERVER_IP>` und eine Test-Discord-ID):
`http://<DEINE_SERVER_IP>:8000/verify-user/123456789012345678`
Du solltest eine Fehlermeldung bezüglich des fehlenden API-Keys erhalten (`{"detail":"Not authenticated"}` oder ähnlich), was zeigt, dass der Sicherheitsteil funktioniert.
Mit dem korrekten Header (z.B. per curl):
`curl -H "Authorization: Bearer DEIN_API_SECRET_KEY" http://<DEINE_SERVER_IP>:8000/verify-user/DEINE_DISCORD_ID`

Wenn alles funktioniert, beende den Testlauf mit `Ctrl+C`.

### Als Service einrichten (systemd)

Damit der Bot dauerhaft im Hintergrund läuft und automatisch nach einem Serverneustart gestartet wird, richtest du einen systemd-Service ein.

1.  **Service-Datei vorbereiten und kopieren:**
    Im `discord_bot`-Verzeichnis befindet sich eine Vorlagedatei namens `endoreservebank_bot.service` (vorher `echosoldojo_bot.service` - der Dateiname im Repository wird ebenfalls angepasst).
    Du musst diese Datei anpassen und dann nach `/etc/systemd/system/` kopieren.

    **Öffne die `endoreservebank_bot.service`-Datei (noch in deinem Projektverzeichnis) mit einem Editor und passe folgende Zeilen an:**
    *   `User=dein_benutzername`: Ersetze `dein_benutzername` durch den Linux-Benutzernamen, unter dem der Bot laufen soll (wahrscheinlich dein eigener Benutzer, wenn du alles in deinem Home-Verzeichnis eingerichtet hast).
    *   `WorkingDirectory=/pfad/zum/discord_bot`: Ersetze `/pfad/zum/discord_bot` durch den **vollständigen, absoluten Pfad** zu deinem `discord_bot`-Verzeichnis. Beispiel: `/home/dein_benutzername/endoreservebank_platform/discord_bot`. Du kannst den Pfad mit `pwd` herausfinden, wenn du im Verzeichnis bist.
    *   `ExecStart=/pfad/zum/discord_bot/venv/bin/python ...`: Ersetze hier ebenfalls `/pfad/zum/discord_bot` in beiden Pfadangaben (zum Python-Interpreter in der venv und zum Uvicorn-Skript in der venv).
    *   `EnvironmentFile=/pfad/zum/discord_bot/.env`: Stelle sicher, dass dieser Pfad korrekt zur `.env`-Datei im `discord_bot`-Verzeichnis zeigt.

    **Beispiel für eine angepasste `endoreservebank_bot.service`:**
    ```ini
    [Unit]
    Description=Endo Reserve Bank Discord Verification Bot <!-- Angepasst -->
    After=network.target

    [Service]
    User=myuser
    WorkingDirectory=/home/myuser/endoreservebank_platform/discord_bot <!-- Angepasst -->
    ExecStart=/home/myuser/endoreservebank_platform/discord_bot/venv/bin/python /home/myuser/endoreservebank_platform/discord_bot/venv/bin/uvicorn main:app --host 0.0.0.0 --port 8000
    Restart=always
    EnvironmentFile=/home/myuser/endoreservebank_platform/discord_bot/.env <!-- Angepasst -->

    [Install]
    WantedBy=multi-user.target
    ```

    **Kopiere die angepasste Datei:**
    (Stelle sicher, dass der Dateiname der Service-Datei `endoreservebank_bot.service` ist, bevor du ihn kopierst)
    ```bash
    sudo cp ~/endoreservebank_platform/discord_bot/endoreservebank_bot.service /etc/systemd/system/endoreservebank_bot.service
    ```
    (Passe den Quellpfad an, falls dein Projekt woanders liegt).

2.  **Systemd neu laden, Service aktivieren und starten:**
    *   Lade die systemd-Konfiguration neu, damit der neue Service erkannt wird:
        ```bash
        sudo systemctl daemon-reload
        ```
    *   Aktiviere den Service, damit er beim Systemstart automatisch gestartet wird:
        ```bash
        sudo systemctl enable endoreservebank_bot.service
        ```
    *   Starte den Service manuell (für den ersten Start):
        ```bash
        sudo systemctl start endoreservebank_bot.service
        ```

3.  **Status überprüfen und Logs einsehen:**
    *   Überprüfe den Status des Services:
        ```bash
        sudo systemctl status endoreservebank_bot.service
        ```
        Du solltest sehen, dass der Service `active (running)` ist. Wenn nicht, gibt die Ausgabe Hinweise auf Fehler.
    *   Um die Log-Ausgaben des Bots (inklusive `print()`-Anweisungen und Fehler) einzusehen:
        ```bash
        sudo journalctl -u endoreservebank_bot.service -f
        ```
        Mit `-f` (follow) siehst du neue Logeinträge in Echtzeit. Drücke `Ctrl+C`, um die Ansicht zu beenden.
        Für spezifische Fehler seit dem letzten Start: `sudo journalctl -u endoreservebank_bot.service -e`

Wenn der Service läuft, ist dein Python-Verifizierungs-Bot einsatzbereit! Denke daran, die URL (inkl. Port, falls nicht 80/443) und den `API_SECRET_KEY` in der `config.php` der Web-Anwendung korrekt zu hinterlegen.

---

## Zusammenfassung aller IDs & Daten

Hier ist eine Checkliste der wichtigen IDs und Konfigurationsdaten, die du während des Installationsprozesses sammeln und eintragen musst. **Ersetze die Platzhalter und Beispielwerte immer mit deinen eigenen, tatsächlichen Daten!**

**1. Für die PHP Web-Anwendung (`web_app/config.php`):**

*   **Domain-Name deiner Webseite:**
    *   Beispiel: `dojo.chelo.lat`
    *   Eintragen in `BASE_URL` und `DISCORD_REDIRECT_URI`.

*   **MySQL-Datenbank:**
    *   `DB_HOST`: (z.B. `localhost`, `127.0.0.1`, oder spezifischer Host deines Anbieters)
    *   `DB_NAME`: (Name der erstellten Datenbank, z.B. `endo_reserve_bank_db`) <!-- Angepasst -->
    *   `DB_USER`: (Datenbank-Benutzername, z.B. `endo_bank_user`) <!-- Angepasst -->
    *   `DB_PASS`: (Passwort für den Datenbank-Benutzer)

*   **Discord OAuth2 Credentials (aus dem Discord Developer Portal):**
    *   `DISCORD_CLIENT_ID`: (Deine Discord App Client ID)
    *   `DISCORD_CLIENT_SECRET`: (Dein Discord App Client Secret)
    *   `DISCORD_REDIRECT_URI`: (Muss exakt übereinstimmen, z.B. `https://dojo.chelo.lat/callback.php`)

*   **Verbindung zum Python Verifizierungs-Bot:**
    *   `VERIFY_BOT_URL`: (Die vollständige, öffentliche URL deines Bots, z.B. `http://DEINE_BOT_IP:8000/verify-user/` oder `https://bot.deinedomain.tld/verify-user/`)
    *   `BOT_API_SECRET_KEY`: (Dein selbstgewähltes, starkes Secret – muss identisch zum Bot sein!)

*   **Allgemeine Einstellungen:**
    *   `SITE_NAME`: (Name deiner Community, z.B. "Endo Reserve Bank") <!-- Angepasst -->
    *   `BASE_URL`: (Die Basis-URL deiner Webseite, z.B. `https://dojo.chelo.lat`)
    *   `DEBUG_MODE`: `true` für Entwicklung, **`false` für Live-Betrieb!**

**2. Für den Python Verifizierungs-Bot (`discord_bot/.env` Datei):**

*   **Discord Bot Token:**
    *   `DISCORD_BOT_TOKEN`: (Dein Discord Bot Token aus dem Discord Developer Portal -> Deine App -> Bot)

*   **API Sicherheit:**
    *   `API_SECRET_KEY`: (Dein selbstgewähltes, starkes Secret – muss identisch zur PHP-App sein!)

*   **Discord Server- und Rollen-IDs (festgelegt für Echo Sol Dojo):**
    *   `GUILD_ID`: `1212768310312042566` (Die ID des Discord Servers, der geprüft werden soll)
    *   `ROLE_ID`: `1391269160994209873` (Die ID der Rolle, die für die Verifizierung benötigt wird)

**3. Wichtige IDs für das Projekt (informativ, teilweise fest im Code/Schema):**

*   **SuperAdmin Discord ID (für `schema.sql`):** `1020559274012852294`
    *   Dieser User erhält automatisch Admin-Rechte bei der Datenbankinitialisierung.
*   **Zu prüfende Server ID (für den Bot):** `1212768310312042566`
*   **Zu prüfende Rollen ID (für den Bot):** `1391269160994209873`

**Beispielhafte Platzhalterwerte (ersetze diese!):**

*   Discord App Client ID (Beispiel): `1392357117142372436`
*   Discord App Client Secret (Beispiel): `YERKzJLoMyv6r2kRTWRhdXjoRBE8in9m`
*   Discord Bot Token (Beispiel): `MTM5MjM1NzExNzE0MjM3MjQzNg.GxBa4W.nnhWKarM4pEt4bY85vKwwyNFv2Zp9Yq0TQU3UU`
*   MySQL-Daten (Beispiel für `config.php`):
    *   Host: `localhost`
    *   DB-Name: `chelo_prime_dojo`
    *   User: `chelo_prime_user`
    *   Pass: `DeinStarkesPasswort123!`
*   API Secret Key (Beispiel): `MeinSuperGeheimerApiKey_EchoSolDojo_XYZ123!@#`

Stelle sicher, dass du alle diese Werte korrekt sammelst und in die entsprechenden Konfigurationsdateien einträgst, um eine erfolgreiche Installation und Funktion der Plattform zu gewährleisten.
