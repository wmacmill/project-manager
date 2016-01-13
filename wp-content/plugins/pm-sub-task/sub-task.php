<?php
/*
  Plugin Name: WP Project Manager - Sub Task
  Plugin URI: http://wedevs.com/plugin/wp-project-manager/
  Description: Time tracker add-on for WP Project Manager
  Version: 0.1
  Author: weDevs
  Author URI: http://wedevs.com
 */

/**
 * Copyright (c) 2014 weDevs. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

/**
 * CPM Time trakcer class
 *
 * @author WeDevs
 */
class CPMST_Sub_Task {

    public static function getInstance() {
        static $_instance = false;

        if ( !$_instance ) {
            $_instance = new CPMST_Sub_Task();
        }
        return $_instance;
    }

    function __construct() {

        $this->instantiate();
        
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', array($this, 'scripts') );
        } else {
            add_action( 'wp_enqueue_scripts', array($this, 'scripts') );
        }

        add_action( 'cpm_task_single_after', array($this, 'subtask'), 11, 4 );
    }

    function instantiate() {
        require_once dirname( __FILE__ ) . '/class/ajax.php';

        CPMST_Sub_Task_Ajax::getInstance( $this );
    }

    function scripts() {
        wp_enqueue_style( 'cpmst_subtask', plugins_url( 'asset/css/style.css', __FILE__ ), false, date( 'Ymd' ) );
        wp_enqueue_script( 'jquery' );

        wp_enqueue_script( 'cpmst_script', plugins_url( 'asset/js/script.js', __FILE__ ), array('jquery'), false, true );

        wp_localize_script( 'cpmst_script', 'CPMST_var', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            '_nonce' => wp_create_nonce( 'cpmst_subtask' ),
        ) );
    }

    function subtask( $parent_task, $project_id, $list_id, $single = false ) {

        if ( $single === false || $single == 0 ) {
            return;
        }

        $completed_status = $parent_task->completed;
        $parent_task_id = isset( $parent_task->ID ) ? $parent_task->ID : $parent_task->task_id;

        include dirname( __FILE__ ) . '/views/subtask.php';
    }

    /**
     * HTML form generator for new/update task form
     *
     * @param int $list_id
     * @param int $project_id
     * @param null|object $task
     */
    function cpm_task_new_form( $list_id, $project_id, $task = null, $single = false ) {

        $action = 'cpmst_task_add';
        $task_content = $task_due = '';
        $assigned_to = '-1';
        $submit_button = __( 'Add this to-do', 'cpm' );

        //for update form
        if ( !is_null( $task ) ) {
            $action = 'cpmst_task_update';
            $task_content = $task->post_content;
            $assigned_to = $task->assigned_to;
            $submit_button = __( 'Save Changes', 'cpm' );

            if ( $task->due_date != '' ) {
                $task_due = date( 'm/d/Y', strtotime( $task->due_date ) );
            }
        }

        if ( isset( $task->start_date ) && $task->start_date != '' ) {
            $task_start = date( 'm/d/Y', strtotime( $task->start_date ) );
        } else {
            $task_start = '';
        }
        ?>

        <form action="" method="post" class="cpmst-sub-task">
            <input type="hidden" name="list_id" value="<?php echo $list_id; ?>">
            <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
            <input type="hidden" name="action" value="<?php echo $action; ?>">
            <input type="hidden" name="single" value="<?php echo $single; ?>">
            <?php wp_nonce_field( $action ); ?>

            <?php if ( $task ) { ?>
                <input type="hidden" name="task_id" value="<?php echo $task->ID; ?>">
            <?php } ?>

            <div class="item content">
                <textarea name="task_text" class="todo_content" cols="40" placeholder="<?php esc_attr_e( 'Add a new to-do', 'cpm' ) ?>" rows="1"><?php echo esc_textarea( $task_content ); ?></textarea>
            </div>
            <div class="item date">
                <?php if ( cpm_get_option( 'task_start_field' ) == 'on' ) { ?>
                    <div class="cpm-task-start-field">
                        <label><?php _e( 'Start date', 'cpm' ); ?></label>
                        <input  type="text" autocomplete="off" class="datepicker" placeholder="<?php esc_attr_e( 'Start date', 'cpm' ); ?>" value="<?php echo esc_attr( $task_start ); ?>" name="task_start" />
                    </div>
                <?php } ?>
                <div class="cpm-task-due-field">
                    <label><?php _e( 'Due date', 'cpm' ); ?></label>
                    <input type="text" autocomplete="off" class="datepicker" placeholder="<?php esc_attr_e( 'Due date', 'cpm' ); ?>" value="<?php echo esc_attr( $task_due ); ?>" name="task_due" />
                </div>
            </div>
            <div class="item user">
                <?php cpm_task_assign_dropdown( $project_id, $assigned_to ); ?>
            </div>

            <?php do_action( 'cpm_task_new_form', $list_id, $project_id, $task ); ?>

            <div class="item submit">
                <span class="cpm-new-task-spinner"></span>
                <input type="submit" class="button-primary cpmi-add-subtask-submit" name="submit_todo" value="<?php echo esc_attr( $submit_button ); ?>">
                <a class="button cpm-cancle-new-sub-task todo-cancel" href="#"><?php _e( 'Cancel', 'cpm' ); ?></a>
            </div>
        </form>
        <?php
    }

    /**
     * This file contains all the functions that are responsible for
     * generating repeated HTML markups.
     *
     * @since 0.1
     * @package CPM
     */

    /**
     * HTML generator for single task
     *
     * @param object $task
     * @param int $project_id
     * @param int $list_id
     * @return string
     */
    function cpm_task_html( $task, $project_id, $list_id, $single = false, $completed_status = '' ) {
        $wrap_class = ( $task->completed == '1' ) ? 'cpm-task-complete' : 'cpm-task-uncomplete';
        $status_class = ( $task->completed == '1' ) ? 'cpmst-complete' : 'cpmst-uncomplete';
        $start_date = isset( $task->start_date ) ? $task->start_date : '';
        ob_start();
        ?>
        <div class="cpm-todo-wrap <?php echo $wrap_class; ?>">
            <?php
            if ( cpm_user_can_delete_edit( $project_id, $task ) ) {
                ?>
                <span class="cpmst-todo-action">
                    <a href="#" class="cpmst-todo-delete cpm-icon-delete" <?php cpm_data_attr( array('single' => $single, 'list_id' => $list_id, 'project_id' => $project_id, 'task_id' => $task->ID, 'confirm' => __( 'Are you sure to delete this to-do?', 'cpm' )) ); ?>>
                        <span><?php _e( 'Delete', 'cpm' ); ?></span>
                    </a>
                    <?php if ( $task->completed == '0' && $completed_status != 1 ) { ?>
                        <a href="#" class="cpmst-todo-edit cpm-icon-edit"><span><?php _e( 'Edit', 'cpm' ); ?></span></a>
                    <?php } ?>

                </span>
            <?php } ?>
            <?php
            if ( $completed_status == 1 ) {
                $check_status = 'disabled';
            } else {
                $check_status = '';
            }
            ?>
            <span class="cpm-spinner"></span>
            <input <?php echo $check_status; ?>  class="<?php echo $status_class; ?>" type="checkbox" <?php cpm_data_attr( array('single' => $single, 'list' => $list_id, 'project' => $project_id) ); ?> value="<?php echo $task->ID; ?>" name="" <?php checked( $task->completed, '1' ); ?>>

            <span class="move"></span>
            <span class="cpm-todo-content">
                <?php if ( $single ) { ?>
                    <span class="cpm-todo-text"><?php echo $task->post_content; ?></span>
                <?php } else { ?>

                    <span class="cpm-todo-text"><?php echo $task->post_content; ?></span>

                <?php } ?>


                <?php
                //if the task is completed, show completed by
                if ( $task->completed == '1' && $task->completed_by ) {
                    $user = get_user_by( 'id', $task->completed_by );
                    $completion_time = cpm_get_date( $task->completed_on, true );
                    ?>
                    <span class="cpm-completed-by">
                        <?php printf( __( '(Completed by %s on %s)', 'cpm' ), $user->display_name, $completion_time ) ?>
                    </span>
                <?php } ?>

                <?php
                if ( $task->completed != '1' ) {
                    if ( $task->assigned_to != '-1' ) {
                        $user = get_user_by( 'id', $task->assigned_to );
                        ?>
                        <span class="cpm-assigned-user"><?php echo $user->display_name; ?></span>
                    <?php } ?>

                    <?php if ( $task->due_date != '' || $start_date != '' ) { ?>
                        <span class="cpm-due-date">
                            <?php
                            if ( ( cpm_get_option( 'task_start_field' ) == 'on' ) && $start_date != '' ) {
                                echo cpm_get_date( $start_date );
                            }
                            if ( $start_date != '' & $task->due_date != '' ) {
                                echo ' - ';
                            }
                            if ( $task->due_date != '' ) {
                                echo cpm_get_date( $task->due_date );
                            }
                            ?>
                        </span>
                        <?php
                    }
                }
                ?>
            </span>



            <?php if ( $task->completed == '0' && $completed_status != 1 ) { ?>
                <div class="cpmst-task-edit-form" style="display: none;">
                    <?php echo $this->cpm_task_new_form( $list_id, $project_id, $task ); ?>
                </div>
            <?php } ?>
            <?php
            if ( $completed_status != 1 && $task->completed != 1 ) {
                do_action( 'cpm_task_single_after', $task, $project_id, $list_id, $single, $task->completed );
            }
            ?>
        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * Get all tasks based on a task list
     *
     * @param int $list_id
     * @return object object array of the result set
     */
    function get_tasks( $list_id, $privacy = null ) {

        $args = array('post_parent' => $list_id, 'post_type' => 'sub_task', 'post_status' => 'publish', 'order' => 'ASC', 'orderby' => 'menu_order');



        $args = apply_filters( 'cpm_get_sub_task', $args );

        $tasks = get_children( $args );

        foreach ($tasks as $key => $task) {
            $this->set_task_meta( $task );
        }

        return $tasks;
    }

    /**
     * Set all the meta values to a single task
     *
     * @param object $task
     */
    function set_task_meta( &$task ) {
        $task->completed = get_post_meta( $task->ID, '_completed', true );
        $task->completed_by = get_post_meta( $task->ID, '_completed_by', true );
        $task->completed_on = get_post_meta( $task->ID, '_completed_on', true );
        $task->assigned_to = get_post_meta( $task->ID, '_assigned', true );
        $task->due_date = get_post_meta( $task->ID, '_due', true );
        $task->start_date = get_post_meta( $task->ID, '_start', true );
    }

    /**
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    static function activate() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        if ( !self::is_plugin_installed() ) {
            deactivate_plugins( __FILE__ );

            exit( '"WP Project Manager PRO" plugin is not installed. Install the plugin first.' );
        }
    }

    function is_plugin_installed() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        if ( is_plugin_active( 'wedevs-project-manager-pro/cpm.php' ) ) {
            return true;
        }

        if ( is_plugin_active( 'wedevs-project-manager/cpm.php' ) ) {
            return true;
        }

        return false;
    }

}

add_action( 'plugins_loaded', 'cpmst_loaded' );

function cpmst_loaded() {
    $instance = CPMST_Sub_Task::getInstance();
}

register_activation_hook( __FILE__, array('CPMST_Sub_Task', 'activate') );