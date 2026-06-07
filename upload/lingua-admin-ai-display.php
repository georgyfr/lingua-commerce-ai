<?php
/**
 * Vue pour la page IA & Automatisation (Design Optimisé + Liens API + Bugfix)
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/admin/partials
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

// Sécurité : on s'assure que $settings est un tableau et on définit des valeurs par défaut
if ( ! isset( $settings ) || ! is_array( $settings ) ) {
    $settings = array();
}
 $ai_tone = isset( $settings['ai_tone'] ) ? $settings['ai_tone'] : 'neutral';
 $auto_validate = isset( $settings['auto_validate'] ) ? $settings['auto_validate'] : 0;
?>

<!-- CSS POUR LE DESIGN DE CETTE PAGE -->
<style>
    /* Conteneur principal */
    .lingua-ai-page h1 { font-size: 23px; font-weight: 400; margin: 0; padding: 9px 0 4px 0; line-height: 1.3; }
    .lingua-ai-page h2 { font-size: 1.5em; margin-bottom: 1em; padding-bottom: 10px; border-bottom: 1px solid #eee; }
    
    /* Onglets */
    .nav-tab-wrapper { margin-bottom: 20px; }
    .nav-tab { text-decoration: none !important; }
    .tab-content { background: #fff; padding: 20px; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); min-height: 400px; }
    
    /* Grille des Moteurs */
    .lingua-engines-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
        gap: 25px;
    }

    /* Design de la Carte Moteur */
    .lingua-engine-card {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-top: 5px solid #2271b1; 
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        display: flex;
        flex-direction: column;
    }

    .lingua-engine-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 12px rgba(0,0,0,0.1);
    }

    .lingua-card-header {
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
        background: #f6f7f7;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .lingua-card-header h3 { margin: 0; font-size: 1.2em; color: #1d2327; }
    .lingua-card-header p { margin: 5px 0 0 0; font-size: 0.85em; color: #646970; font-style: italic; }

    /* Formulaire Config (Haut) */
    .lingua-config-area { padding: 20px; background: #fff; border-bottom: 1px dashed #ddd; }
    .lingua-config-form input { width: 100%; }

    /* Zone de Test (Bas) */
    .lingua-test-area {
        flex-grow: 1;
        padding: 20px;
        background: #f9f9f9;
        display: flex;
        flex-direction: column;
    }
    .lingua-test-area h4 { margin-top: 0; margin-bottom: 15px; font-size: 1em; color: #444; border-left: 3px solid #666; padding-left: 10px; }

    .lingua-split-view {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .lingua-field-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        color: #646970;
        margin-bottom: 5px;
    }

    /* Champs et Boutons */
    .lingua-split-view textarea { width: 100%; min-height: 100px; padding: 10px; border: 1px solid #8c8f94; border-radius: 4px; font-family: monospace; font-size: 13px; resize: vertical; }
    .lingua-split-view input[type="text"] { width: 100%; padding: 8px; border: 1px solid #ddd; background: #eee; color: #555; }
    .lingua-split-view select { width: 100%; padding: 8px; border: 1px solid #8c8f94; border-radius: 4px; }

    /* Lien API */
    .lingua-api-link { display: inline-block; margin-top: 5px; font-size: 12px; color: #2271b1; text-decoration: none; }
    .lingua-api-link:hover { color: #135e96; text-decoration: underline; }

    /* Zone d'erreur */
    .lingua-error-msg {
        display: none; 
        padding: 12px;
        background: #fff0f1;
        border-left: 4px solid #d63638;
        color: #444;
        font-size: 13px;
        border-radius: 0 4px 4px 0;
        margin-top: 10px;
    }

    /* Cartes Dashboard */
    .lingua-dashboard-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .lingua-dash-card { background: #fff; padding: 20px; border: 1px solid #c3c4c7; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .lingua-dash-card h3 { margin-top: 0; font-size: 1.1em; color: #1d2327; }
    .lingua-dash-card .big-number { font-size: 2em; font-weight: bold; color: #2271b1; }

    /* Couleurs spécifiques des moteurs */
    .border-openrouter { border-top-color: #2271b1; }
    .border-deepseek { border-top-color: #0073aa; }
    .border-deepl { border-top-color: #0366d6; }
    .border-google { border-top-color: #4285F4; }
    .border-mistral { border-top-color: #ff7043; }
     .border-yandex { border-top-color: #FF0000; } /* Rouge Yandex */
    .border-baidu { border-top-color: #2319DC; } /* Bleu Baidu */
    .border-microsoft { border-top-color: #008272; } /* Vert Microsoft */
    @media (max-width: 768px) {
        .lingua-split-view, .lingua-dashboard-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="wrap lingua-ai-page">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <!-- NAVIGATION PAR ONGLETS -->
    <h2 class="nav-tab-wrapper">
        <a href="#dashboard" class="nav-tab nav-tab-active" onclick="return false;">📊 Tableau de bord</a>
        <a href="#engines" class="nav-tab" onclick="return false;">🤖 Moteurs IA</a>
        <a href="#settings" class="nav-tab" onclick="return false;">⚙️ Paramètres</a>
        <a href="#queue" class="nav-tab" onclick="return false;">🔄 File d'attente</a>
        <a href="#logs" class="nav-tab" onclick="return false;">📜 Logs & Historique</a>
    </h2>

    <!-- ONGLET 1 : TABLEAU DE BORD -->
    <div id="dashboard" class="tab-content active">
        <div class="lingua-dashboard-grid">
            <div class="lingua-dash-card" style="border-left: 4px solid #00a32a;">
                <h3>🟢 Moteurs Actifs</h3>
                <p class="description">Prêts à traduire.</p>
                <div class="big-number"><?php echo count( $engines ); ?></div>
            </div>
            <div class="lingua-dash-card" style="border-left: 4px solid #dba617;">
                <h3>⏳ En attente</h3>
                <p class="description">Dans la file de traitement.</p>
                <div class="big-number">0</div>
            </div>
            <div class="lingua-dash-card" style="border-left: 4px solid #2271b1;">
                <h3>💰 Estimation Coût</h3>
                <p class="description">Basé sur l'activité.</p>
                <div class="big-number">-- €</div>
            </div>
        </div>
        <div style="margin-top: 30px; padding: 20px; background: #e7f3ff; border: 1px solid #c3c4c7; border-radius: 5px;">
            <h3 style="margin-top:0;">💡 Conseil</h3>
            <p>Pour commencer, allez dans l'onglet <strong>Moteurs IA</strong>, entrez vos clés API et faites un test de traduction.</p>
        </div>
    </div>

    <!-- ONGLET 2 : MOTEURS IA -->
    <div id="engines" class="tab-content" style="display: none;">
        <div class="lingua-engines-grid">

            <!-- === BLOC 1 : OPENROUTER === -->
            <div class="lingua-engine-card border-openrouter">
                <div class="lingua-card-header">
                    <div>
                        <h3>🌐 OpenRouter</h3>
                        <p>Hub Multi-Modèles</p>
                    </div>
                    <?php if(isset($engines['openrouter']) && $engines['openrouter']['status'] == 'active') : ?>
                        <span class="dashicons dashicons-yes-alt" style="color:#00a32a; font-size:24px;"></span>
                    <?php endif; ?>
                </div>

                <div class="lingua-config-area">
                    <form class="lingua-config-form">
                        <input type="hidden" name="engine_name" value="openrouter">
                        
                        <div style="margin-bottom: 10px;">
                            <label class="lingua-field-label">Clé API OpenRouter</label>
                            <input type="password" name="api_key" placeholder="sk-or-v1-..." 
                                   value="<?php echo isset($engines['openrouter']) ? esc_attr($engines['openrouter']['api_key']) : ''; ?>">
                            <a href="https://openrouter.ai/keys" target="_blank" class="lingua-api-link">🔗 Obtenir une clé API OpenRouter</a>
                        </div>

                        <div style="margin-bottom: 10px;">
                            <label class="lingua-field-label">Modèle IA Payant</label>
                            <select name="model_paid" class="regular-text" style="width: 100%;">
                                <option value="">-- Sélectionner --</option>
                                <option value="anthropic/claude-3.5-sonnet" <?php echo (isset($engines['openrouter']['settings']['model_paid']) && $engines['openrouter']['settings']['model_paid'] === 'anthropic/claude-3.5-sonnet') ? 'selected' : ''; ?>>⭐ Claude 3.5 Sonnet (Meilleur SEO)</option>
                                <option value="openai/gpt-4o" <?php echo (isset($engines['openrouter']['settings']['model_paid']) && $engines['openrouter']['settings']['model_paid'] === 'openai/gpt-4o') ? 'selected' : ''; ?>>GPT-4o (Référence)</option>
                                <option value="deepseek/deepseek-chat" <?php echo (isset($engines['openrouter']['settings']['model_paid']) && $engines['openrouter']['settings']['model_paid'] === 'deepseek/deepseek-chat') ? 'selected' : ''; ?>>DeepSeek Chat (Économique)</option>
                                <option value="openai/gpt-4o-mini" <?php echo (isset($engines['openrouter']['settings']['model_paid']) && $engines['openrouter']['settings']['model_paid'] === 'openai/gpt-4o-mini') ? 'selected' : ''; ?>>GPT-4o Mini (Rapide)</option>
                                <option value="meta-llama/llama-3.1-70b-instruct" <?php echo (isset($engines['openrouter']['settings']['model_paid']) && $engines['openrouter']['settings']['model_paid'] === 'meta-llama/llama-3.1-70b-instruct') ? 'selected' : ''; ?>>Llama 3.1 70B</option>
                            </select>
                        </div>

                        <div style="margin-bottom: 10px;">
                            <label class="lingua-field-label">Modèle IA Gratuit</label>
                            <select name="model_free" class="regular-text" style="width: 100%;">
                                <option value="">-- Sélectionner --</option>
                                <option value="meta-llama/llama-3.2-3b-instruct:free" <?php echo (isset($engines['openrouter']['settings']['model_free']) && $engines['openrouter']['settings']['model_free'] === 'meta-llama/llama-3.2-3b-instruct:free') ? 'selected' : ''; ?>>Llama 3.2 3B (Gratuit)</option>
                                <option value="meta-llama/llama-3-8b-instruct:free" <?php echo (isset($engines['openrouter']['settings']['model_free']) && $engines['openrouter']['settings']['model_free'] === 'meta-llama/llama-3-8b-instruct:free') ? 'selected' : ''; ?>>Llama 3 8B (Gratuit)</option>
                            </select>
                        </div>

                        <div style="text-align: right;">
                            <button type="submit" class="button button-primary">💾 Sauver la config</button>
                        </div>
                    </form>
                </div>

                <div class="lingua-test-area">
                    <h4>🧪 Zone de Test</h4>
                    <div class="lingua-split-view">
                        <div>
                            <label class="lingua-field-label">Langue Source</label>
                            <input type="text" disabled value="<?php echo esc_html($default_lang_obj->native_name); ?>">
                            <label class="lingua-field-label" style="margin-top:10px;">Langue Cible</label>
                            <select name="target_lang" class="lingua-target-lang">
                                <?php foreach ( $active_languages as $lang ) : ?>
                                    <?php if( $lang->code !== $default_lang_obj->code ) : ?>
                                        <option value="<?php echo esc_attr($lang->code); ?>"><?php echo esc_html( $lang->native_name ); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <label class="lingua-field-label" style="margin-top:10px;">Texte</label>
                            <textarea name="text_source" class="lingua-source-text" placeholder="Tapez ici..."></textarea>
                            <button type="button" class="lingua-btn-translate button button-primary" style="width:100%; margin-top:10px;">Traduire ➜</button>
                        </div>
                        <div>
                            <label class="lingua-field-label">Résultat</label>
                            <textarea id="result_openrouter" class="lingua-result-text" readonly placeholder="Résultat..."></textarea>
                            <div id="error_openrouter" class="lingua-error-msg"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- === BLOC 2 : DEEPSEEK === -->
            <div class="lingua-engine-card border-deepseek">
                <div class="lingua-card-header">
                    <div>
                        <h3>🇨🇳 DeepSeek</h3>
                        <p>Champion Rapport/Coût</p>
                    </div>
                    <?php if(isset($engines['deepseek']) && $engines['deepseek']['status'] == 'active') : ?>
                        <span class="dashicons dashicons-yes-alt" style="color:#00a32a; font-size:24px;"></span>
                    <?php endif; ?>
                </div>
                <div class="lingua-config-area">
                    <form class="lingua-config-form">
                        <input type="hidden" name="engine_name" value="deepseek">
                        <div style="margin-bottom: 10px;">
                            <label class="lingua-field-label">Clé API DeepSeek</label>
                            <input type="password" name="api_key" placeholder="Clé API..." 
                                   value="<?php echo isset($engines['deepseek']) ? esc_attr($engines['deepseek']['api_key']) : ''; ?>">
                            <a href="https://platform.deepseek.com/api_keys" target="_blank" class="lingua-api-link">🔗 Obtenir une clé API DeepSeek</a>
                        </div>
                        <div style="text-align: right;">
                            <button type="submit" class="button button-primary">💾 Sauver</button>
                        </div>
                    </form>
                </div>
                <div class="lingua-test-area">
                    <h4>🧪 Zone de Test</h4>
                    <div class="lingua-split-view">
                        <div>
                            <label class="lingua-field-label">Source</label>
                            <input type="text" disabled value="<?php echo esc_html($default_lang_obj->native_name); ?>">
                            <label class="lingua-field-label" style="margin-top:10px;">Cible</label>
                             <select name="target_lang" class="lingua-target-lang">
                                <?php foreach ( $active_languages as $lang ) : ?>
                                    <?php if( $lang->code !== $default_lang_obj->code ) : ?>
                                        <option value="<?php echo esc_attr($lang->code); ?>"><?php echo esc_html( $lang->native_name ); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="text_source" class="lingua-source-text" placeholder="Texte..."></textarea>
                            <button type="button" class="lingua-btn-translate button button-primary" style="width:100%; margin-top:10px;">Traduire ➜</button>
                        </div>
                        <div>
                            <label class="lingua-field-label">Résultat</label>
                            <textarea id="result_deepseek" class="lingua-result-text" readonly></textarea>
                            <div id="error_deepseek" class="lingua-error-msg"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- === BLOC 3 : DEEPL === -->
            <div class="lingua-engine-card border-deepl">
                <div class="lingua-card-header">
                    <div>
                        <h3>⚡ DeepL</h3>
                        <p>Référence Européenne</p>
                    </div>
                    <?php if(isset($engines['deepl']) && $engines['deepl']['status'] == 'active') : ?>
                        <span class="dashicons dashicons-yes-alt" style="color:#00a32a; font-size:24px;"></span>
                    <?php endif; ?>
                </div>
                <div class="lingua-config-area">
                    <form class="lingua-config-form">
                        <input type="hidden" name="engine_name" value="deepl">
                        <div style="margin-bottom: 10px;">
                            <label class="lingua-field-label">Clé API DeepL</label>
                            <input type="password" name="api_key" placeholder="Clé Auth..." 
                                   value="<?php echo isset($engines['deepl']) ? esc_attr($engines['deepl']['api_key']) : ''; ?>">
                            <a href="https://www.deepl.com/pro-api" target="_blank" class="lingua-api-link">🔗 Obtenir une clé API DeepL</a>
                        </div>
                        <div style="text-align: right;">
                            <button type="submit" class="button button-primary">💾 Sauver</button>
                        </div>
                    </form>
                </div>
                <div class="lingua-test-area">
                    <h4>🧪 Zone de Test</h4>
                    <div class="lingua-split-view">
                        <div>
                            <label class="lingua-field-label">Source</label>
                            <input type="text" disabled value="<?php echo esc_html($default_lang_obj->native_name); ?>">
                            <label class="lingua-field-label" style="margin-top:10px;">Cible</label>
                             <select name="target_lang" class="lingua-target-lang">
                                <?php foreach ( $active_languages as $lang ) : ?>
                                    <?php if( $lang->code !== $default_lang_obj->code ) : ?>
                                        <option value="<?php echo esc_attr($lang->code); ?>"><?php echo esc_html( $lang->native_name ); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="text_source" class="lingua-source-text" placeholder="Texte..."></textarea>
                            <button type="button" class="lingua-btn-translate button button-primary" style="width:100%; margin-top:10px;">Traduire ➜</button>
                        </div>
                        <div>
                            <label class="lingua-field-label">Résultat</label>
                            <textarea id="result_deepl" class="lingua-result-text" readonly></textarea>
                            <div id="error_deepl" class="lingua-error-msg"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- === BLOC 4 : GOOGLE === -->
            <div class="lingua-engine-card border-google">
                <div class="lingua-card-header">
                    <div>
                        <h3>🔵 Google Cloud</h3>
                        <p>Couverture Mondiale</p>
                    </div>
                    <?php if(isset($engines['google']) && $engines['google']['status'] == 'active') : ?>
                        <span class="dashicons dashicons-yes-alt" style="color:#00a32a; font-size:24px;"></span>
                    <?php endif; ?>
                </div>
                <div class="lingua-config-area">
                    <form class="lingua-config-form">
                        <input type="hidden" name="engine_name" value="google">
                        <div style="margin-bottom: 10px;">
                            <label class="lingua-field-label">Clé API Google</label>
                            <input type="password" name="api_key" placeholder="API Key..." 
                                   value="<?php echo isset($engines['google']) ? esc_attr($engines['google']['api_key']) : ''; ?>">
                            <a href="https://aistudio.google.com/app/apikey" target="_blank" class="lingua-api-link">🔗 Obtenir une clé API Google (AI Studio)</a>
                        </div>
                        <div style="text-align: right;">
                            <button type="submit" class="button button-primary">💾 Sauver</button>
                        </div>
                    </form>
                </div>
                <div class="lingua-test-area">
                    <h4>🧪 Zone de Test</h4>
                    <div class="lingua-split-view">
                        <div>
                            <label class="lingua-field-label">Source</label>
                            <input type="text" disabled value="<?php echo esc_html($default_lang_obj->native_name); ?>">
                            <label class="lingua-field-label" style="margin-top:10px;">Cible</label>
                             <select name="target_lang" class="lingua-target-lang">
                                <?php foreach ( $active_languages as $lang ) : ?>
                                    <?php if( $lang->code !== $default_lang_obj->code ) : ?>
                                        <option value="<?php echo esc_attr($lang->code); ?>"><?php echo esc_html( $lang->native_name ); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="text_source" class="lingua-source-text" placeholder="Texte..."></textarea>
                            <button type="button" class="lingua-btn-translate button button-primary" style="width:100%; margin-top:10px;">Traduire ➜</button>
                        </div>
                        <div>
                            <label class="lingua-field-label">Résultat</label>
                            <textarea id="result_google" class="lingua-result-text" readonly></textarea>
                            <div id="error_google" class="lingua-error-msg"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- === BLOC 5 : MISTRAL === -->
            <div class="lingua-engine-card border-mistral">
                <div class="lingua-card-header">
                    <div>
                        <h3>🇪🇺 Mistral AI</h3>
                        <p>Champion Européen</p>
                    </div>
                    <?php if(isset($engines['mistral']) && $engines['mistral']['status'] == 'active') : ?>
                        <span class="dashicons dashicons-yes-alt" style="color:#00a32a; font-size:24px;"></span>
                    <?php endif; ?>
                </div>
                <div class="lingua-config-area">
                    <form class="lingua-config-form">
                        <input type="hidden" name="engine_name" value="mistral">
                        <div style="margin-bottom: 10px;">
                            <label class="lingua-field-label">Clé API Mistral</label>
                            <input type="password" name="api_key" placeholder="API Key..." 
                                   value="<?php echo isset($engines['mistral']) ? esc_attr($engines['mistral']['api_key']) : ''; ?>">
                            <a href="https://console.mistral.ai/api-keys/" target="_blank" class="lingua-api-link">🔗 Obtenir une clé API Mistral</a>
                        </div>
                        <div style="text-align: right;">
                            <button type="submit" class="button button-primary">💾 Sauver</button>
                        </div>
                    </form>
                </div>
                <div class="lingua-test-area">
                    <h4>🧪 Zone de Test</h4>
                    <div class="lingua-split-view">
                        <div>
                            <label class="lingua-field-label">Source</label>
                            <input type="text" disabled value="<?php echo esc_html($default_lang_obj->native_name); ?>">
                            <label class="lingua-field-label" style="margin-top:10px;">Cible</label>
                             <select name="target_lang" class="lingua-target-lang">
                                <?php foreach ( $active_languages as $lang ) : ?>
                                    <?php if( $lang->code !== $default_lang_obj->code ) : ?>
                                        <option value="<?php echo esc_attr($lang->code); ?>"><?php echo esc_html( $lang->native_name ); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="text_source" class="lingua-source-text" placeholder="Texte..."></textarea>
                            <button type="button" class="lingua-btn-translate button button-primary" style="width:100%; margin-top:10px;">Traduire ➜</button>
                        </div>
                        <div>
                            <label class="lingua-field-label">Résultat</label>
                            <textarea id="result_mistral" class="lingua-result-text" readonly></textarea>
                            <div id="error_mistral" class="lingua-error-msg"></div>
                        </div>
                    </div>
                </div>
            </div
            
                        <!-- === BLOC 6 : YANDEX TRANSLATE === -->
            <div class="lingua-engine-card border-yandex">
                <div class="lingua-card-header">
                    <div>
                        <h3>🇷🇺 Yandex Translate</h3>
                        <p>Couverture Europe de l'Est / Asie</p>
                    </div>
                    <?php if(isset($engines['yandex']) && $engines['yandex']['status'] == 'active') : ?>
                        <span class="dashicons dashicons-yes-alt" style="color:#00a32a; font-size:24px;"></span>
                    <?php endif; ?>
                </div>
                <div class="lingua-config-area">
                    <form class="lingua-config-form">
                        <input type="hidden" name="engine_name" value="yandex">
                        <div style="margin-bottom: 10px;">
                            <label class="lingua-field-label">Clé API Yandex</label>
                            <input type="password" name="api_key" placeholder="trnsl..." value="<?php echo isset($engines['yandex']) ? esc_attr($engines['yandex']['api_key']) : ''; ?>">
                            <a href="https://translate.yandex.com/developers/keys" target="_blank" class="lingua-api-link">🔗 Obtenir une clé API</a>
                        </div>
                        <div style="text-align: right;">
                            <button type="submit" class="button button-primary">💾 Sauver</button>
                        </div>
                    </form>
                </div>
                <div class="lingua-test-area">
                    <h4>🧪 Zone de Test</h4>
                    <div class="lingua-split-view">
                        <div>
                            <label class="lingua-field-label">Source</label><input type="text" disabled value="<?php echo esc_html($default_lang_obj->native_name); ?>">
                            <label class="lingua-field-label" style="margin-top:10px;">Cible</label>
                            <select name="target_lang" class="lingua-target-lang">
                                <?php foreach ( $active_languages as $lang ) : ?>
                                    <?php if( $lang->code !== $default_lang_obj->code ) : ?>
                                        <option value="<?php echo esc_attr($lang->code); ?>"><?php echo esc_html( $lang->native_name ); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="text_source" class="lingua-source-text" placeholder="Texte..."></textarea>
                            <button type="button" class="lingua-btn-translate button button-primary" style="width:100%; margin-top:10px;">Traduire ➜</button>
                        </div>
                        <div>
                            <label class="lingua-field-label">Résultat</label>
                            <textarea id="result_yandex" class="lingua-result-text" readonly></textarea>
                            <div id="error_yandex" class="lingua-error-msg"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- === BLOC 7 : BAIDU TRANSLATE === -->
            <div class="lingua-engine-card border-baidu" style="border-top-color: #2319DC;">
                <div class="lingua-card-header">
                    <div>
                        <h3>🇨🇳 Baidu Translate</h3>
                        <p>Généreux & Rapide (Asie)</p>
                    </div>
                    <?php if(isset($engines['baidu']) && $engines['baidu']['status'] == 'active') : ?>
                        <span class="dashicons dashicons-yes-alt" style="color:#00a32a; font-size:24px;"></span>
                    <?php endif; ?>
                </div>
                <div class="lingua-config-area">
                    <form class="lingua-config-form">
                        <input type="hidden" name="engine_name" value="baidu">
                        <div style="margin-bottom: 10px;">
                            <label class="lingua-field-label">App ID</label>
                            <input type="text" name="api_key" placeholder="Votre App ID" value="<?php echo isset($engines['baidu']) ? esc_attr($engines['baidu']['api_key']) : ''; ?>">
                        </div>
                        <div style="margin-bottom: 10px;">
                            <label class="lingua-field-label">Secret Key</label>
                            <!-- On utilise le champ model_free pour stocker le secret Baidu (astuce) -->
                            <input type="password" name="model_free" placeholder="Clé Secrète" value="<?php echo isset($engines['baidu']['settings']['model_free']) ? esc_attr($engines['baidu']['settings']['model_free']) : ''; ?>">
                             <a href="https://fanyi-api.baidu.com/" target="_blank" class="lingua-api-link">🔗 Console Baidu</a>
                        </div>
                        <div style="text-align: right;">
                            <button type="submit" class="button button-primary">💾 Sauver</button>
                        </div>
                    </form>
                </div>
                <div class="lingua-test-area">
                    <h4>🧪 Zone de Test</h4>
                    <div class="lingua-split-view">
                        <div>
                            <label class="lingua-field-label">Source</label><input type="text" disabled value="<?php echo esc_html($default_lang_obj->native_name); ?>">
                            <label class="lingua-field-label" style="margin-top:10px;">Cible</label>
                             <select name="target_lang" class="lingua-target-lang">
                                <?php foreach ( $active_languages as $lang ) : ?>
                                    <?php if( $lang->code !== $default_lang_obj->code ) : ?>
                                        <option value="<?php echo esc_attr($lang->code); ?>"><?php echo esc_html( $lang->native_name ); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="text_source" class="lingua-source-text" placeholder="Texte..."></textarea>
                            <button type="button" class="lingua-btn-translate button button-primary" style="width:100%; margin-top:10px;">Traduire ➜</button>
                        </div>
                        <div>
                            <label class="lingua-field-label">Résultat</label>
                            <textarea id="result_baidu" class="lingua-result-text" readonly></textarea>
                            <div id="error_baidu" class="lingua-error-msg"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- === BLOC 8 : MICROSOFT BING === -->
            <div class="lingua-engine-card border-microsoft" style="border-top-color: #008272;">
                <div class="lingua-card-header">
                    <div>
                        <h3>🟦 Microsoft Bing</h3>
                        <p>Azure Cognitive Services</p>
                    </div>
                    <?php if(isset($engines['microsoft']) && $engines['microsoft']['status'] == 'active') : ?>
                        <span class="dashicons dashicons-yes-alt" style="color:#00a32a; font-size:24px;"></span>
                    <?php endif; ?>
                </div>
                <div class="lingua-config-area">
                    <form class="lingua-config-form">
                        <input type="hidden" name="engine_name" value="microsoft">
                        <div style="margin-bottom: 10px;">
                            <label class="lingua-field-label">Clé API Azure (Key 1)</label>
                            <input type="password" name="api_key" placeholder="Clé..." value="<?php echo isset($engines['microsoft']) ? esc_attr($engines['microsoft']['api_key']) : ''; ?>">
                        </div>
                         <div style="margin-bottom: 10px;">
                            <label class="lingua-field-label">Région</label>
                            <!-- On utilise model_paid pour la région -->
                            <input type="text" name="model_paid" placeholder="ex: global, westeurope" value="<?php echo isset($engines['microsoft']['settings']['model_paid']) ? esc_attr($engines['microsoft']['settings']['model_paid']) : 'global'; ?>">
                            <a href="https://azure.microsoft.com/fr-fr/services/cognitive-services/translator/" target="_blank" class="lingua-api-link">🔗 Créer ressource Azure</a>
                        </div>
                        <div style="text-align: right;">
                            <button type="submit" class="button button-primary">💾 Sauver</button>
                        </div>
                    </form>
                </div>
                <div class="lingua-test-area">
                    <h4>🧪 Zone de Test</h4>
                    <div class="lingua-split-view">
                        <div>
                            <label class="lingua-field-label">Source</label><input type="text" disabled value="<?php echo esc_html($default_lang_obj->native_name); ?>">
                            <label class="lingua-field-label" style="margin-top:10px;">Cible</label>
                             <select name="target_lang" class="lingua-target-lang">
                                <?php foreach ( $active_languages as $lang ) : ?>
                                    <?php if( $lang->code !== $default_lang_obj->code ) : ?>
                                        <option value="<?php echo esc_attr($lang->code); ?>"><?php echo esc_html( $lang->native_name ); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <textarea name="text_source" class="lingua-source-text" placeholder="Texte..."></textarea>
                            <button type="button" class="lingua-btn-translate button button-primary" style="width:100%; margin-top:10px;">Traduire ➜</button>
                        </div>
                        <div>
                            <label class="lingua-field-label">Résultat</label>
                            <textarea id="result_microsoft" class="lingua-result-text" readonly></textarea>
                            <div id="error_microsoft" class="lingua-error-msg"></div>
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- Fin de la grid -->
    </div>

        </div>
    </div>

       <!-- ONGLET 3 : PARAMÈTRES -->
    <div id="settings" class="tab-content" style="display: none;">
        
        <!-- Carte 1 : Comportement IA -->
        <div class="lingua-dash-card" style="max-width: 800px; margin-bottom: 20px;">
            <h3>🧠 Comportement de l'Intelligence Artificielle</h3>
            <p class="description">Définissez comment l'IA doit traduire votre contenu. Ces réglages s'appliquent à toutes les traductions automatiques.</p>

            <form id="lingua-ai-settings-form">
                <table class="form-table">
                    
                    <!-- Moteur par défaut -->
                    <tr>
                        <th scope="row"><label for="default_engine">Moteur par défaut</label></th>
                        <td>
                            <select name="default_engine" id="default_engine" class="regular-text">
                                <option value="openrouter" <?php selected( isset($settings['default_engine']) ? $settings['default_engine'] : '', 'openrouter' ); ?>>🌐 OpenRouter (Polyvalent)</option>
                                <option value="deepl" <?php selected( isset($settings['default_engine']) ? $settings['default_engine'] : '', 'deepl' ); ?>>⚡ DeepL (Qualité Linguistique)</option>
                                <option value="deepseek" <?php selected( isset($settings['default_engine']) ? $settings['default_engine'] : '', 'deepseek' ); ?>>🇨🇳 DeepSeek (Économique)</option>
                                <option value="google" <?php selected( isset($settings['default_engine']) ? $settings['default_engine'] : '', 'google' ); ?>>🔵 Google (Couverture)</option>
                                <option value="mistral" <?php selected( isset($settings['default_engine']) ? $settings['default_engine'] : '', 'mistral' ); ?>>🇪🇺 Mistral (Européen)</option>
                            </select>
                            <p class="description">Moteur utilisé pour les actions "Traduire avec IA" dans la liste des produits.</p>
                        </td>
                    </tr>

                    <!-- Ton de l'IA -->
                    <tr>
                        <th scope="row"><label for="ai_tone">Ton de la Traduction</label></th>
                        <td>
                            <select name="ai_tone" id="ai_tone" class="regular-text">
                                <option value="commercial" <?php selected( $ai_tone, 'commercial' ); ?>>🛒 Commercial (Persuasif & Vendeur)</option>
                                <option value="neutral" <?php selected( $ai_tone, 'neutral' ); ?>>📝 Neutre (Informatif & Fidèle)</option>
                                <option value="creative" <?php selected( $ai_tone, 'creative' ); ?>>🎨 Créatif (Marketing & Accrocheur)</option>
                                <option value="formal" <?php selected( $ai_tone, 'formal' ); ?>>🤵 Formel (Professionnel Strict)</option>
                            </select>
                        </td>
                    </tr>

                    <!-- Instructions Personnalisées -->
                    <tr>
                        <th scope="row"><label for="custom_instructions">Instructions Personnalisées</label></th>
                        <td>
                            <textarea name="custom_instructions" id="custom_instructions" rows="4" class="large-text" placeholder="Ex: Ne jamais traduire les noms de marques. Toujours utiliser le tutoiement."><?php echo esc_textarea( isset($settings['custom_instructions']) ? $settings['custom_instructions'] : '' ); ?></textarea>
                            <p class="description">Ajoutez des règles spécifiques que l'IA doit suivre (Prompt Système additionnel).</p>
                        </td>
                    </tr>

                    <!-- Validation -->
                    <tr>
                        <th scope="row">Validation Humaine</th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_validate" value="1" <?php checked( $auto_validate, 1 ); ?>>
                                Publier automatiquement les traductions IA (Mode "Auto-pilote")
                            </label>
                            <p class="description" style="color:#d63638;">⚠️ Déconseillé : Laisser ce choix décoché permet de valider manuellement, ce qui garantit la qualité.</p>
                        </td>
                    </tr>

                </table>
                
                <p class="submit">
                    <?php submit_button( 'Sauvegarder les paramètres', 'primary', 'submit-settings', false ); ?>
                </p>
            </form>
        </div>
    </div>
    
        <!-- ONGLET 4 : FILE D'ATTENTE -->
    <div id="queue" class="tab-content" style="display: none;">
        
        <!-- CSS Spécifique à la File (Injection locale pour la démo) -->
        <style>
            .lingua-queue-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: #fff; padding: 15px; border: 1px solid #c3c4c7; border-radius: 4px; }
            .lingua-status-badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; color: #fff; text-transform: uppercase; }
            .status-pending { background-color: #dba617; }
            .status-processing { background-color: #2271b1; }
            .status-error { background-color: #d63638; }
            .status-done { background-color: #00a32a; }
            .lingua-progress-bar { height: 8px; background: #eee; border-radius: 4px; margin-top: 5px; overflow: hidden; }
            .lingua-progress-fill { height: 100%; background: #2271b1; width: 0%; transition: width 0.5s; }
        </style>

        <!-- En-tête et Actions Rapides -->
        <div class="lingua-queue-actions">
            <div>
                <h2 style="margin:0;">État de la File de Traduction</h2>
                <p class="description" style="margin:0;">Gérez les tâches en attente, en cours ou échouées.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="button" id="lingua-clear-queue" title="Supprime toutes les tâches en attente">🗑️ Vider la file</button>
                <button class="button button-primary" id="lingua-force-process" title="Lance manuellement le traitement">🚀 Forcer le traitement</button>
            </div>
        </div>

        <!-- Mini Dashboard (Stats) -->
        <div class="lingua-dashboard-grid" style="margin-bottom: 25px;">
             <div class="lingua-dash-card" style="border-left-color: #dba617;">
                <h3>⏳ En attente</h3>
                <p class="description">Prêts à être traduits.</p>
                <div class="big-number" id="queue-count-pending">12</div>
            </div>
             <div class="lingua-dash-card" style="border-left-color: #2271b1;">
                <h3>🔄 En cours</h3>
                <p class="description">Traitement actif.</p>
                <div class="big-number" id="queue-count-processing">1</div>
            </div>
             <div class="lingua-dash-card" style="border-left-color: #d63638;">
                <h3>❌ Échecs</h3>
                <p class="description">Nécessitent attention.</p>
                <div class="big-number" id="queue-count-error">0</div>
            </div>
        </div>

        <!-- Barre de progression globale -->
        <div style="background: #fff; padding: 15px; border: 1px solid #c3c4c7; margin-bottom: 20px; border-radius: 4px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                <strong>Progression Globale</strong>
                <span id="lingua-progress-percent">0%</span>
            </div>
            <div class="lingua-progress-bar">
                <div class="lingua-progress-fill" id="lingua-progress-bar-fill"></div>
            </div>
            <p class="description" style="margin-top:5px;">Traitement automatique en arrière-plan (Cron) toutes les minutes.</p>
        </div>

        <!-- Formulaire et Tableau -->
        <form id="lingua-queue-form" method="post">
            
            <!-- Filtres et Actions Groupées -->
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <label for="bulk-action-selector-top" class="screen-reader-text">Sélectionner l'action groupée</label>
                    <select name="action" id="bulk-action-selector-top">
                        <option value="-1">Actions groupées</option>
                        <option value="delete">Supprimer</option>
                        <option value="retry">Relancer la traduction</option>
                    </select>
                    <input type="submit" id="doaction" class="button action" value="Appliquer">
                </div>
                
                               <div class="alignleft actions">
                    <select name="status_filter" id="status_filter">
                        <option value="all">Tous les statuts</option>
                        <option value="pending">En attente</option>
                        <option value="processing">En cours</option>
                        <option value="error">Échoué</option>
                    </select>
                    
                    <!-- LISTE DYNAMIQUE SYNCHRONISÉE -->
                    <select name="type_filter" id="type_filter">
                        <option value="all">Tous les contenus</option>
                        <?php 
                        if ( isset( $content_types ) && is_array( $content_types ) ) :
                            foreach ( $content_types as $key => $label ) : 
                        ?>
                            <option value="<?php echo esc_attr( $key ); ?>">
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php 
                            endforeach; 
                        endif; 
                        ?>
                    </select>
                    
                    <input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filtrer">
                </div>

            <!-- Le Tableau -->
            <table class="wp-list-table widefat fixed striped table-view-list queue-table">
                <thead>
                    <tr>
                        <td class="check-column"><input type="checkbox" id="select-all-queue"></td>
                        <th scope="col" class="column-primary">Contenu</th>
                        <th scope="col" style="width: 15%;">Champ</th>
                        <th scope="col" style="width: 10%;">Langues</th>
                        <th scope="col" style="width: 10%;">Moteur</th>
                        <th scope="col" style="width: 10%;">Statut</th>
                        <th scope="col" style="width: 15%;">Date</th>
                        <th scope="col" style="width: 10%;">Actions</th>
                    </tr>
                </thead>
                <tbody id="the-queue-list">
                    
                    <!-- EXEMPLE LIGNE 1 (En attente) -->
                    <tr>
                        <th scope="row" class="check-column"><input type="checkbox" name="item[]" value="1"></th>
                        <td class="column-primary has-row-actions" data-colname="Contenu">
                            <strong><a href="#">Huile de Coco Vierge 500ml</a></strong>
                            <div class="row-actions">
                                <span class="product">Produit</span> | ID: 124
                            </div>
                        </td>
                        <td data-colname="Champ">Description longue</td>
                        <td data-colname="Langues">FR ➔ EN</td>
                        <td data-colname="Moteur">DeepSeek</td>
                        <td data-colname="Statut">
                            <span class="lingua-status-badge status-pending">En attente</span>
                        </td>
                        <td data-colname="Date">Il y a 5 min</td>
                        <td data-colname="Actions">
                            <button class="button button-small" title="Traiter maintenant">▶️</button>
                            <button class="button button-small" title="Supprimer" style="color:#b32d2e;">❌</button>
                        </td>
                    </tr>

                    <!-- EXEMPLE LIGNE 2 (Erreur) -->
                    <tr>
                        <th scope="row" class="check-column"><input type="checkbox" name="item[]" value="2"></th>
                        <td class="column-primary has-row-actions" data-colname="Contenu">
                            <strong><a href="#">Vitamine C 1000mg</a></strong>
                            <div class="row-actions">
                                <span class="product">Produit</span> | ID: 128
                            </div>
                        </td>
                        <td data-colname="Champ">Meta Title</td>
                        <td data-colname="Langues">FR ➔ ES</td>
                        <td data-colname="Moteur">OpenRouter</td>
                        <td data-colname="Statut">
                            <span class="lingua-status-badge status-error" title="Erreur API: Rate Limit">Erreur</span>
                        </td>
                        <td data-colname="Date">Il y a 12 min</td>
                        <td data-colname="Actions">
                            <button class="button button-small" title="Réessayer">🔄</button>
                            <button class="button button-small" title="Supprimer" style="color:#b32d2e;">❌</button>
                        </td>
                    </tr>

                    <!-- EXEMPLE LIGNE 3 (En cours) -->
                    <tr>
                        <th scope="row" class="check-column"><input type="checkbox" name="item[]" value="3"></th>
                        <td class="column-primary has-row-actions" data-colname="Contenu">
                            <strong><a href="#">Page Accueil</a></strong>
                            <div class="row-actions">
                                <span class="page">Page</span> | ID: 2
                            </div>
                        </td>
                        <td data-colname="Champ">Contenu</td>
                        <td data-colname="Langues">FR ➔ DE</td>
                        <td data-colname="Moteur">DeepL</td>
                        <td data-colname="Statut">
                            <span class="lingua-status-badge status-processing">En cours</span>
                        </td>
                        <td data-colname="Date">Il y a 1 min</td>
                        <td data-colname="Actions">
                            <button class="button button-small" disabled>⏳</button>
                        </td>
                    </tr>

                </tbody>
                <tfoot>
                    <tr>
                        <td class="check-column"><input type="checkbox"></td>
                        <th scope="col">Contenu</th>
                        <th scope="col">Champ</th>
                        <th scope="col">Langues</th>
                        <th scope="col">Moteur</th>
                        <th scope="col">Statut</th>
                        <th scope="col">Date</th>
                        <th scope="col">Actions</th>
                    </tr>
                </tfoot>
            </table>
        </form>
    </div>
    
        <!-- ONGLET 5 : LOGS & HISTORIQUE -->
    <div id="logs" class="tab-content" style="display: none;">
        
        <style>
            .log-viewer-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100000; }
            .log-viewer-content { background: #fff; width: 600px; margin: 100px auto; padding: 20px; border-radius: 8px; max-height: 400px; overflow-y: auto; }
        </style>

        <!-- Dashboard Stats Logs -->
        <div class="lingua-dashboard-grid" style="margin-bottom: 25px;">
             <div class="lingua-dash-card" style="border-left-color: #2271b1;">
                <h3>📊 Total Tokens</h3>
                <p class="description">Consommation totale.</p>
                <div class="big-number" id="logs-total-tokens">0</div>
            </div>
             <div class="lingua-dash-card" style="border-left-color: #00a32a;">
                <h3>💰 Coût Estimé</h3>
                <p class="description">Basé sur tarifs publics.</p>
                <div class="big-number" id="logs-total-cost">0.00 $</div>
            </div>
             <div class="lingua-dash-card" style="border-left-color: #d63638;">
                <h3>❌ Erreurs</h3>
                <p class="description">À corriger.</p>
                <div class="big-number" id="logs-total-errors">0</div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
            <h2 style="margin:0;">Historique des Opérations</h2>
            <button class="button" id="lingua-clear-logs">🗑️ Vider l'historique</button>
        </div>

        <!-- Tableau des Logs -->
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th scope="col" style="width: 15%;">Date</th>
                    <th scope="col" style="width: 25%;">Événement</th>
                    <th scope="col" style="width: 10%;">Moteur</th>
                    <th scope="col" style="width: 10%;">Tokens</th>
                    <th scope="col" style="width: 10%;">Coût</th>
                    <th scope="col" style="width: 10%;">Statut</th>
                    <th scope="col" style="width: 10%;">Détails</th>
                </tr>
            </thead>
            <tbody id="the-logs-list">
                <tr><td colspan="7" style="text-align:center;">Chargement...</td></tr>
            </tbody>
        </table>
        
        <!-- Fenêtre Modale pour les détails -->
        <div id="lingua-log-viewer" class="log-viewer-modal">
            <div class="log-viewer-content">
                <h3>Détails de l'entrée</h3>
                <pre id="log-details-content" style="background: #f0f0f1; padding: 15px; white-space: pre-wrap;"></pre>
                <button class="button" onclick="jQuery('#lingua-log-viewer').hide();">Fermer</button>
            </div>
        </div>
    </div>

<!-- JAVASCRIPT -->
<script>
jQuery(document).ready(function($) {
    // Onglets
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').hide();
        $($(this).attr('href')).show();
    });

    // Sauvegarde Config
    $('.lingua-config-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var btn = $form.find('button[type="submit"]');
        var originalText = btn.text();
        
        btn.prop('disabled', true).text('Sauvegarde...');

        var modelPaidVal = $form.find('[name="model_paid"]').val();
        var modelFreeVal = $form.find('[name="model_free"]').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_save_engine',
                nonce: '<?php echo wp_create_nonce( 'lingua_admin_nonce' ); ?>',
                engine_name: $form.find('input[name="engine_name"]').val(),
                api_key: $form.find('input[name="api_key"]').val(),
                model_paid: modelPaidVal,
                model_free: modelFreeVal,
                priority: 10
            },
            success: function(res) {
                if(res.success) {
                    btn.text('✅ Sauvé !');
                    setTimeout(function(){ btn.prop('disabled', false).text(originalText); },2000);
                } else {
                    alert('Erreur : ' + res.data);
                    btn.prop('disabled', false).text(originalText);
                }
            }
        });
    });

    // Test Traduction
    $('.lingua-btn-translate').on('click', function() {
        var $card = $(this).closest('.lingua-engine-card');
        var $resultBox = $card.find('.lingua-result-text');
        var $errorBox = $card.find('.lingua-error-msg');
        
        $resultBox.val('...').removeClass('error-state');
        $errorBox.hide();

        var engineName = $card.find('input[name="engine_name"]').val();
        var apiKey = $card.find('input[name="api_key"]').val();
        var modelPaid = $card.find('[name="model_paid"]').val();
        var modelFree = $card.find('[name="model_free"]').val();
        var targetLang = $card.find('.lingua-target-lang').val();
        var textSource = $card.find('.lingua-source-text').val();

        if(!textSource) { $errorBox.text('Texte vide').show(); return; }
        if(!apiKey) { $errorBox.text('Clé API manquante').show(); return; }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_test_engine',
                nonce: '<?php echo wp_create_nonce( 'lingua_admin_nonce' ); ?>',
                engine_name: engineName,
                api_key: apiKey,
                model_paid: modelPaid,
                model_free: modelFree,
                target_lang: targetLang,
                text_source: textSource
            },
            success: function(res) {
                if(res.success) {
                    $resultBox.val(res.data.translated_text);
                } else {
                    $resultBox.val('Échec');
                    $errorBox.text('Erreur : ' + res.data).show();
                }
            }
        });
    });
    
        // --- SAUVEGARDE PARAMÈTRES GLOBAUX (AJOUTÉ) ---
    $('#lingua-ai-settings-form').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#submit-settings');
        var originalText = btn.val();
        
        btn.prop('disabled', true).val('Sauvegarde...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_save_ai_settings',
                nonce: '<?php echo wp_create_nonce( 'lingua_admin_nonce' ); ?>',
                ai_tone: $('#ai_tone').val(),
                default_engine: $('#default_engine').val(),
                custom_instructions: $('#custom_instructions').val(),
                auto_validate: $('input[name="auto_validate"]').is(':checked') ? 1 : 0
            },
            success: function(res) {
                if(res.success) {
                    btn.val('✅ Sauvegardé !');
                    setTimeout(function(){ btn.val(originalText).prop('disabled', false); }, 2000);
                } else {
                    alert('Erreur : ' + res.data);
                    btn.val(originalText).prop('disabled', false);
                }
            },
            error: function() {
                alert('Erreur de connexion serveur.');
                btn.val(originalText).prop('disabled', false);
            }
        });
    });
    
        // --- GESTION FILE D'ATTENTE (QUEUE) ---
    
    // 1. Charger la file quand on clique sur l'onglet
    $('a[href="#queue"]').on('click', function() {
        loadQueueItems();
    });

        // Fonction de chargement
    function loadQueueItems(status = 'all', type = 'all') {
        $('#the-queue-list').html('<tr><td colspan="8" style="text-align:center;">Chargement...</td></tr>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_get_queue_items',
                nonce: '<?php echo wp_create_nonce( 'lingua_admin_nonce' ); ?>',
                status: status,
                type: type // On envoie le type choisi
            },
            success: function(res) {
                if(res.success) {
                    $('#the-queue-list').html(res.data.html);
                    
                    // Mise à jour des compteurs
                    $('#queue-count-pending').text(res.data.counts.pending);
                    $('#queue-count-processing').text(res.data.counts.processing);
                    $('#queue-count-error').text(res.data.counts.error);
                }
            }
        });
    }
    // 2. Action : Supprimer une tâche
    $(document).on('click', '.queue-btn-delete', function() {
        var id = $(this).data('id');
        if(!confirm('Supprimer cette tâche ?')) return;
        
        var btn = $(this);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_delete_queue_item',
                nonce: '<?php echo wp_create_nonce( 'lingua_admin_nonce' ); ?>',
                id: id
            },
            success: function(res) {
                if(res.success) {
                    btn.closest('tr').fadeOut(300, function(){ $(this).remove(); });
                } else {
                    alert(res.data);
                }
            }
        });
    });

    // 3. Filtre Statut
    $('#status_filter').on('change', function() {
        loadQueueItems( $(this).val() );
    });
    
        // 3. Filtres Dynamiques
    $('#status_filter, #type_filter').on('change', function() {
        var current_status = $('#status_filter').val();
        var current_type = $('#type_filter').val();
        loadQueueItems(current_status, current_type);
    });
    
        // --- GESTION LOGS & HISTORIQUE ---
    
    // Charger les logs quand on clique sur l'onglet
    $('a[href="#logs"]').on('click', function() {
        loadLogs();
    });

    function loadLogs() {
        $('#the-logs-list').html('<tr><td colspan="7" style="text-align:center;">Chargement...</td></tr>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_get_logs',
                nonce: '<?php echo wp_create_nonce( 'lingua_admin_nonce' ); ?>'
            },
            success: function(res) {
                if(res.success) {
                    $('#the-logs-list').html(res.data.html);
                    // Mise à jour stats
                    $('#logs-total-tokens').text(numberWithCommas(res.data.stats.tokens));
                    $('#logs-total-cost').text(res.data.stats.cost + ' $');
                    $('#logs-total-errors').text(res.data.stats.errors);
                }
            }
        });
    }

    // Helper format nombre
    function numberWithCommas(x) { return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","); }

    // Voir les détails
    $(document).on('click', '.view-log-details', function() {
        var details = $(this).data('details');
        $('#log-details-content').text(details);
        $('#lingua-log-viewer').show();
    });

    // Vider les logs
    $('#lingua-clear-logs').on('click', function() {
        if(!confirm('Voulez-vous vraiment supprimer tout l\'historique ?')) return;
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_clear_logs',
                nonce: '<?php echo wp_create_nonce( 'lingua_admin_nonce' ); ?>'
            },
            success: function(res) {
                if(res.success) loadLogs();
            }
        });
    });
    
});
</script>