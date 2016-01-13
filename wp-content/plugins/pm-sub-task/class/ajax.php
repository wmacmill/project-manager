<?php

class CPMST_Sub_Task_Ajax {

    private $parent_class;

    public static function getInstance( $parent_class ) {
        static $_instance = false;
        
        if ( !$_instance ) {
            $_instance = new CPMST_Sub_Task_Ajax( $parent_class );
        }
        return $_instance;
    }

    function __construct( $parent_class ) {
        $this->parent_class = $parent_class;
        
        add_action( 'wp_ajax_cpmst_task_add', array($this, 'insert_subtask') );
        add_action( 'wp_ajax_cpmst_task_complete', array($this, 'mark_task_complete') );
        add_action( 'wp_ajax_cpmst_task_open', array($this, 'mark_task_open') );
        add_action( 'wp_ajax_cpmst_task_update', array($this, 'update_task') );
        add_action( 'wp_ajax_cpmst_task_delete', array($this, 'delete_task') );
    }

    function delete_task() {
        check_ajax_referer( 'cpmst_subtask' );

        $task_id = (int) $_POST['task_id'];
        $list_id = (int) $_POST['list_id'];
        $project_id = (int) $_POST['project_id'];

        $task_obj = CPM_Task::getInstance();
        $task_obj->delete_task( $task_id, true );
        $complete = $task_obj->get_completeness( $list_id );
        
        do_action( 'cpm_delete_task_after', $task_id, $list_id, $project_id, $task_obj );
        echo json_encode( array(
            'success' => true,
            'list_url' => cpm_url_single_tasklist( $project_id, $list_id ),
            'progress' => cpm_task_completeness( $complete['total'], $complete['completed'] )
        ) );

        exit;
    }

    function update_task() {
        $posted = $_POST;

        $list_id = $posted['list_id'];
        $project_id = $posted['project_id'];
        $task_id = $posted['task_id'];
        $single = (int) $posted['single'];

        $task_obj = CPM_Task::getInstance();
        $task_id = $this->insert_subtask( $list_id, $task_id );
        $task = $task_obj->get_task( $task_id );

        if ( $task_id ) {
            $response = array(
                'success' => true,
                'content' => $this->parent_class->cpm_task_html( $task, $project_id, $list_id )
            );
        } else {
            $response = array('success' => false);
        }

        echo json_encode( $response );
        exit;
    }

    function mark_task_open() {
        check_ajax_referer( 'cpmst_subtask' );

        $posted = $_POST;
        $task_id = (int) $posted['task_id'];
        $list_id = $posted['list_id'];
        $project_id = $posted['project_id'];
        $single = (int) $posted['single'];

        $task_obj = CPM_Task::getInstance();
        $task_obj->mark_open( $task_id );
        $complete = $task_obj->get_completeness( $list_id );

        $task = $task_obj->get_task( $task_id );
        $response = array(
            'success' => true,
            'content' => $this->parent_class->cpm_task_html( $task, $project_id, $list_id ),
            'progress' => cpm_task_completeness( $complete['total'], $complete['completed'] )
        );

        echo json_encode( $response );
        exit;
    }

    function mark_task_complete() {
        check_ajax_referer( 'cpmst_subtask' );

        $posted = $_POST;

        $task_id = (int) $posted['task_id'];
        $list_id = $posted['list_id'];
        $project_id = $posted['project_id'];
        $single = (int) $posted['single'];

        $task_obj = CPM_Task::getInstance();
        $task_obj->mark_complete( $task_id );
        $complete = $task_obj->get_completeness( $list_id );

        $task = $task_obj->get_task( $task_id );
        $response = array(
            'success' => true,
            'content' => $this->parent_class->cpm_task_html( $task, $project_id, $list_id ),
            'progress' => cpm_task_completeness( $complete['total'], $complete['completed'] )
        );

        echo json_encode( $response );
        exit;
    }

    function insert_subtask() {
        $posted = $_POST;

        $list_id = $posted['list_id'];
        $project_id = $posted['project_id'];
        $task_id = isset( $posted['task_id'] ) ? $posted['task_id'] : 0;

        $task_obj = CPM_Task::getInstance();
        $task_id = $this->add_sub_task( $posted['list_id'], $task_id );
        $task = $task_obj->get_task( $task_id );

        $complete = $task_obj->get_completeness( $list_id );

        if ( $task_id ) {
            $response = array(
                'success' => true,
                'id' => $task_id,
                'content' => $this->parent_class->cpm_task_html( $task, $project_id, $list_id ),
                'progress' => cpm_task_completeness( $complete['total'], $complete['completed'] )
            );
        } else {
            $response = array('success' => false);
        }

        echo json_encode( $response );
        exit;
    }

    /**
     * Add a single task
     *
     * @param int $list_id task list id
     * @return int $task_id task id for update purpose
     */
    function add_sub_task( $list_id, $task_id = 0 ) {
        $postdata = $_POST;
        $files = isset( $postdata['cpm_attachment'] ) ? $postdata['cpm_attachment'] : array();
        $task_privacy = isset( $postdata['task_privacy'] ) ? $postdata['task_privacy'] : 'no';
        $is_update = $task_id ? true : false;

        $content = trim( $postdata['task_text'] );
        $assigned = $postdata['task_assign'];
        $due = empty( $postdata['task_due'] ) ? '' : cpm_date2mysql( $postdata['task_due'] );
        $start = empty( $postdata['task_start'] ) ? '' : cpm_date2mysql( $postdata['task_start'] );

        $data = array(
            'post_parent' => $list_id,
            'post_title' => trim( substr( $content, 0, 40 ) ), //first 40 character
            'post_content' => $content,
            'post_type' => 'sub_task',
            'post_status' => 'publish'
        );

        $data = apply_filters( 'cpm_task_params', $data );

        if ( $task_id ) {
            $data['ID'] = $task_id;
            $task_id = wp_update_post( $data );
        } else {
            $task_id = wp_insert_post( $data );
        }

        if ( $task_id ) {
            update_post_meta( $task_id, '_assigned', $assigned );
            update_post_meta( $task_id, '_due', $due );
            update_post_meta( $task_id, '_task_privacy', $task_privacy );
            
            if ( cpm_get_option( 'task_start_field' ) == 'on' ) {
                update_post_meta( $task_id, '_start', $start );
            } else {
                update_post_meta( $task_id, '_start', '' );
            }

            //initially mark as uncomplete
            if ( !$is_update ) {
                update_post_meta( $task_id, '_completed', 0 );
            }

            //if there is any file, update the object reference
            if ( count( $files ) > 0 ) {
                $comment_obj = CPM_Comment::getInstance();

                foreach ($files as $file_id) {
                    $comment_obj->associate_file( $file_id, $task_id );
                }
            }

            if ( $is_update ) {
                do_action( 'cpm_task_update', $list_id, $task_id, $data );
            } else {
                do_action( 'cpm_task_new', $list_id, $task_id, $data );
            }
        }

        return $task_id;
    }

}
