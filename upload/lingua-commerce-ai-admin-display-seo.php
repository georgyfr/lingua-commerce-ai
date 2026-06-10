<?php
/**
 * Page d'administration SEO Multilingue
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/admin/partials
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

// Récupérer les réglages actuels
 $settings = get_option( 'lingua_commerce_ai_settings', array() );

// --- DÉTECTION AMÉLIORÉE DES PLUGINS SEO ---
// On utilise 'defined' et 'class_exists' car c'est plus fiable que is_plugin_active
// (Cela fonctionne même pour les versions Premium qui ont des chemins différents)

// 1. Yoast SEO
 $has_yoast = defined( 'WPSEO_VERSION' ) || class_exists( 'Yoast\WP\SEO\Main' );

// 2. RankMath
 $has_rankmath = defined( 'RANK_MATH_VERSION' );

// 3. All in One SEO Pack (AJOUTÉ)
 $has_aioseo = defined( 'AIOSEO_VERSION' ) || class_exists( 'All_in_One_SEO_Pack' );

?>

<div class="wrap lingua-seo-page">
    <h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <hr class="wp-header-end">

    <div class="lingua-seo-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
        
            <!-- ZONE S : VISUALISATEUR DE SYNCHRONISATION (VERSION CORRIGÉE) -->
    <div class="lingua-card" style="grid-column: 1 / -1; background: #e7f3ff; border: 1px solid #c3c4c7; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px;">
        <h2 style="margin-top: 0; font-size: 1.2em; display: flex; align-items: center;">
            <span class="dashicons dashicons-update" style="margin-right: 10px; color: #2271b1;"></span>
            État de Synchronisation Multilingue
        </h2>
        
        <div style="display: flex; align-items: center; gap: 30px; margin-top: 15px;">
            
            <div style="flex-grow: 1;">
                <div style="font-size: 13px; font-weight: bold; color: #333;">Langue Source (Maître)</div>
                <div style="margin-top: 5px;">
                    <?php 
                        // CORRECTION : PHP écrit maintenant DIRECTEMENT dans la div principale
                        if ( isset( $default_lang ) && $default_lang && isset( $default_lang->native_name ) ) : ?>
                            <div style="font-size: 24px; color: #2271b1; font-weight: bold;">
                                <?php echo esc_html( $default_lang->native_name ); ?>
                            </div>
                            <div style="font-size: 12px; color: #666; margin-top: 2px;">
                                Code : <?php echo esc_html( $default_lang->code ); ?>
                            </div>
                        <?php else : ?>
                            <div style="font-size: 24px; color: #dba617; font-weight: bold;">
                                Non définie
                            </div>
                            <div style="font-size: 12px; color: #666; margin-top: 2px;">
                                (Aucune langue configurée)
                            </div>
                        <?php endif; ?>
                </div>
            </div>
            
            <div style="border-left: 2px solid #ddd; padding-left: 20px;">
                <div style="font-size: 13px; font-weight: bold; color: #333;">Statut Audit SEO</div>
                <div style="margin-top: 5px;">
                    <?php 
                    if ( isset( $default_lang ) && $default_lang ) : ?>
                        <span style="color: #00a32a; font-weight: bold;">✓ Synchronisé</span><br>
                        <small>L'audit SEO utilise bien cette langue comme source de référence.</small>
                    <?php else : ?>
                        <span style="color: #dba617; font-weight: bold;">⚠ Avertissement</span><br>
                        <small>Aucune langue par défaut n'a été trouvée dans la base de données.</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>      
            <!-- ZONE 0 : TABLEAU DE BORD DE SANTÉ TECHNIQUE -->
    <div class="lingua-card" style="grid-column: 1 / -1; background: #fff; border: 1px solid #c3c4c7; padding: 20px; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); margin-bottom: 20px;">
        <h2 style="margin-top: 0; font-size: 1.3em; display: flex; align-items: center;">
            <span class="dashicons dashicons-dashboard" style="margin-right: 10px; color: #dba617;"></span>
            État de Santé Technique SEO
        </h2>
        <p class="description">Vérification rapide de l'environnement pour garantir l'indexation.</p>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-top: 15px;">
            
            <!-- 1. CONFLITS PLUGINS -->
            <div style="border-left: 4px solid #ddd; padding-left: 15px;">
                <strong>🛡️ Conflits de Plugins</strong><br>
                <small>Recherche de multiples plugins SEO actifs.</small>
                <div style="margin-top: 8px;">
                    <?php 
                    $conflicts = 0;
                    $seo_plugins = array();
                    if ( $has_yoast ) { $seo_plugins[] = 'Yoast SEO'; $conflicts++; }
                    if ( $has_rankmath ) { $seo_plugins[] = 'RankMath'; $conflicts++; }
                    if ( $has_aioseo ) { $seo_plugins[] = 'All in One SEO'; $conflicts++; }

                    if ( $conflicts > 1 ) : ?>
                        <span style="color: #dba617; font-weight: bold;">⚠️ Conflit détecté !</span>
                        <div style="font-size: 12px; margin-top: 5px; color: #666;">
                            Vous avez activé : <?php echo implode( ' et ', $seo_plugins ); ?>.<br>
                            Cela peut créer des balises méta en double. Gardez-en un seul actif.
                        </div>
                    <?php elseif ( $conflicts == 1 ) : ?>
                        <span style="color: #00a32a; font-weight: bold;">✓ Pas de conflit</span>
                        <div style="font-size: 12px; margin-top: 5px; color: #666;">
                            <?php echo implode( ' est actif.', $seo_plugins ); ?>
                        </div>
                    <?php else : ?>
                        <span style="color: #666;">Aucun plugin SEO majeur.</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. ANALYSE ROBOTS.TXT -->
            <div style="border-left: 4px solid #ddd; padding-left: 15px;">
                <strong>🤖 Analyse Robots.txt</strong><br>
                <small>Vérification des règles d'indexation.</small>
                <div style="margin-top: 8px;">
                    <?php 
                    $robots_file = ABSPATH . 'robots.txt';
                    $robots_status = 'unknown';
                    $robots_msg = '';

                    if ( file_exists( $robots_file ) ) {
                        $robots_content = file_get_contents( $robots_file );
                        // On vérifie s'il y a une règle qui bloque les paramètres
                        if ( strpos( $robots_content, 'Disallow' ) !== false && strpos( $robots_content, '?' ) !== false ) {
                            $robots_status = 'error';
                            $robots_msg = 'Risque de blocage des URLs ?lang=xx.';
                        } else {
                            $robots_status = 'ok';
                            $robots_msg = 'Aucun blocage détecté.';
                        }
                    } else {
                        $robots_status = 'missing';
                        $robots_msg = 'Fichier robots.txt introuvable.';
                    }

                    if ( $robots_status === 'error' ) : ?>
                        <span style="color: #d63638; font-weight: bold;">❌ Alerte Bloquage</span>
                        <div style="font-size: 12px; margin-top: 5px; color: #666;"><?php echo esc_html( $robots_msg ); ?></div>
                    <?php elseif ( $robots_status === 'ok' ) : ?>
                        <span style="color: #00a32a; font-weight: bold;">✓ Autorisé</span>
                        <div style="font-size: 12px; margin-top: 5px; color: #666;"><?php echo esc_html( $robots_msg ); ?></div>
                    <?php else : ?>
                        <span style="color: #dba617;">⚠️ <?php echo esc_html( $robots_msg ); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3. SANTÉ SITEMAP (CORRIGÉ) -->
            <div style="border-left: 4px solid #ddd; padding-left: 15px;">
                <strong>📂 Sitemap XML</strong><br>
                <small>Disponibilité et âge du fichier.</small>
                <div style="margin-top: 8px;">
                    <?php 
                    $sitemap_file = ABSPATH . 'sitemap-lingua.xml';
                    // CORRECTION : On remplace { par : pour être cohérent avec le else : plus bas
                    if ( file_exists( $sitemap_file ) ) : 
                        $mtime = filemtime( $sitemap_file );
                        $time_diff = time() - $mtime;
                        $days_ago = floor( $time_diff / (60 * 60 * 24) );
                        
                        if ( $days_ago > 7 ) : ?>
                            <span style="color: #dba617; font-weight: bold;">⚠️ Vieux</span>
                            <div style="font-size: 12px; margin-top: 5px; color: #666;">Généré il y a <?php echo $days_ago; ?> jours.</div>
                        <?php else : ?>
                            <span style="color: #00a32a; font-weight: bold;">✓ À jour</span>
                            <div style="font-size: 12px; margin-top: 5px; color: #666;">Généré récemment.</div>
                        <?php endif;
                    else : ?>
                        <span style="color: #d63638; font-weight: bold;">❌ Introuvable</span>
                        <div style="font-size: 12px; margin-top: 5px; color: #666;">Le fichier n'existe pas encore.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
    
        
        <!-- ZONE 1 : RADAR D'INTÉGRATION (CORRIGÉ) -->
        <div class="lingua-card" style="background: #fff; border: 1px solid #c3c4c7; padding: 20px; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <h2 style="margin-top: 0; font-size: 1.2em; display: flex; align-items: center;">
                <span class="dashicons dashicons-admin-plugins" style="margin-right: 10px; color: #2271b1;"></span>
                Intégration Plugins SEO
            </h2>
            <p class="description">Détection automatique de vos outils de référencement.</p>
            
            <div style="margin-top: 15px;">
                <!-- YOAST SEO -->
                <?php if ( $has_yoast ): ?>
                    <div style="background: #e7f7ed; border-left: 4px solid #00a32a; padding: 10px; margin-bottom: 10px; display: flex; align-items: center;">
                        <span class="dashicons dashicons-yes-alt" style="color: #00a32a; margin-right: 10px;"></span>
                        <strong>Yoast SEO</strong> est actif. Intégration optimale.
                    </div>
                <?php else: ?>
                    <div style="background: #fff0f1; border-left: 4px solid #d63638; padding: 10px; margin-bottom: 10px; display: flex; align-items: center; opacity: 0.6;">
                        <span class="dashicons dashicons-dismiss" style="color: #d63638; margin-right: 10px;"></span>
                        Yoast SEO non détecté.
                    </div>
                <?php endif; ?>

                <!-- RANKMATH -->
                <?php if ( $has_rankmath ): ?>
                    <div style="background: #e7f7ed; border-left: 4px solid #00a32a; padding: 10px; margin-bottom: 10px; display: flex; align-items: center;">
                        <span class="dashicons dashicons-yes-alt" style="color: #00a32a; margin-right: 10px;"></span>
                        <strong>RankMath</strong> est actif. Intégration optimale.
                    </div>
                <?php else: ?>
                    <div style="background: #fff0f1; border-left: 4px solid #d63638; padding: 10px; margin-bottom: 10px; display: flex; align-items: center; opacity: 0.6;">
                        <span class="dashicons dashicons-dismiss" style="color: #d63638; margin-right: 10px;"></span>
                        RankMath non détecté.
                    </div>
                <?php endif; ?>

                <!-- ALL IN ONE SEO (AJOUTÉ) -->
                <?php if ( $has_aioseo ): ?>
                    <div style="background: #e7f7ed; border-left: 4px solid #00a32a; padding: 10px; margin-bottom: 10px; display: flex; align-items: center;">
                        <span class="dashicons dashicons-yes-alt" style="color: #00a32a; margin-right: 10px;"></span>
                        <strong>All in One SEO</strong> est actif. Intégration optimale.
                    </div>
                <?php else: ?>
                    <div style="background: #fff0f1; border-left: 4px solid #d63638; padding: 10px; margin-bottom: 10px; display: flex; align-items: center; opacity: 0.6;">
                        <span class="dashicons dashicons-dismiss" style="color: #d63638; margin-right: 10px;"></span>
                        All in One SEO non détecté.
                    </div>
                <?php endif; ?>
                
                <!-- MESSAGE SI AUCUN PLUGIN -->
                <?php if ( !$has_yoast && !$has_rankmath && !$has_aioseo ): ?>
                    <div class="notice notice-warning inline">
                        <p>Aucun plugin SEO majeur détecté. L'optimisation automatique des méta-données sera limitée.</p>
                    </div>
                <?php endif; ?>
            </div>
            
                <!-- ZONE DE DIAGNOSTIC (Pour valider la sauvegarde) -->
    <div class="lingua-card" style="background: #f9f9f9; border: 1px dashed #bbb; padding: 15px; margin-top: 30px; border-radius: 4px;">
        <h3 style="margin-top:0;">🔍 Diagnostic de Sauvegarde</h3>
        <p style="font-size: 13px; color: #666;">Cette zone affiche ce qui est réellement stocké dans la base de données WordPress. Si tu changes les réglages ci-dessus et que tu cliques sur "Sauvegarder", les valeurs ci-dessous doivent se mettre à jour.</p>
        
        <table class="widefat fixed" style="width: auto; min-width: 400px;">
            <thead>
                <tr>
                    <th>Réglage</th>
                    <th>Valeur enregistrée en BDD</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>IA SEO Activée</strong></td>
                    <td>
                        <?php 
                        $ai_enabled = isset( $settings['seo_ai_enabled'] ) ? $settings['seo_ai_enabled'] : '0';
                        echo ($ai_enabled == '1') ? '<span style="color:green; font-weight:bold;">OUI (1)</span>' : '<span style="color:red;">NON (0)</span>'; 
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Stratégie Mots-Clés</strong></td>
                    <td>
                        <code><?php echo isset( $settings['keyword_strategy'] ) ? esc_html( $settings['keyword_strategy'] ) : 'Non défini'; ?></code>
                    </td>
                </tr>
                <tr>
                    <td><strong>Tonalité Contenu</strong></td>
                    <td>
                        <code><?php echo isset( $settings['content_tone'] ) ? esc_html( $settings['content_tone'] ) : 'Non défini'; ?></code>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
        </div>
        <!-- ZONE 2 : CONFIGURATION IA SEO (COMPLÈTE ET CORRIGÉE) -->
        <div class="lingua-card" style="background: #fff; border: 1px solid #c3c4c7; padding: 20px; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
            <h2 style="margin-top: 0; font-size: 1.2em; display: flex; align-items: center;">
                <span class="dashicons dashicons-editor-spellcheck" style="margin-right: 10px; color: #dba617;"></span>
                Moteur IA SEO & Prompting
            </h2>
            <p class="description">Paramètres avancés pour la génération des titres et descriptions optimisés.</p>

           <form method="post" action="options.php">
    <?php settings_fields( 'lingua_commerce_ai_settings_group' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row" style="width: 40%;">Activer l'Optimisation SEO</th>
                        <td>
                            <label class="switch">
                                <input type="checkbox" name="lingua_commerce_ai_settings[seo_ai_enabled]" value="1" <?php checked( isset( $settings['seo_ai_enabled'] ) ? $settings['seo_ai_enabled'] : 0, 1 ); ?>>
                                <span class="slider round"></span>
                            </label>
                            <p class="description">L'IA réécrira les traductions spécifiques pour le référencement.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="seo_engine_select">Moteur IA dédié SEO</label></th>
                        <td>
                            <select name="lingua_commerce_ai_settings[seo_engine]" id="seo_engine_select" class="regular-text">
                                <option value="default" <?php selected( isset( $settings['seo_engine'] ) ? $settings['seo_engine'] : '', 'default' ); ?>>Défaut (Celui du module Traduction)</option>
                                
                                <?php 
                                // DÉTECTION DYNAMIQUE DES MOTEURS
                                global $wpdb;
                                // Sécurité supplémentaire : on vérifie que la table existe avant de la requêter
                                $table_engines = $wpdb->prefix . 'lingua_ai_engines';
                                $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_engines'" );
                                
                                $active_engines = array();
                                if ( $table_exists ) {
                                    $active_engines = $wpdb->get_results( "SELECT engine_name, api_key FROM $table_engines WHERE status = 'active'" );
                                }
                                
                                if ( ! empty( $active_engines ) ) : ?>
                                    <optgroup label="Moteurs configurés et actifs">
                                    <?php foreach ( $active_engines as $eng ) : ?>
                                        <option value="<?php echo esc_attr( $eng->engine_name ); ?>" <?php selected( isset( $settings['seo_engine'] ) ? $settings['seo_engine'] : '', $eng->engine_name ); ?>>
                                            <?php echo esc_html( $eng->engine_name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    </optgroup>
                                <?php else : ?>
                                    <!-- FALLBACK : Si la table est vide ou inexistante -->
                                    <option value="" disabled>⚠️ Aucun moteur actif</option>
                                <?php endif; ?>

                                <optgroup label="Moteurs supportés (Configurez-les via IA & Automatisation)" style="color:#999;">
                                    <option value="openrouter" disabled>OpenRouter (Aggrégateur IA)</option>
                                    <option value="deepl" disabled>DeepL (Traduction pro)</option>
                                    <option value="deepseek" disabled>DeepSeek (Rapide & Eco)</option>
                                    <option value="gpt-4" disabled>OpenAI GPT-4 (Qualité)</option>
                                </optgroup>
                            </select>
                            
                            <?php if ( empty( $active_engines ) ) : ?>
                                <p class="description" style="color: #d63638;">
                                    Vous devez configurer les moteurs IA dans le menu <strong>IA & Automatisation</strong> avant de pouvoir les utiliser ici.
                                </p>
                            <?php else : ?>
                                <p class="description">
                                    Ce moteur sera utilisé spécifiquement pour générer les Meta Titres et Descriptions optimisés.
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="seo_creativity">Tonalité & Créativité (Température)</label></th>
                        <td>
                            <select name="lingua_commerce_ai_settings[content_tone]" id="seo_creativity" class="regular-text">
                                <option value="neutral" <?php selected( isset( $settings['content_tone'] ) ? $settings['content_tone'] : '', 'neutral' ); ?>>Neutre / Standard (Température 0.7)</option>
                                <option value="creative" <?php selected( isset( $settings['content_tone'] ) ? $settings['content_tone'] : '', 'creative' ); ?>>Créatif / Marketing (Température 1.0)</option>
                                <option value="conservative" <?php selected( isset( $settings['content_tone'] ) ? $settings['content_tone'] : '', 'conservative' ); ?>>Conservateur / Strict (Température 0.3)</option>
                                <option value="formal" <?php selected( isset( $settings['content_tone'] ) ? $settings['content_tone'] : '', 'formal' ); ?>>Formel / Professionnel</option>
                            </select>
                            <p class="description">Contrôle la "chaleur" de l'IA. Plus c'est créatif, plus l'IA invente des accroches marketing.</p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="keyword_strategy">Stratégie de Mots-Clés</label></th>
                        <td>
                            <select name="lingua_commerce_ai_settings[keyword_strategy]" id="keyword_strategy" class="regular-text">
                                <option value="literal" <?php selected( isset( $settings['keyword_strategy'] ) ? $settings['keyword_strategy'] : 'literal', 'literal' ); ?>>Traduction Littérale</option>
                                <option value="adapted" <?php selected( isset( $settings['keyword_strategy'] ) ? $settings['keyword_strategy'] : '', 'adapted' ); ?>>Adaptation au Marché Local</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="seo_title_length">Longueur Maxi Titre SEO</label></th>
                        <td>
                            <input type="number" name="lingua_commerce_ai_settings[seo_title_length]" id="seo_title_length" class="small-text" value="<?php echo isset( $settings['seo_title_length'] ) ? esc_attr( $settings['seo_title_length'] ) : '60'; ?>" max="100" min="30">
                            <span style="color:#666;"> caractères.</span>
                            <p class="description">Google affiche environ 60 caractères. Force l'IA à respecter cette limite.</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button( 'Sauvegarder les réglages IA' ); ?>
            </form>
        </div>
        
        
               <!-- ZONE 2.6 : TABLEAU DE BORD SEO AUDIT (CORRIGÉ) -->
    <div class="lingua-card" style="background: #fff; border: 1px solid #c3c4c7; padding: 20px; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); margin-bottom: 20px;">
        <h2 style="margin-top: 0; font-size: 1.2em; display: flex; align-items: center;">
            <span class="dashicons dashicons-chart-line" style="margin-right: 10px; color: #2271b1;"></span>
            Audit SEO des Contenus
        </h2>
        <p class="description">Vue synchronisée avec la page de traduction. Analyse des performances d'indexation.</p>

        <!-- Barre d'Outils (Filtres identiques à la page Traduction) -->
        <div style="background: #f6f7f7; padding: 15px; border-radius: 5px; margin-bottom: 15px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            
            <div style="display:flex; align-items:center; gap: 5px;">
                <label><strong>Contenu :</strong></label>
                <select id="lingua_seo_audit_type" class="regular-text" style="width: 200px;">
                    <option value="product">Produits WooCommerce</option>
                    <option value="page">Pages</option>
                    <option value="post">Articles</option>
                    <option value="product_cat">Catégories Produits</option>
                    <option value="attachment">Médias</option>
                </select>
            </div>

            <div style="display:flex; align-items:center; gap: 5px;">
                <label><strong>Langue :</strong></label>
                <select id="lingua_seo_audit_lang" class="regular-text" style="width: 150px;">
                    <?php 
                    // CORRECTION : On charge les langues ici pour éviter la liste vide
                    if ( ! class_exists( 'LinguaCommerce_Language_Service' ) ) {
                        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-lingua-language-service.php';
                    }
                    $audit_languages = LinguaCommerce_Language_Service::get_active_languages();
                    $default_lang_obj = LinguaCommerce_Language_Service::get_default_language();
                    $default_lang_code = $default_lang_obj ? $default_lang_obj->code : 'en_US';
                    
                    foreach ( $audit_languages as $lang ) : ?>
                        <option value="<?php echo esc_attr($lang->code); ?>" <?php selected( $lang->code, $default_lang_code ); ?>>
                            <?php echo esc_html( $lang->native_name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button id="lingua-btn-refresh-seo-audit" class="button">
                <span class="dashicons dashicons-update-alt" style="margin-top:4px;"></span> Actualiser Audit
            </button>
        </div>

        <!-- Tableau des Résultats -->
        <table class="widefat fixed striped" style="border: 1px solid #ddd;">
            <thead>
                <tr>
                    <th class="lingua-sticky-col" style="position: sticky; left: 0; background: #f9f9f9; border-right: 1px solid #ddd; z-index: 10;">Élément</th>
                    <th style="text-align:center; width: 120px;">Statut Traduction</th>
                    <th style="text-align:center; width: 100px;">Qualité SEO</th>
                    <th>Meta Title (Google)</th>
                    <th style="text-align:center; width: 100px;">Meta Desc</th>
                    <th style="text-align:right; width: 100px;">Action</th>
                </tr>
            </thead>
            <tbody id="lingua-seo-audit-body">
                <tr><td colspan="6" style="text-align:center; padding: 20px; color: #666;">Cliquez sur "Actualiser Audit" pour charger les données.</td></tr>
            </tbody>
        </table>
        
        <!-- Légende des Scores -->
        <div style="margin-top: 15px; font-size: 12px; color: #666; display: flex; gap: 15px;">
            <span><span style="color:#00a32a; font-weight:bold;">✓ Excellente</span> : Traduit + Titre SEO OK</span>
            <span><span style="color:#dba617; font-weight:bold;">⚠ Avertissement</span> : Traduit mais Pas de Titre SEO ou Trop long</span>
            <span><span style="color:#d63638; font-weight:bold;">❌ Mauvais</span> : Non traduit</span>
        </div>
    </div>

    <!-- SCRIPT JS POUR LE CHARGEMENT DE L'AUDIT -->
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        
        function loadSeoAudit() {
            var btn = $('#lingua-btn-refresh-seo-audit');
            var type = $('#lingua_seo_audit_type').val();
            var lang = $('#lingua_seo_audit_lang').val();
            var tbody = $('#lingua-seo-audit-body');

            // État de chargement
            btn.prop('disabled', true).text('Chargement...');
            tbody.html('<tr><td colspan="6" style="text-align:center; padding: 30px;">Analyse SEO en cours...</td></tr>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'lingua_get_seo_audit',
                    nonce: '<?php echo wp_create_nonce( 'lingua_admin_nonce' ); ?>',
                    post_type: type,
                    lang: lang
                },
                success: function(response) {
                    if (response.success) {
                        tbody.html(response.data.html);
                        btn.prop('disabled', false).html('<span class="dashicons dashicons-update-alt" style="margin-top:4px;"></span> Actualiser Audit');
                    } else {
                        tbody.html('<tr><td colspan="6" style="color:red;">Erreur : ' + (response.data || 'Inconnue') + '</td></tr>');
                        btn.prop('disabled', false).html('<span class="dashicons dashicons-update-alt" style="margin-top:4px;"></span> Réessayer');
                    }
                },
                error: function() {
                    tbody.html('<tr><td colspan="6" style="color:red;">Erreur serveur (Voir la console JS).</td></tr>');
                    btn.prop('disabled', false).html('<span class="dashicons dashicons-update-alt" style="margin-top:4px;"></span> Réessayer');
                }
            });
        }

        // Chargement initial
        loadSeoAudit();

        // Au clic sur le bouton
        $('#lingua-btn-refresh-seo-audit').on('click', function() {
            loadSeoAudit();
        });

        // Au changement de filtre
        $('#lingua_seo_audit_type, #lingua_seo_audit_lang').on('change', function() {
            loadSeoAudit();
        });
    });
    </script>
        
    <!-- ZONE 3 : TECHNIQUE (URLS) -->
    <div class="lingua-card" style="background: #fff; border: 1px solid #c3c4c7; padding: 20px; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); margin-top: 20px;">
        <h2 style="margin-top: 0; font-size: 1.2em;">Structure des URLs & Hreflang</h2>
        <p>Configuration de l'architecture des liens multilingues.</p>
        
        <div style="background: #f6f7f7; padding: 15px; border-radius: 4px; border: 1px solid #ddd;">
            <label><strong>Format d'URL :</strong></label><br><br>
            <label style="margin-right: 20px;">
                <input type="radio" name="url_format" value="param" checked> 
                <code>monsite.com/produit?lang=en</code> (Recommandé pour commencer)
            </label>
            <label>
                <input type="radio" name="url_format" value="subdirectory"> 
                <code>monsite.com/en/produit</code> (Nécessite configuration serveur)
            </label>
        </div>
        
        <p style="margin-top: 15px;">
            <strong>Statut Hreflang :</strong> 
            <span style="color: #00a32a; font-weight: bold;">ACTIF</span>
            <span class="description">- Les balises sont injectées automatiquement dans le &lt;head&gt;.</span>
        </p>
    </div>

       <!-- ZONE 4 : SITEMAP (CORRIGÉE) -->
    <div class="lingua-card" style="background: #fff; border: 1px solid #c3c4c7; padding: 20px; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); margin-top: 20px;">
        <h2 style="margin-top: 0; font-size: 1.2em;">Sitemap Multilingue</h2>
        <p>Génération du plan du site pour Google.</p>
        
        <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            
            <!-- Bouton de génération AJAX -->
            <button id="lingua-btn-generate-sitemap" class="button button-primary button-large">
                <span class="dashicons dashicons-update-alt" style="margin-top: 4px;"></span> Générer le Sitemap XML
            </button>

            <!-- Bouton de visualisation dynamique -->
            <?php 
            $sitemap_url = '';
            if ( $has_yoast || $has_rankmath ) {
                // Yoast et RankMath utilisent généralement sitemap_index.xml
                $sitemap_url = home_url( '/sitemap_index.xml' );
            } elseif ( $has_aioseo ) {
                // AIOSEO utilise sitemap.xml
                $sitemap_url = home_url( '/sitemap.xml' );
            } else {
                // Notre plugin généré (ou lien par défaut)
                $sitemap_url = home_url( '/sitemap-lingua.xml' );
            }
            ?>
            
            <a id="lingua-view-sitemap-link" href="<?php echo esc_url( $sitemap_url ); ?>" target="_blank" class="button">
                Voir le Sitemap actuel <span class="dashicons dashicons-external" style="vertical-align: text-bottom;"></span>
            </a>
            
            <span id="lingua-sitemap-message" style="color: #666; font-style: italic; font-size: 13px;"></span>
        </div>
    </div>

    <!-- SCRIPT JS POUR LE BOUTON GÉNÉRER -->
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#lingua-btn-generate-sitemap').on('click', function(e) {
            e.preventDefault();
            
            var btn = $(this);
            var originalText = btn.html();
            var messageSpan = $('#lingua-sitemap-message');
            var viewLink = $('#lingua-view-sitemap-link');

            // État de chargement
            btn.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spin" style="margin-top: 4px;"></span> Génération en cours...');
            messageSpan.text('');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'lingua_generate_sitemap',
                    nonce: '<?php echo wp_create_nonce( 'lingua_admin_nonce' ); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        btn.html('<span class="dashicons dashicons-yes-alt" style="color:green; margin-top: 4px;"></span> Généré !');
                        messageSpan.html('✅ ' + response.data);
                        
                        // Actualiser le lien "Voir le sitemap" pour pointer vers notre nouveau fichier
                        viewLink.attr('href', '<?php echo home_url('/sitemap-lingua.xml'); ?>');
                        
                        // Rétablir après 3 secondes
                        setTimeout(function() {
                            btn.prop('disabled', false).html(originalText);
                        }, 3000);
                    } else {
                        btn.prop('disabled', false).html(originalText);
                        alert('Erreur : ' + (response.data || 'Inconnue'));
                    }
                },
                error: function() {
                    btn.prop('disabled', false).html(originalText);
                    alert('Erreur serveur lors de la génération.');
                }
            });
        });
    });
    </script>

    <!-- CSS POUR LES SWITCHES -->
    <style>
        .switch { position: relative; display: inline-block; width: 40px; height: 22px; vertical-align: middle; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #2271b1; }
        input:checked + .slider:before { transform: translateX(18px); }
    </style
    
    <!-- SCRIPT JS POUR L'ACTION QUICK FIX (AJOUT MANQUANT) -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Écouteur pour le bouton "Quick Fix"
    $(document).on('click', '.lingua-quick-fix-btn', function(e) {
        e.preventDefault();
        
        var btn = $(this);
        var id = btn.data('id');
        var type = btn.data('type');
        var actionType = btn.data('action'); // 'translate_all' ou 'fix_title'
        
        // Récupération de la langue cible depuis le filtre du haut
        var targetLang = $('#lingua_seo_audit_lang').val();
        
        // Désactivation du bouton pendant le traitement
        btn.prop('disabled', true).text('⏳ En cours...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lingua_quick_fix_item',
                nonce: '<?php echo wp_create_nonce( 'lingua_admin_nonce' ); ?>',
                id: id,
                type: type,
                lang: targetLang,
                action_type: actionType,
                engine: 'default' // Ou récupérer depuis un sélecteur si vous en avez un
            },
            success: function(res) {
                if (res.success) {
                    btn.text('✅ ' + res.data.message);
                    // Optionnel : Rafraîchir la ligne du tableau après 1 seconde
                    setTimeout(function() {
                        $('#lingua-btn-refresh-seo-audit').trigger('click');
                    }, 1000);
                } else {
                    alert('Erreur : ' + (res.data || 'Inconnue'));
                    btn.prop('disabled', false).text('⚠️ Réessayer');
                }
            },
            error: function() {
                alert('Erreur de connexion au serveur.');
                btn.prop('disabled', false).text('⚠️ Erreur');
            }
        });
    });

});
</script>
    
</div>