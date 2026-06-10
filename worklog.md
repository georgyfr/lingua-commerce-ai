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
