## Security (Sicherheit) nur falls zutreffend

* **Eingabevalidierung & Sanitization:** Konsequente Validierung aller Benutzereingaben (Whitelisting) und Bereinigung, um Injections zu verhindern.
* **Schutz vor SQL-Injection:** Ausschließliche Verwendung von Prepared Statements (Parameterized Queries) bei Datenbankzugriffen.
* **Cross-Site Scripting (XSS) Schutz:** Maskierung aller dynamischen Ausgaben im HTML-Kontext (Kontext-sensitives Escaping) und Nutzen von `HttpOnly`-Flags für Cookies.
* **Cross-Site Request Forgery (CSRF) Schutz:** Implementierung von kryptografisch sicheren Anti-CSRF-Token für alle zustandsändernden Anfragen (POST/PUT/DELETE).
* **Authentifizierung & Session-Management:** * Sicheres Passwort-Hashing (z. B. mit Argon2 oder bcrypt, niemals MD5 oder SHA-1).
    * Generierung neuer Session-IDs nach dem Login.
    * Regelmäßiges Session-Timeout und sicheres Zerstören der Session beim Logout.
* **Sichere Konfiguration & Transport:**
    * Erzwingen von HTTPS via TLS 1.3 (bzw. mindestens TLS 1.2) und Setzen des `Strict-Transport-Security` (HSTS) Headers.
    * Deaktivierung von Verzeichnis-Listing (Directory Browsing) auf dem Webserver.
    * Ausblenden von Server-Signaturen und Versionsnummern in den HTTP-Headern.
* **Fehlermanagement & Logging:** * Verhindern von detaillierten Fehlermeldungen (Stack Traces, SQL-Queries) im Frontend für Endbenutzer.
    * Zentrales, manipulationssicheres Logging von sicherheitsrelevanten Events (z. B. fehlgeschlagene Logins).
* **Abhängigkeiten (Dependencies):** Regelmäßige Überprüfung und Aktualisierung von genutzten Frameworks, Bibliotheken und Docker-Basis-Images auf bekannte Sicherheitslücken (CVEs).

---

## Datenschutz (Privacy)

* **Datenminimierung (Art. 5 DSGVO):** Prüfung, ob nur Daten erhoben und verarbeitet werden, die für den spezifischen Zweck der Anwendung zwingend erforderlich sind.
* **Rechtmäßigkeit & Einwilligung:** * Vorhandensein einer klaren Rechtsgrundlage für jede Verarbeitung (z. B. Einwilligung oder Vertragserfüllung).
    * Einholen einer aktiven, informierten Einwilligung (Opt-In), bevor Cookies oder Tracker gesetzt werden, die nicht technisch essenziell sind.
* **Betroffenenrechte (Art. 12-22 DSGVO):** Technische Möglichkeit zur Umsetzung von:
    * Auskunftsrecht (Export aller über den Nutzer gespeicherten Daten).
    * Recht auf Berichtigung.
    * Recht auf Löschung („Recht auf Vergessenwerden“ – vollständiges Entfernen oder Anonymisieren aus DB und Backups).
* **Verschlüsselung gespeicherter Daten (Data at Rest):** Verschlüsselung von sensiblen personenbezogenen Daten in der Datenbank (z. B. Adressdaten oder IP-Adressen, falls diese dauerhaft gespeichert werden).
* **Protokollierung von Zugriffen:** Nachvollziehbarkeit, wer wann auf personenbezogene Daten zugegriffen oder diese verändert hat (insbesondere bei administrativen Backends).
* **Speicherbegrenzung & Löschkonzepte:** Automatische oder regelmäßige manuelle Löschung von Daten, deren Aufbewahrungsfrist abgelaufen ist oder deren Zweck entfallen ist (z. B. alte Protokolldaten).
* **Datenschutzerklärung & Impressum:** Leicht erreichbare, transparente Einbindung einer aktuellen Datenschutzerklärung, die exakt beschreibt, welche Daten zu welchem Zweck verarbeitet werden, sowie ein gesetzeskonformes Impressum.
* * Aufruf externer Dienste (Google Fonts, Bibliotheken) unterbinden und eventuell lokal einbinden.