---
Task ID: 1
Agent: Main Agent
Task: Fix "Erreur serveur. Vérifiez votre connexion." error on Z.AI test

Work Log:
- Diagnosed the issue: jQuery `error:` callback fires when server returns non-200 HTTP status or non-JSON content
- Identified 3 root causes: (1) PHP warnings corrupting JSON output, (2) expired nonces causing 403, (3) insufficient error diagnostics
- Fixed JS in lingua-admin-ai-display.php: Added `dataType: 'json'`, detailed error messages via `getAjaxErrorMessage()`, nonce auto-refresh via `refreshNonce()` function
- Fixed PHP in class-lingua-admin-ai.php: Added `ob_start()/ob_end_clean()` in `ajax_test_translate()` and `ajax_test_api_key()` to prevent PHP warnings from corrupting JSON
- Added `ajax_refresh_nonce()` method and registered `wp_ajax_lingua_refresh_nonce` hook
- Enhanced error logging in `do_engine_translate()` for LLM engines: logs connection errors, non-200 HTTP codes, and unexpected responses
- Improved error messages to be more descriptive (shows engine name, HTTP code, suggests checking logs)

Stage Summary:
- Files modified: `admin/partials/lingua-admin-ai-display.php`, `admin/class-lingua-admin-ai.php`
- Key fix: `ob_start()/ob_end_clean()` prevents PHP warnings/notices from being prepended to JSON responses
- Key fix: `dataType: 'json'` ensures jQuery properly detects JSON parse errors vs HTTP errors
- Key fix: Nonce refresh mechanism prevents stale nonce 403 errors
- Key fix: Detailed error messages replace generic "Erreur serveur" for better debugging

---
Task ID: 4
Agent: Main Agent
Task: Complete audit and fix of the IA & Automatisation admin page

Work Log:
- Analyzed screenshot showing broken UI: Z.AI shown as "Gratuit — Aucune clé nécessaire", save button shows "Erreur serveur", toggles don't work
- Performed comprehensive code audit of all AJAX handlers in class-lingua-admin-ai.php
- Found CRITICAL bug: `lingua_commerce_ai_ai_settings_group` was never registered via `register_setting()` — the options.php form silently failed
- Found CRITICAL bug: dual option keys (`lingua_commerce_ai_settings` vs `lingua_commerce_ai_ai_settings`) causing settings to be saved/loaded from different rows
- Found Z.AI was hardcoded as `$api_key = 'free'` in `ajax_test_api_key()` — bypassing the user's actual key
- Found `ob_end_clean()` missing on 2 early error paths in `ajax_test_api_key()`
- Found toggles/selects used `name=` attributes for a non-existent form submission instead of AJAX
- Found the `<form action="options.php">` was empty (no inputs inside it) and connected to an unregistered settings group
- Registered `lingua_commerce_ai_ai_settings` as a proper WordPress option with `sanitize_ai_settings()` callback
- Added `ajax_save_ai_settings_ajax()` method that saves to the CORRECT option key `lingua_commerce_ai_ai_settings`
- Added `lingua_save_ai_settings_ajax` AJAX action and hook registration
- Converted all settings inputs (toggles, selects, numbers, glossary) to use `class="lingua-ai-setting" data-setting="..."` for AJAX collection
- Added JS `collectAISettings()` function to gather all settings from `.lingua-ai-setting` elements
- Added "Sauvegarder les réglages IA" button with AJAX save
- Added "Tout sauvegarder (Clés + Réglages)" button that saves both keys and settings
- Fixed Z.AI `api_key = 'free'` bypass — removed the hardcoded free assignment
- Added `ob_end_clean()` before early `wp_send_json_error` calls
- Made glossary section show/hide dynamically via JS toggle
- Fixed recap input class to `lingua-api-key-input-recap` for proper targeting
- Added `dataType: 'json'` to all remaining AJAX calls missing it

Stage Summary:
- CRITICAL FIX: `register_setting()` for `lingua_commerce_ai_ai_settings_group` now works
- CRITICAL FIX: Settings saved to correct option key via new `ajax_save_ai_settings_ajax` handler
- CRITICAL FIX: Z.AI no longer forced to 'free' — user key is properly used
- FIX: All toggles/selects now save via AJAX instead of broken form submission
- FIX: "Tout sauvegarder" button replaces the broken `<form action="options.php">`
- Files modified: `admin/class-lingua-admin.php`, `admin/class-lingua-admin-ai.php`, `admin/partials/lingua-admin-ai-display.php`
