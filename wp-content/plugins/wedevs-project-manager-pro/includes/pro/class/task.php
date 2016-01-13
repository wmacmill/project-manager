<?php
/**
 * Task list manager class
 *
 * @author Tareq Hasan
 */
class CPM_Pro_Task {
	private static $_instance;

	public static function getInstance() {
        if ( !self::$_instance ) {
            self::$_instance = new CPM_Pro_Task();
        }

        return self::$_instance;
    }

	function __construct() {

		add_filter( 'cpm_task_complete_response', array($this, 'mytask_count'), 10, 4 );
        add_filter( 'cpm_task_open_response', array($this, 'mytask_count'), 10, 4 );
        add_action( 'cpm_after_new_task', array($this, 'mytask_flush_cache') );
        add_action( 'cpm_after_update_task', array($this, 'mytask_flush_cache') );
        add_action( 'cpm_delete_task_after', array($this, 'mytask_flush_cache') );
	}

	function task_count() {
		
        global $wpdb;

        $logged_in_user_id = get_current_user_id();

        $task = $wpdb->get_results(
            "SELECT du.meta_value as due_date, n.meta_value as complete_status
            FROM `$wpdb->posts` AS t
            LEFT JOIN $wpdb->posts AS tl ON tl.ID = t.post_parent
            LEFT JOIN $wpdb->posts AS p ON p.ID = tl.post_parent
            LEFT JOIN $wpdb->postmeta AS m ON m.post_id = t.ID
            LEFT JOIN $wpdb->postmeta AS n ON n.post_id = t.ID
            LEFT JOIN $wpdb->postmeta AS du ON du.post_id = t.ID
            WHERE t.post_type = 'task' AND t.post_status = 'publish'
            AND m.meta_key = '_assigned' AND m.meta_value = $logged_in_user_id
            AND n.meta_key = '_completed'
            AND du.meta_key = '_due'
            AND p.post_title is not null"

        );

        $counts = array( 'current_task' => 0, 'outstanding' => 0, 'complete' => 0 );
        
        foreach( $task as $key => $obj ) {

            if( ( empty( $obj->due_date ) || date( 'Y-m-d', strtotime( $obj->due_date ) ) >= date( 'Y-m-d', time() ) )  && $obj->complete_status != 1 ) {
                $counts['current_task'] += 1;
            }

            if( ! empty( $obj->due_date ) && date( 'Y-m-d', strtotime( $obj->due_date ) ) < date( 'Y-m-d', time() )  && $obj->complete_status != 1 ) {
                $counts['outstanding'] += 1;
            }

            if( $obj->complete_status == 1 ) {
                $counts['complete'] += 1;
            }
        }

        return $counts;
    }


    function current_user_task( $user_id ) {
        global $wpdb;

        if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'outstanding' ) {

            $query1 = "AND n.meta_key = '_completed' AND n.meta_value = '0'";
            $query2 = "AND due.meta_value != '' AND STR_TO_DATE( due.meta_value, '%Y-%m-%d') < STR_TO_DATE( NOW(), '%Y-%m-%d')";
        } else if ( isset( $_GET['tab'] ) && $_GET['tab'] == 'complete' ) {

            $query1 = "AND n.meta_key = '_completed' AND n.meta_value = '1'";
            $query2 = '';
        } else {
            $query1 = "AND n.meta_key = '_completed' AND n.meta_value = '0'";
            $query2 = "AND ( due.meta_value = '' OR STR_TO_DATE( due.meta_value, '%Y-%m-%d') >= STR_TO_DATE( NOW(), '%Y-%m-%d') ) ";
        }

        $que = "SELECT t.post_title as task, t.comment_count as comment_count, t.ID as task_id, tl.post_title as list, tl.ID as task_list_id,
                    p.post_title as project_title, p.ID as project_id, m.meta_value as assigned_to, n.meta_value as completed, due.meta_value as due_date,
                    strday.meta_value as start_date
                FROM `$wpdb->posts` AS t
                LEFT JOIN $wpdb->posts AS tl ON t.post_parent = tl.ID
                LEFT JOIN $wpdb->posts AS p ON tl.post_parent = p.ID
                LEFT JOIN $wpdb->postmeta AS m ON m.post_id = t.ID
                LEFT JOIN $wpdb->postmeta AS n ON n.post_id = t.ID
                LEFT JOIN $wpdb->postmeta AS due ON due.post_id = t.ID
                LEFT JOIN $wpdb->postmeta AS strday ON strday.post_id = t.ID
                WHERE t.post_type = 'task' AND t.post_status = 'publish'
                    AND m.meta_key = '_assigned' AND m.meta_value = $user_id
                    $query1
                    AND strday.meta_key = '_start'
                    AND due.meta_key = '_due' $query2
                    AND p.post_title is not null
                ORDER BY project_id DESC";

        $tasks = $wpdb->get_results( $que );
        $project = array();
        foreach ($tasks as $task) {
            $projects[$task->project_id]['tasks'][] = $task;
            $projects[$task->project_id]['title'] = $task->project_title;
        }
        $projects = isset( $projects ) ? $projects : '';

        return $projects;
    }

    function get_mytasks( $user_id ) {

        $cache_key = 'cpm_mytask_' . $user_id;
        $project = wp_cache_get( $cache_key );

        if ( $project === false ) {
            $project = $this->current_user_task( $user_id );
            wp_cache_set( $cache_key, $project );
        }

        return $project;
    }

	/**
     * Counts my task
     *
     * @param type $response
     * @param type $task_id
     * @param type $list_id
     * @param type $project_id
     * 
     * @return type
     */
    function mytask_count( $response = null, $task_id = null, $list_id = null, $project_id = null ) {
        $user_id = get_current_user_id();
        $cache_key = 'cpm_mytask_count_' . $user_id;
        $task = wp_cache_get( $cache_key );

        if ( $task === false ) {
            $task = $this->task_count();
            wp_cache_set( $cache_key, $task );
        }

        $response['current_task'] = $task['current_task'];
        $response['outstanding'] = $task['outstanding'];
        $response['complete'] = $task['complete'];

        return $response;
    }

    function mytask_flush_cache( $task_id ) {
        $user_id = get_current_user_id();
        wp_cache_delete( 'cpm_mytask_' .$task_id.$user_id );
        wp_cache_delete( 'cpm_mytask_count_' .$task_id.$user_id );
    }
}