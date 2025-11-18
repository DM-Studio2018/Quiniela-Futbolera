<?php
/**
 * Plugin Name: Quiniela FIFA 2026
 * Plugin URI: https://tudominio.com/quiniela-fifa
 * Description: Plugin completo para gestionar quinielas de fútbol multi-torneo con sistema de puntos, premios y clasificación
 * Version: 1.0.0
 * Author: Tu Nombre
 * Author URI: https://tudominio.com
 * Text Domain: quiniela-fifa
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('QUINIELA_VERSION', '1.0.0');
define('QUINIELA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QUINIELA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('QUINIELA_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin
 */
class Quiniela_FIFA_2026 {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Cargar dependencias del plugin
     */
    private function load_dependencies() {
        // Core
        require_once QUINIELA_PLUGIN_DIR . 'includes/class-quiniela-db.php';
        require_once QUINIELA_PLUGIN_DIR . 'includes/class-quiniela-tournament.php';
        require_once QUINIELA_PLUGIN_DIR . 'includes/class-quiniela-match.php';
        require_once QUINIELA_PLUGIN_DIR . 'includes/class-quiniela-prediction.php';
        require_once QUINIELA_PLUGIN_DIR . 'includes/class-quiniela-scoring.php';
        require_once QUINIELA_PLUGIN_DIR . 'includes/class-quiniela-prizes.php';
        
        // Admin
        require_once QUINIELA_PLUGIN_DIR . 'admin/class-quiniela-admin.php';
        require_once QUINIELA_PLUGIN_DIR . 'admin/class-quiniela-csv-importer.php';
        
        // Frontend
        require_once QUINIELA_PLUGIN_DIR . 'public/class-quiniela-frontend.php';
        require_once QUINIELA_PLUGIN_DIR . 'public/class-quiniela-shortcodes.php';
    }
    
    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Activación del plugin
     */
    public function activate() {
        global $wpdb;
        
        $db = new Quiniela_DB();
        $db->create_tables();
        
        // Crear página de pronósticos
        $this->create_default_pages();
        
        // Configuración por defecto
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Crear páginas por defecto
     */
    private function create_default_pages() {
        $pages = array(
            'mis-pronosticos' => array(
                'title' => 'Mis Pronósticos',
                'content' => '[quiniela_predictions]'
            ),
            'tabla-posiciones' => array(
                'title' => 'Tabla de Posiciones',
                'content' => '[quiniela_standings]'
            ),
            'premios' => array(
                'title' => 'Premios',
                'content' => '[quiniela_prizes]'
            ),
            'reglas' => array(
                'title' => 'Reglas del Torneo',
                'content' => '[quiniela_rules]'
            )
        );
        
        foreach ($pages as $slug => $page) {
            if (!get_page_by_path($slug)) {
                wp_insert_post(array(
                    'post_title' => $page['title'],
                    'post_name' => $slug,
                    'post_content' => $page['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'comment_status' => 'closed',
                    'ping_status' => 'closed'
                ));
            }
        }
    }
    
    /**
     * Configuración por defecto
     */
    private function set_default_options() {
        $defaults = array(
            // Sistema de puntos
            'quiniela_points_winner' => 5,
            'quiniela_points_one_score' => 5,
            'quiniela_points_both_scores' => 10,
            'quiniela_points_champion' => 30,
            'quiniela_points_runner_up' => 25,
            'quiniela_points_third' => 20,
            'quiniela_points_fourth' => 15,
            'quiniela_points_top_scorer' => 20,
            
            // Premios
            'quiniela_entry_fee' => 50000,
            'quiniela_pot_percentage' => 30,
            'quiniela_pot_max' => 10000000,
            'quiniela_prize_champion_pct' => 70,
            'quiniela_prize_runner_up_pct' => 30,
            
            // Configuración general
            'quiniela_edit_deadline_minutes' => 10,
            'quiniela_show_user_column' => true,
            'quiniela_show_name_column' => true
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    /**
     * Cargar dominio de texto
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'quiniela-fifa',
            false,
            dirname(QUINIELA_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Inicializar componentes
     */
    public function init() {
        if (is_admin()) {
            new Quiniela_Admin();
        }
        
        new Quiniela_Frontend();
        new Quiniela_Shortcodes();
    }
    
    /**
     * Cargar assets públicos
     */
    public function enqueue_public_assets() {
        wp_enqueue_style(
            'quiniela-public',
            QUINIELA_PLUGIN_URL . 'assets/css/public.css',
            array(),
            QUINIELA_VERSION
        );
        
        wp_enqueue_script(
            'quiniela-public',
            QUINIELA_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            QUINIELA_VERSION,
            true
        );
        
        wp_localize_script('quiniela-public', 'quinielaAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('quiniela_nonce'),
            'strings' => array(
                'saving' => __('Guardando...', 'quiniela-fifa'),
                'saved' => __('Pronósticos guardados correctamente', 'quiniela-fifa'),
                'error' => __('Error al guardar', 'quiniela-fifa'),
                'confirm' => __('¿Confirmar pronósticos?', 'quiniela-fifa')
            )
        ));
    }
    
    /**
     * Cargar assets del admin
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'quiniela') === false) {
            return;
        }
        
        wp_enqueue_style(
            'quiniela-admin',
            QUINIELA_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            QUINIELA_VERSION
        );
        
        wp_enqueue_script(
            'quiniela-admin',
            QUINIELA_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker'),
            QUINIELA_VERSION,
            true
        );
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }
}

// Inicializar el plugin
function quiniela_fifa_init() {
    return Quiniela_FIFA_2026::get_instance();
}

add_action('plugins_loaded', 'quiniela_fifa_init');
