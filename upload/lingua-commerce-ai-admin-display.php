<?php
/**
 * Fichier d'affichage pour la page principale de l'administration
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/admin/partials
 */

// Si ce fichier est appelé directement, on abandonne.
if ( ! defined( 'WPINC' ) ) {
    die;
?>

<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <p>Bienvenue sur LinguaCommerce AI !</p>
    <p>Le plugin est activé et prêt à être configuré.</p>
    <p>Les prochaines étapes consisteront à construire les différentes sections de l'administration.</p>
</div>

<?php } ?>