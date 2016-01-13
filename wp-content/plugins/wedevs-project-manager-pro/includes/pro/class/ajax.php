<?php

/**
 * Description of ajax
 *
 * @author tareq
 */
class CPM_Pro_Ajax extends CPM_Ajax {
	private static $_instance;

	public static function getInstance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
    	add_action( 'wp_ajax_cpm_get_events', array( $this, 'get_events' ) );
        add_action( 'wp_ajax_cpm_project_duplicate', array( $this, 'project_duplicate' ) );
    }

    function get_events() {

        $events = CPM_Pro_Calendar::getInstance()->get_events();

        if ( $events ) {
            echo json_encode( $events );
        } else {
            echo json_encode( array(
                'success' => false
            ) );
        }
        exit;
    }

    function project_duplicate() {

        if ( ! wp_verify_nonce( $_POST['_nonce'], 'cpm_nonce' ) ) {
            wp_send_json_error( __( 'Are you cheating?', 'cpm' ) );
        }

        if ( isset( $_POST['project_id'] ) ) {
            $project_id = $_POST['project_id'];
        } else {
            wp_send_json_error( __( 'Project ID required', 'cpm' ) );
        }

        CPM_Pro_Duplicate::getInstance()->create_duplicate( $project_id );

        wp_send_json_success( array(
            'url' => $_POST['url']
        ) );
    }

}


