# Tesla4IPS
- Version 0.2
- Author: fasteagle
- Date: 29.03.2016
- Description: Script for Tesla remote control integration in IP-Symcon 

# Erstinstallation:
1. Zur Verwendung das Standardinclude.php in den Skript Ordner (...\IP-Symcon\Scripts\) von IP-Symcon kopieren.
2. Das Tesla4IPS_Install Skript in der IP-Symcon Konsole an gewünschter Stelle erstellen und Skripteditor öffnen.
3. Token und FzgID eintragen. (Kann über REMOTE S ermittelt werden: https://tff-forum.de/viewtopic.php?f=58 ... 30#p207620)
4. Anschließend das Skript ausführen. Es werden alle Variablen und Profile erstellt.
5. Ein weiteres Ausführen des Skriptes lädt dann erneut alle Variablen .

# Update Installation:
Wer das erste Skript schon installiert hat löscht am besten die Tesla Control Instanz und fängt bei Punkt 2) der Erstinstallation an.

#Funktionsumfang:
- Anzeige der wichtigsten Werte
- Button zum Starten/Stoppen der Klima
- Button zum öffnen des Ladeport
- Button für Hupe
- Button für Lichthupe
- Steuerung des Ladelimit
- Button zum Starten/Stoppen des Ladevorgangs
- Button zum Entriegeln/Verriegeln

Was noch fehlt:
- Gliederung in verschiedene Instanzen (Akku, Infos, Klima, Steuerung), aktuell ist alles in einer Liste, was der Übersicht nicht dient.
- Steuerung des Schiebedachs
- Google Map Anzeige der aktuellen Position
- Steuerung zur Einstellung der Innenraum-Temperatur
