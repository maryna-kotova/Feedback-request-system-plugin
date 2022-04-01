<?php
/**
 * Plugin Name: Feedback Request System
 * Plugin URI: https://github.com/maryna-kotova/feedback-request-system-plugin
 * Description: It is a feedback request system. The output of the form for sending a request for feedback is carried out through a shortcode. All data in the application is recorded in the database and displayed in the admin panel
 * Version: 1.0
 * Author: Maryna Kotova
 *
 * Text Domain: feedback_request_system
 *
 * Requires PHP: 7.4
 */

add_action( 'wp_enqueue_scripts', 'frs_style_scripts' );
function frs_style_scripts() {
    wp_register_style( 'frs_style', plugins_url( 'style.css', __FILE__ ) );
    wp_enqueue_style( 'frs_style' );
}

register_activation_hook( __FILE__, function () {
    global $wpdb;

    $sql = "CREATE TABLE {$wpdb->prefix}frs (
      id INT(6) UNSIGNED AUTO_INCREMENT,
      user_name VARCHAR(250) NOT NULL,
      user_email VARCHAR(100) NOT NULL,
      user_phone VARCHAR(13) NOT NULL, 
      created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id)
   );";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    dbDelta( $sql );
} );

add_action( 'plugins_loaded', function () {

    global $wpdb;

    if ( $_SERVER["REQUEST_METHOD"] == "POST" ) {
        if ( isset( $_POST["frs_username"] ) ) {
            $name = trim( strip_tags( $_POST["frs_username"] ) );
        }
        if ( isset( $_POST["frs_usernumber"] ) ) {
            $number = trim( strip_tags( $_POST["frs_usernumber"] ) );
        }
        if ( isset( $_POST["frs_useremail"] ) ) {
            $email = trim( strip_tags( $_POST["frs_useremail"] ) );
        }

        $wpdb->insert( "{$wpdb->prefix}frs", array(
            'user_name'  => $name,
            'user_email' => $email,
            'user_phone' => $number,
            'created'    => current_time( 'mysql' ),
        ) );
    }

}, 100, 0 );

add_action( 'admin_menu', 'feedback_request_system_top_menu_page', 25 );

function feedback_request_system_top_menu_page() {
    $hook = add_menu_page(
        'Feedback Request System',
        'Feedback request system',
        'manage_options',
        'feedback_request_system',
        'feedback_request_system_table_page',
        'dashicons-image-rotate',
        20
    );
    add_action( "load-$hook", 'feedback_request_system_table_page_load' );
}

add_shortcode( 'frs-contact-form', 'frs_contact_form' );

function frs_contact_form( $attr ) {
    global $wpdb;

    $params = shortcode_atts( [
        'class' => 'frs-contact-form',
    ], $attr );

    $frs_contact_form = '
        <form class="' . $params['class'] . '" method="post">
        	<div class="' . $params['class'] . '-container"> 
        		<div>
        			<label>Name <span>*</span></label>
        			<input type="text" name="frs_username" required>
        		</div>
        		<div>
        			<label>Phone (with code) <span>*</span></label>
        			<input type="tel" name="frs_usernumber" required>
        		</div>
        		<div>
        			<label>Email</label>
        			<input type="email" name="frs_useremail" required>
        		</div>
        		<input class="bot-send-mail" type="submit" value="Send request">
        	</div>
        </form>';
    $result           = $frs_contact_form;

    return $result;
}

function feedback_request_system_table_page_load() {
    require_once __DIR__ . '/classes/FrsListTable.php';

    $GLOBALS['FrsListTable'] = new FrsListTable();
}

function feedback_request_system_table_page() {
    ?>
    <div class="wrap">
        <h2><?php echo get_admin_page_title() ?></h2>
        <h4>Copy this shortcode and paste it on your website page:</h4>
        <p style="width: 300px;padding:20px;background: #fff">
            [frs-contact-form class="frs-contact-form"]
        </p>
        <small>*You can replace the class value, giving it your own styles</small>
        <?php
        echo '<form action="" method="POST">';
        $GLOBALS['FrsListTable']->display();
        echo '</form>';
        ?>
    </div>
    <?php
}

register_uninstall_hook( __FILE__, 'frs_uninstall' );

function frs_uninstall() {
    global $wpdb;

    $wpdb->query( sprintf(
        "DROP TABLE IF EXISTS %s",
        $wpdb->prefix . 'frs'
    ) );
}
