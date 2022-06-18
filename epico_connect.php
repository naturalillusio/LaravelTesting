<?php 

/**
 * Plugin Name: SoftOne Intergration
 * Plugin URI: https://www.epico.gr
 * Description: EPICO - intergration with SoftOne ERP 
 * Version: 1.0.1
 * Author: Nikos Gioumatzidis | EPICO consultant
 * Author URI: https://www.epico.gr
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( 'Invalid request.' );
}

register_activation_hook(__FILE__,  'flush_rules' );
register_deactivation_hook( __FILE__, 'deactivate_epico' );
register_uninstall_hook( __FILE__, 'uninstall_epico' );

function flush_rules()
{   
    global $wpdb;
    $table_credentials = $wpdb->prefix . "epico_credentials";
    $table_new_product = $wpdb->prefix . "epico_new_product";
    $table_logfile = $wpdb->prefix . "epico_logfile";
    $table_softone_product_connector = $wpdb->prefix . "epico_softone_product_connector";
    $epico_credentials_db_version = '1.0.0';
    $charset_collate = $wpdb->get_charset_collate();
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_credentials}'" ) != $table_credentials ) {

        $sql1 = "CREATE TABLE ".$table_credentials."(
                id INT NOT NULL AUTO_INCREMENT,
                `MetaKey` VARCHAR(250) NOT NULL,
                `MetaValue` VARCHAR(250) NOT NULL,
                PRIMARY KEY  (id)
        ) ".$charset_collate.";";               
        dbDelta( $sql1 );      
    }
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_softone_product_connector}'" ) != $table_softone_product_connector ) {

        $sql2 = "CREATE TABLE ".$table_softone_product_connector."(
                id INT NOT NULL AUTO_INCREMENT,
                `softone_id` VARCHAR(250) NOT NULL,
                `WC_id` VARCHAR(250) NOT NULL,
                `Sku` VARCHAR(250) NOT NULL,
                PRIMARY KEY  (id)
        ) ".$charset_collate.";";               
        dbDelta( $sql2 );      
    }
     if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_logfile}'" ) != $table_logfile ) {

        $sql3 = "CREATE TABLE ".$table_logfile."(
                id INT NOT NULL AUTO_INCREMENT,
                `error_type` VARCHAR(250) NOT NULL,
                `error_message` VARCHAR(250) NOT NULL,
                PRIMARY KEY  (id)
        ) ".$charset_collate.";";               
        dbDelta( $sql3 );      
    }
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_new_product}'" ) != $table_new_product ) {
        $sql4 = "CREATE TABLE ".$table_new_product."(
                id INT NOT NULL AUTO_INCREMENT,
                `MTRL` VARCHAR(250) NOT NULL,
                `CODE` VARCHAR(250) NOT NULL,
                `NAME` VARCHAR(250) NOT NULL,
                `MTRUNIT` VARCHAR(250) NOT NULL,                
                `UNAME` VARCHAR(250) NOT NULL,
                `UPDDATE` VARCHAR(250) NOT NULL,
                `MTRGROUP` VARCHAR(250),
                `MTRGROUP_NAME` VARCHAR(250),
                `MTRCATEGORY` VARCHAR(250),
                `MTRCATEGORY_NAME` VARCHAR(250),
                `MTRMARK` VARCHAR(250),
                `MTRMARK_NAME` VARCHAR(250),
                `MTRSEASON` VARCHAR(250),
                `MTRSEASON_NAME` VARCHAR(250),
                PRIMARY KEY  (id)
        ) ".$charset_collate.";";  
        dbDelta( $sql4 );   

    }

    flush_rewrite_rules();   
}

function deactivate_epico()
{
  
}

function uninstall_epico() {
    global $wpdb;
    $table_credentials = $wpdb->prefix . "epico_credentials";
    $table_new_product = $wpdb->prefix . "epico_new_product";
    $table_logfile = $wpdb->prefix . "epico_logfile";
    $table_softone_product_connector = $wpdb->prefix . "epico_softone_product_connector";
    $wpdb->query("DROP TABLE IF EXISTS " .$table_credentials);    
    $wpdb->query("DROP TABLE IF EXISTS " .$table_logfile);
    $wpdb->query("DROP TABLE IF EXISTS " .$table_new_product);
    $wpdb->query("DROP TABLE IF EXISTS " .$table_softone_product_connector);    
}

add_action(

    'plugins_loaded', 

    array(Registration::get_instance(), 'setup')

);
class Registration {

    protected static $instance = NULL;

    public function __construct() {

    }

    public static function get_instance() {

        NULL === self::$instance and self::$instance = new self;

        return self::$instance;

    }


    public function setup() {

        add_action('init', array($this, 'rewrite_rules'));

        add_filter('query_vars', array($this, 'query_vars'), 10, 1);

        add_action('parse_request', array($this, 'parse_request'), 10, 1);

        add_action('admin_menu', array($this, 'epico_admin_menu'), 10, 1);

        add_action( 'admin_enqueue_scripts', array($this, 'adminMenu_enqueue') );

        add_action( 'wp_ajax_softone_settings',  array($this, 'softone_settings') );

        add_action( 'woocommerce_product_options_general_product_data', array($this, 'woocommerce_product_custom_fields') );

        add_action( 'woocommerce_admin_process_product_object',  array($this, 'woocommerce_product_custom_fields_save') );
    }


    public function woocommerce_product_custom_fields() {
        global $product_object;

        echo '<div class="product_custom_field ">';

        // Custom Product Text Field
        woocommerce_wp_text_input( array( 
            'id'          => 'softeone_id',
            'label'       => __('ERP ID', 'woocommerce'),
            'placeholder' => '',
            'desc_tip'    => 'true' // <== Not needed as you don't use a description
        ) );

        echo '</div>';
    }

    public function woocommerce_product_custom_fields_save( $product ) {
        if ( isset($_POST['softeone_id']) ) {
            $product->update_meta_data( 'softeone_id', sanitize_text_field( $_POST['softeone_id'] ) );
        }
    }

    public function adminMenu_enqueue($hook) {
        if ("toplevel_page_epico_settings" != $hook) {
           return;
        }  
         wp_enqueue_style( 'Bootstrap3',  plugins_url( '/assets/css/bootstrap.min.css', __FILE__ ),$deps = array(), "ver3.3.7" );
        wp_enqueue_style( 'my_custom_css1',  plugins_url( '/assets/css/admin_settings.css', __FILE__ ),$deps = array(), "ver1.0.0" ); 
        wp_enqueue_script( 'my_custom_script1',  plugins_url( '/assets/js/js_admin.js', __FILE__ ),array('jquery') );
        wp_localize_script( 'ajax-script', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );

    }

    public function softone_settings()
    {
        if (isset($_POST['action']) & $_POST['action'] == 'softone_settings' ) {
            include plugin_dir_path(__FILE__) . '/classes/admin_controller.php';
            global $wpdb;
            $posthadle = new EpicoAdminAjaxController($_POST,$wpdb);   
            switch ($_POST['type']) {
                        case 'save-settings':
                            echo $posthadle->StoreSettingsData($_POST);
                            break;
                        case 'product-newproduct':                       
                           
                            break;
                        case 'product-newprices':   
                            $sku = $_POST['sku'];
                            $product_id = wc_get_product_id_by_sku( $sku );
                            $product = wc_get_product( $product_id );
                            echo $posthadle->GetAllPrices($product);
                            break;
                        default:
                            echo 100;
                            break;
                    }
        }
         
        wp_die();
        
    }
    public function epico_admin_menu(){      
       add_menu_page('Epico intergration with softOne', 'Epico', 'manage_options', "epico_settings", array($this, 'epico_admin_settings') , plugins_url('/epico_connect/assets/img/epico_ico.png',__DIR__));
 
    }

    public function epico_admin_settings()
    {

        global $wpdb;
        include plugin_dir_path(__FILE__) . '/init/admin_settings.php';
    }

    public static function rewrite_rules(){
           

        add_rewrite_rule('startschedule/?$', 'index.php?startschedule=true', 'top');

    }

    public function query_vars($vars){

        $vars[] = 'startschedule';

        return $vars;

    }



    public function parse_request($wp){

        if ( array_key_exists( 'startschedule', $wp->query_vars ) ){
            global $wpdb;
            include plugin_dir_path(__FILE__) . '/classes/trust.php';
            include plugin_dir_path(__FILE__) . '/classes/softone_service.php';
            include plugin_dir_path(__FILE__) . '/classes/startschedule.php';
           

            exit();

        }

    }
   
}