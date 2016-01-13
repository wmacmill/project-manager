<?php
/**
 * Class CPM_BP_Frontend_URLs
 *
 * @author weDevs
 */
class CPM_BP_Frontend_URLs {

    private $slug;
    private $base_url;

    function __construct( $base_url, $slug_id ) {
        $this->base_url = $base_url;
        $this->slug     = $slug_id;

        add_filter( 'cpm_url_project_details', array( $this, 'project_details' ), 10, 2 );
        add_filter( 'cpm_url_tasklist_index', array( $this, 'tasklist_index' ), 10, 2 );
        add_filter( 'cpm_url_single_tasklislt', array( $this, 'tasklist_single' ), 10, 3 );
        add_filter( 'cpm_url_single_task', array( $this, 'task_single' ), 10, 4 );

        add_filter( 'cpm_url_message_index', array( $this, 'message_index' ), 10, 2 );
        add_filter( 'cpm_url_single_message', array( $this, 'single_message' ), 10, 3 );

        add_filter( 'cpm_url_milestone_index', array( $this, 'milestone_index' ), 10, 2 );
        add_filter( 'cpm_url_file_index', array( $this, 'file_index' ), 10, 2 );
        add_filter( 'cpm_url_all', array( $this, 'show_all_project' ) );

        add_filter( 'cpm_url_project_page', array( $this, 'show_all_project' ) );
        add_filter( 'cpm_url_active', array( $this, 'show_all_active' ) );
        add_filter( 'cpm_url_archive', array( $this, 'show_all_archive' ) );
        add_filter( 'cpm_project_duplicate', array( $this, 'show_all_active' ) );
        add_filter( 'cpm_url_settings_index', array( $this, 'show_all_settings' ), 10, 2 );

        add_filter( 'cpm_url_my_task', array( $this, 'show_url_my_task' ) );
        add_filter( 'cpm_url_outstanding_task', array( $this, 'show_url_outstanding_task' ) );
        add_filter( 'cpm_url_complete_task', array( $this, 'show_url_complete_task' ) );
        add_filter( 'cpm_url_user', array( $this, 'cpm_front_end_url_user' ), 10, 5 );
        add_filter( 'cpmtt_log_redirect', array( $this, 'redirect_log' ), 10, 5 );
        add_filter( 'cpm_url_kanboard', array( $this, 'cpm_url_kanboard' ), 10, 2 );
        add_filter( 'cpm_url_gantt_chart', array( $this, 'cpm_url_gantt_chart' ), 10, 2 );

        add_filter( 'cpm_project_list_url', array( $this, 'get_permalink' ) );

    }

    /**
     * Gantt chart url
     * @param $url
     * @param $project_id
     *
     * @since 1.1
     *
     * @return string
     */
    function cpm_url_gantt_chart( $url, $project_id ) {
        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab'        => 'chart',
            'action'     => 'index'
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @param $url
     * @param $project_id
     *
     * @return string
     */
    function cpm_url_kanboard( $url, $project_id ) {
        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab'        => 'kanboard',
            'action'     => 'index'
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @param $redirect
     * @param $project_id
     * @param $list_id
     * @param $task_id
     * @param $del_status
     *
     * @return string
     */
    function redirect_log( $redirect, $project_id, $list_id, $task_id, $del_status ) {

        $url = $this->task_single( $redirect, $project_id, $list_id, $task_id );

        $url = add_query_arg( array( 'delete' => $del_status ), $url );

        return $url;
    }

    /**
     * @param $url
     * @param $user
     * @param $link
     * @param $avatar
     * @param $size
     *
     * @return string
     */
    function cpm_front_end_url_user( $url, $user, $link, $avatar, $size ) {
        $page_id = cpm_get_option( 'my_task' );
        $name    = $user->display_name;

        if ( $avatar ) {
            $name = get_avatar( $user->ID, $size, $user->display_name );
        }

        $link = add_query_arg( array( 'user_id' => $user->ID ), get_permalink( $page_id ) );
        $url  = sprintf( '<a href="%s">%s</a>', $link, $name );

        return $url;
    }

    /**
     * @param $url
     *
     * @return string
     */
    function show_url_my_task( $url ) {
        $url = add_query_arg( array(
            'page' => 'cpm_task',
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @param $url
     *
     * @return string
     */
    function show_url_outstanding_task( $url ) {
        $url = add_query_arg( array(
            'page' => 'cpm_task',
            'tab'  => 'outstanding'
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @param $url
     *
     * @return string
     */
    function show_url_complete_task( $url ) {
        $url = add_query_arg( array(
            'page' => 'cpm_task',
            'tab'  => 'complete'
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @param $url
     * @param $project_id
     *
     * @return string
     */
    function show_all_settings( $url, $project_id ) {
        $url = add_query_arg( array(
            'page'       => 'cpm_projects',
            'tab'        => 'settings',
            'action'     => 'index',
            'project_id' => $project_id
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @param $url
     *
     * @return string
     */
    function show_all_active( $url ) {
        $url = add_query_arg( array(
            'status' => 'active',
            'page'   => 'cpm_projects'
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @param $url
     *
     * @return string
     */
    function show_all_archive( $url ) {
        $url = add_query_arg( array(
            'status' => 'archive',
            'page'   => 'cpm_projects'
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @param $url
     *
     * @return string
     */
    function show_all_project( $url ) {
        $url = add_query_arg( array(
            'status' => 'all',
            'page'   => 'cpm_projects'
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @return string
     */
    function get_permalink() {
        return $this->base_url . $this->slug;
    }

    /**
     * @param $url
     * @param $project_id
     *
     * @return string
     */
    function project_details( $url, $project_id ) {
        $url = add_query_arg( array(
            'project_id' => $project_id
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @param $url
     * @param $project_id
     *
     * @return string
     */
    function tasklist_index( $url, $project_id ) {

        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab'        => 'task',
            'action'     => 'index'
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @param $url
     * @param $project_id
     * @param $list_id
     *
     * @return string
     */
    function tasklist_single( $url, $project_id, $list_id ) {

        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab'        => 'task',
            'action'     => 'single',
            'list_id'    => $list_id
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @param $url
     * @param $project_id
     * @param $list_id
     * @param $task_id
     *
     * @return string
     */
    function task_single( $url, $project_id, $list_id, $task_id ) {
        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab'        => 'task',
            'action'     => 'todo',
            'list_id'    => $list_id,
            'task_id'    => $task_id
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @param $url
     * @param $project_id
     *
     * @return string
     */
    function message_index( $url, $project_id ) {
        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab'        => 'message',
            'action'     => 'index'
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @param $url
     * @param $project_id
     * @param $message_id
     *
     * @return string
     */
    function single_message( $url, $project_id, $message_id ) {
        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab'        => 'message',
            'action'     => 'single',
            'message_id' => $message_id
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @param $url
     * @param $project_id
     *
     * @return string
     */
    function milestone_index( $url, $project_id ) {
        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab'        => 'milestone',
            'action'     => 'index'
        ), $this->get_permalink() );

        return $url;
    }

    /**
     * @param $url
     * @param $project_id
     *
     * @return string
     */
    function file_index( $url, $project_id ) {
        $url = add_query_arg( array(
            'project_id' => $project_id,
            'tab'        => 'files',
            'action'     => 'index'
        ), $this->get_permalink() );

        return $url;
    }

}