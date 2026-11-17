# DeepPoster – WordPress-Plugin für KI-generierte Beiträge

WordPress-Plugin (PHP, Hauptdatei `plugin/deepposter.php`, Version in `DEEPPOSTER_VERSION`), das mit OpenAI (Chat Completions, Modellliste über `/v1/models`) oder DeepSeek Blogbeiträge aus gespeicherten Prompts erzeugt, mit Vorschau und Planungsfunktion. Prompts liegen als eigener Post Type. Anforderungen (Entwurfsstand, Projektname dort „DeepBlogger“): `SoftwareRequirements.md`; Ideen: `plugin/docs/future-enhancements.md`.

## Umgebung und Betrieb

- Es gibt weder CI noch einen im Repo beschriebenen Deploy-Weg; das Plugin wird von Hand in WordPress installiert. Production-URLs und Server sind im Repo nicht belegt.
- Lokal: `docker/docker-compose.yml` startet WordPress (PHP 8.2) auf `http://localhost:8000` und MySQL 8; `plugin/` ist als `wp-content/plugins/deepposter` eingehängt. Die Image-Plattform ist `linux/arm64/v8` (Apple Silicon).
- Einstellungen (API-Schlüssel, Provider, Modell, Max Tokens, Temperatur) stehen in WordPress-Optionen mit Präfix `deepposter_`.
- E2E-Tests: Cypress gegen `http://localhost:8000`. Im Wurzelverzeichnis `npm run cy:run` (Specs `cypress/e2e/`), im Ordner `plugin/` eine zweite Suite (`plugin/cypress/e2e/`) mit Zugangsdaten aus `plugin/cypress.env.json` und Percy (`npm run percy:run`, braucht `PERCY_TOKEN`).

## Fallen

- `node_modules/` im Wurzelverzeichnis ist eingecheckt (rund 7.850 Dateien); `.gitignore` schließt es nicht aus. Ein `npm install` erzeugt daher große Diffs.
- Mehrere Dateien liegen doppelt: `plugin/includes/` und `plugin/assets/includes/`, `plugin/assets/js/` und `plugin/admin/js/`, dazu Kopien unter `assets/` und `admin/js/` im Wurzelverzeichnis. `plugin/deepposter.php` lädt nur `plugin/includes/*` und `plugin/admin/class-deepposter-admin.php`; Änderungen in den anderen Kopien wirken dort nicht, ob sie anderswo eingebunden werden, ist nicht geprüft.
- Die Schlüssel-Optionen sind uneinheitlich benannt: `deepposter_openai_api_key` (AJAX-Generierung, Admin-Klasse) und `deepposter_openai_key` (`generator.php`, `templates/settings.php`) sowie `deepposter_deepseek_key` und `deepposter_deepseek_api_key`. Ein unter dem anderen Namen gespeicherter Schlüssel wird an der jeweiligen Stelle nicht gefunden.
- `plugin/deepposter.php` schaltet `DEEPPOSTER_DEBUG`, `WP_DEBUG` und `WP_DEBUG_LOG` fest an; Logs landen in `wp-content/debug.log`.

## Konventionen

- Kommentare auf Englisch, Oberfläche und Texte auf Deutsch.
- WordPress-Standards: Nonces, Escaping/Sanitizing, `$wpdb->prepare()`, `dbDelta()` für Schemaänderungen, `wp_enqueue_*` für Assets.
