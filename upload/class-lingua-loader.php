<?php
/**
 * Enregistre tous les hooks et actions du plugin
 *
 * @package    LinguaCommerce_AI
 * @subpackage LinguaCommerce_AI/includes
 */

// Si ce fichier est appelé directement, on abandonne.
if ( ! defined( 'WPINC' ) ) {
    die;
}

class LinguaCommerce_AI_Loader {
    /**
     * Tableau des actions enregistrées
     */
    protected $actions;
    
    /**
     * Tableau des filtres enregistrés
     */
    protected $filters;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }
    
    /**
     * Ajoute une nouvelle action
     */
    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
    }
    
    /**
     * Ajoute un nouveau filtre
     */
    public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
    }
    
    /**
     * Ajoute un hook au tableau approprié
     */
    private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        
        return $hooks;
    }
    
    /**
     * Enregistre les hooks auprès de WordPress
     */
    public function run() {
        foreach ( $this->filters as $hook ) {
            add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
        }
        
        foreach ( $this->actions as $hook ) {
            add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
        }
    }
}