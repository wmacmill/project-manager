<?php
/**
 * This file contains all the helper functions for Project Manager.
 *
 * @since 0.1
 * @package CPM
 */
/**
 * Filter all the tasks as pending and completed
 *
 * This function gets all the tasks for a tasklist and returns pending and
 * completed tasks as an array
 *
 * @uses `cpm_tasks_filter_done`
 * @uses `cpm_tasks_filter_pending`
 *
 * @since 0.1
 * @param array $tasks
 * @return array
 */
function cpm_tasks_filter( $tasks ) {
    $response = array(
        'completed' => array(),
        'pending' => array()
    );

    if ( $tasks ) {
        $response['pending'] = array_filter( $tasks, 'cpm_tasks_filter_pending' );
        $response['completed'] = array_filter( $tasks, 'cpm_tasks_filter_done' );
    }

    return $response;
}


function cpm_project_filters(){
    ?>
    <input type="text" id="cpm-search-client" name="searchitem" placeholder="<?php _e( 'Search by Client...', 'cpm' ); ?>" value="" />
    <input type="text" id="cpm-all-search" name="searchitem" placeholder="<?php _e( 'Search All...', 'cpm' ); ?>" value="" />
    <?php
}
/**
 * A category dropdown helper function.
 *
 * @since 0.4.4
 *
 * @param int $current_category_id
 * @param bool $show_count
 * @param boll $show_all
 * @return string
 */
function cpm_dropdown_category( $current_category_id = -1, $show_count = false, $show_all = false, $class = '' ) {
    $args = array(
        'class' => $class,
        'child_of' => 0,
        'depth' => 0,
        'echo' => 0,
        'hide_empty' => 0,
        'hide_if_empty' => 0,
        'hierarchical' => true,
        'name' => 'project_cat',
        'order' => 'ASC',
        'orderby' => 'name',
        'selected' => $current_category_id,
        'show_count' => $show_count,
        'show_option_all' => $show_all ? __( '- All Categories -', 'cpm' ) : '',
        'show_option_none' => !$show_all ? __( '- Project Category -', 'cpm' ) : '',
        'tab_index' => 0,
        'taxonomy' => 'project_category',
    );

    $args = apply_filters( 'cpm_category_dropdown', $args, $current_category_id );

    return wp_dropdown_categories( $args );
}

function cpm_filter_category( $current_category_id ) {
    return cpm_dropdown_category( $current_category_id, false, false );
}
/**
 * Filter function for `cpm_tasks_filter` for completed tasks
 *
 * @since 0.1
 * @param object $task
 * @return bool
 */
function cpm_tasks_filter_done( $task ) {
    return $task->completed == '1';
}

/**
 * Filter function for `cpm_tasks_filter` for pending tasks
 *
 * @since 0.1
 * @param object $task
 * @return bool
 */
function cpm_tasks_filter_pending( $task ) {
    return $task->completed != '1';
}

/**
 * A user dropdown helper function.
 *
 * Similar to `wp_dropdown_users` function, but it is made for custom placeholder
 * attribute and for multiple dropdown. It's mainly used in creating and editing
 * projects.
 *
 * @since 0.1
 * @param type $selected
 * @return string
 */
function cpm_dropdown_users( $selected = array() ) {

    $placeholder = __( 'Select co-workers', 'cpm' );
    $sel = ' selected="selected"';

    $users = get_users();
    $options = array();
    if ( $users ) {
        foreach ($users as $user) {
            $options[] = sprintf( '<option value="%s"%s>%s</option>', $user->ID, array_key_exists( $user->ID, $selected ) ? $sel : '', $user->display_name );
        }
    }

    $dropdown = '<select name="project_coworker[]" id="project_coworker" placeholder="' . $placeholder . '" multiple="multiple">';
    $dropdown .= implode("\n", $options );
    $dropdown .= '</select>';

    return $dropdown;
}

/**
 * Helper function for converting a normal date string to unix date/time string
 *
 * @since 0.1
 * @param string $date
 * @param int $gmt
 * @return string
 */
function cpm_date2mysql( $date, $gmt = 0 ) {
    $time = strtotime( $date );
    //return ( $gmt ) ? gmdate( 'Y-m-d H:i:s', $time ) : get_gmt_from_date( date( 'Y-m-d H:i:s', strtotime( $date ) ) ); //gmdate( 'Y-m-d H:i:s', ( $time + ( get_option( 'timezone_string' ) * 3600 ) ) );
    return ( $gmt ) ? gmdate( 'Y-m-d H:i:s', $time ) : gmdate( 'Y-m-d H:i:s',strtotime( $date ) );
}

/**
 * Displays users as checkboxes from a project
 *
 * @since 0.1
 * @param int $project_id
 */
function cpm_user_checkboxes( $project_id ) {
    $pro_obj  = CPM_Project::getInstance();
    $users    = $pro_obj->get_users( $project_id );
    $cur_user = get_current_user_id();

    // remove current logged in user from list
    if ( array_key_exists( $cur_user, $users ) ) {
        unset( $users[$cur_user] );
    }

    foreach ($users as $key => $user) {
        $sort[$key]  = strtolower( $user['name'] );
    }

    if ( $users ) {
        array_multisort( $sort, SORT_ASC, $users );

        foreach ($users as $user) {
            $check = sprintf( '<input type="checkbox" name="notify_user[]" id="cpm_notify_%1$s" value="%1$s" />', $user['id'] );
            printf( '<label for="cpm_notify_%d">%s %s</label> ', $user['id'], $check, ucwords(strtolower( $user['name'] )) );
        }
    } else {
        echo __( 'No users found', 'cpm' );
    }

    return $users;
}

/**
 * User dropdown for task
 *
 * @since 0.1
 * @param int $project_id
 * @param int $selected
 */
function cpm_task_assign_dropdown( $project_id, $selected = '-1' ) {
    $users = CPM_Project::getInstance()->get_users( $project_id );
    if ( $users ) {
        echo '<select name="task_assign[]" class="chosen-select"  id="task_assign" multiple="multiple" data-placeholder="' . __( 'Select User', 'cpm' ) . '">';

        foreach ($users as $user) {

            if ( is_array( $selected ) ) {
                $selectd_status = in_array($user['id'], $selected) ? 'selected="selected"' : '';
            } else {
                $selectd_status = selected( $selected, $user['id'], false );
            }
            printf( '<option value="%s"%s>%s</opton>', $user['id'], $selectd_status, $user['name'] );
        }
        echo '</select>';
    }
}

/**
 * Comment form upload field helper
 *
 * Generates markup for ajax file upload list and prints attached files.
 *
 * @since 0.1
 * @param int $id comment ID. used for unique edit comment form pickfile ID
 * @param array $files attached files
 */
function cpm_upload_field( $id, $files = array() ) {
    $id = $id ? '-' . $id : '';
    ?>
    <div id="cpm-upload-container<?php echo $id; ?>">
        <div class="cpm-upload-filelist">
            <?php if ( $files ) { ?>
                <?php foreach ($files as $file) {
                    $delete = sprintf( '<a href="#" data-id="%d" class="cpm-delete-file button">%s</a>', $file['id'], __( 'Delete File' ) );
                    $hidden = sprintf( '<input type="hidden" name="cpm_attachment[]" value="%d" />', $file['id'] );
                    $file_url = sprintf( '<a href="%1$s" target="_blank"><img src="%2$s" alt="%3$s" /></a>', $file['url'], $file['thumb'], esc_attr( $file['name'] ) );

                    $html = '<div class="cpm-uploaded-item">' . $file_url . ' ' . $delete . $hidden . '</div>';
                    echo $html;
                } ?>
            <?php } ?>
        </div>
        <?php printf( __('To attach, <a id="cpm-upload-pickfiles%s" href="#">select files</a> from your computer.', 'cpm' ), $id ); ?>
    </div>
    <?php
}

/**
 * Helper function for formatting date field
 *
 * @since 0.1
 * @param string $date
 * @param bool $show_time
 * @return string
 */
function cpm_get_date( $date, $show_time = false ) {

    $date = strtotime( $date );

    if ( $show_time ) {
        $format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
    } else {
        $format = get_option( 'date_format' );
    }
    //$format = 'M j, Y';
    $date_html = sprintf( '<time datetime="%1$s" title="%1$s">%2$s</time>', date( 'c', $date ), date_i18n( $format, $date ) );

    return apply_filters( 'cpm_get_date', $date_html, $date );
}

/**
 * Helper function for formatting date field without html
 *
 * @since 1.2
 * @param string $date
 * @param bool $show_time
 * @return string
 */
function cpm_get_date_without_html( $date, $show_time = false ) {

    $date = strtotime( $date );

    if ( $show_time ) {
        $format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
    } else {
        $format = get_option( 'date_format' );
    }
    //$format = 'M j, Y';
    $date_html = sprintf( '%s', date_i18n( $format, $date ) );

    return apply_filters( 'cpm_get_date_without_html', $date_html, $date );
}

/**
 * Show info messages
 *
 * @since 0.1
 * @param string $msg message to show
 * @param string $type message type
 */
function cpm_show_message( $msg, $type = 'cpm-updated' ) {
    ?>
    <div class="<?php echo esc_attr( $type ); ?>">
        <p><strong><?php echo $msg; ?></strong></p>
    </div>
    <?php
}

/**
 * Helper function to generate task list completeness progressbar
 *
 * @since 0.1
 * @param int $total
 * @param int $completed
 * @return string
 */
function cpm_task_completeness( $total, $completed ) {
    //skipping vision by zero problem
    if ( $total < 1 ) {
        return;
    }

    $percentage = (100 * $completed) / $total;

    ob_start();
    ?>
    <div class="cpm-progress cpm-progress-info">
        <div style="width:<?php echo $percentage; ?>%" class="bar completed"></div>
        <div class="text"><?php printf( '%s: %d%% (%d of %d)', __( 'Completed', 'cpm' ), $percentage, $completed, $total ); ?></div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Helper function to calcalute left milestone
 *
 * @since 0.1
 * @param int $from
 * @param int $to
 * @return bool
 */
function cpm_is_left( $from, $to ) {
    $diff = $to - $from;

    if ( $diff > 0 ) {
        return true;
    }

    return false;
}

/**
 * The main logging function
 *
 * @since 0.1
 * @uses error_log
 * @param string $type type of the error. e.g: debug, error, info
 * @param string $msg
 */
function cpm_log( $type = '', $msg = '' ) {
    if ( WP_DEBUG == true ) {
        $msg = sprintf( "[%s][%s] %s\n", date( 'd.m.Y h:i:s' ), $type, $msg );
        error_log( $msg, 3, dirname( __FILE__ ) . '/debug.log' );
    }
}

/**
 * Helper function for displaying localized numbers
 *
 * @since 0.1
 * @param int $number
 * @return string
 */
function cpm_get_number( $number ) {
    return number_format_i18n( $number );
}

/**
 * Helper function for generating anchor tags
 *
 * @since 0.1
 * @param string $link
 * @param string $text
 * @return string
 */
function cpm_print_url( $link, $text ) {
    return sprintf( '<a href="%s">%s</a>', $link, $text );
}

/**
 * Displays tasks, messages, milestones contents. Removed `the_content` filter
 * and applied other filters due to conflicts created by other plugins.
 *
 * @since 0.1
 * @param string $content
 * @return string
 */
function cpm_get_content( $content ) {
    $content = apply_filters( 'cpm_get_content', $content );

    return $content;
}

/**
 * Helper function to include `header.php` ono project tabs
 *
 * @since 0.1
 * @param string $active_menu
 * @param int $project_id
 */
function cpm_get_header( $active_menu, $project_id = 0 ) {
    $cpm_active_menu = $active_menu;
    require_once CPM_PATH . '/views/project/header.php';
}

/**
 * Displays comment texts. Mainly used for applying `comment_text` filter
 * on messages, tasks and to-do's comments.
 *
 * @since 0.1
 * @param type $comment_ID
 * @return string
 */
function cpm_comment_text( $comment_ID = 0 ) {
    $comment = get_comment( $comment_ID );
    return apply_filters( 'comment_text', get_comment_text( $comment_ID ), $comment );
}

/**
 * Helper function for displaying excerpts
 *
 * @since 0.1
 * @param string $text
 * @param int $length
 * @param string $append
 * @return string
 */
function cpm_excerpt( $text, $length, $append = '...' ) {
    $text  = wp_strip_all_tags( $text, true );

    if ( function_exists( 'mb_strlen' ) ) {
        $count = mb_strlen( $text );
        $text  = mb_substr( $text, 0, $length );
    } else {
        $count = strlen( $text );
        $text  = substr( $text, 0, $length );
    }

    if ( $count > $length ) {
        $text = $text . $append;
    }

    return $text;
}

/**
 * Helper function for displaying data attributes on HTML tags
 *
 * @since 0.1
 * @param array $values
 */
function cpm_data_attr( $values ) {

    $data = array();
    foreach ($values as $key => $val) {
        $data[] = sprintf( 'data-%s="%s"', $key, esc_attr( $val ) );
    }

    echo implode( ' ', $data );
}

/**
 * Helper function for displaying project summary
 *
 * @since 0.1
 * @param object $info
 * @return string
 */
function cpm_project_summary( $info ) {
    $info_array = array();

    if( $info->discussion ) {
        $info_array[] = sprintf( _n( '<strong>%d </strong> Message', '<strong>%d </strong> Messages', $info->discussion, 'cpm' ), $info->discussion );
    }

    if( $info->todolist ) {
        $info_array[] = sprintf( _n( '<strong>%d </strong> To-do list', '<strong>%d </strong> To-do lists', $info->todolist, 'cpm' ), $info->todolist );
    }

    if( $info->todos ) {
        $info_array[] = sprintf( _n( '<strong>%d </strong> To-do', '<strong>%d </strong> To-dos', $info->todos, 'cpm' ), $info->todos );
    }

    if( $info->comments ) {
        $info_array[] = sprintf( _n( '<strong>%d </strong> Comment', '<strong>%d </strong> Comments', $info->comments, 'cpm' ), $info->comments );
    }

    if( $info->files ) {
        $info_array[] = sprintf( _n( '<strong>%d </strong> File', '<strong>%d </strong> Files', $info->files, 'cpm' ), $info->files );
    }

    if( $info->milestone ) {
        $info_array[] = sprintf( _n( '<strong>%d </strong> Milestone', '<strong>%d </strong> Milestones', $info->milestone, 'cpm' ), $info->milestone );
    }

    return implode(' <br/>', $info_array );
}

/**
 * Serve project files with proxy
 *
 * This function handles project files for privacy. It gets the file ID
 * and project ID as input. Checks if the current user has access on that
 * project and serves the attached file with right header type. If the
 * request is not from a user from this project, s/he will not be able to
 * see the file.
 *
 * @uses `wp_ajax_cpm_file_get` action
 * @since 0.3
 */
function cpm_serve_file() {
    $file_id = isset( $_GET['file_id'] ) ? intval( $_GET['file_id'] ) : 0;
    $project_id = isset( $_GET['project_id'] ) ? intval( $_GET['project_id'] ) : 0;
    $type = isset( $_GET['type'] ) ? $_GET['type'] : 'full';

    //check permission
    $pro_obj = CPM_Project::getInstance();
    $project = $pro_obj->get( $project_id );
    if ( !$pro_obj->has_permission( $project ) ) {
        die( 'file access denied' );
    }

    //get file path
    $file_path = get_attached_file( $file_id );
    if ( !file_exists( $file_path ) ) {
        header( "Status: 404 Not Found" );
        die('file not found');
    }

    if ( $type == 'thumb' ) {
        $metadata = wp_get_attachment_metadata( $file_id );
        $filename = basename( $file_path );

        //if thumbnail is found, replace file name with thumb file name
        if ( array_key_exists( 'thumbnail', $metadata['sizes'] ) ) {
            $file_path = str_replace( $filename, $metadata['sizes']['thumbnail']['file'], $file_path );
        }
    }

    $extension = strtolower( substr( strrchr( $file_path, '.' ), 1 ) );

    // get the file mime type using the file extension
    switch ($extension) {
        case 'jpeg':
        case 'jpg':
            $mime = 'image/jpeg';
            break;

        case 'png':
            $mime = 'image/png';
            break;

        case 'gif':
            $mime = 'image/gif';
            break;

        case 'bmp':
            $mime = 'image/bmp';
            break;

        default:
            $mime = 'application/force-download';
    }

    // serve the file with right header
    if ( is_readable( $file_path ) ) {
        header( 'Content-Type: ' . $mime );
        header( 'Content-Transfer-Encoding: binary' );
        header( 'Content-Disposition: inline; filename=' . basename( $file_path ) );
        readfile( $file_path );
    }

    exit;
}

add_action( 'wp_ajax_cpm_file_get', 'cpm_serve_file' );

/**
 * Show denied file access for un-authenticated users
 *
 * @since 0.3.1
 */
function cpm_serve_file_denied() {
    die( 'file access denied' );
}

add_action( 'wp_ajax_nopriv_cpm_file_get', 'cpm_serve_file_denied' );

/**
 * Remove comments from listing publicly
 *
 * Hides all comments made on project, task_list, task, milestone, message
 * from listing on frontend, admin dashboard, and admin comments page.
 *
 * @since 0.2
 *
 * @param array $clauses
 * @return array
 */
function cpm_hide_comments( $clauses ) {
    global $wpdb, $pagenow;

    if ( !is_admin() || $pagenow == 'edit-comments.php' || (is_admin() && $pagenow == 'index.php') ) {
        $post_types = implode( "', '", array('project', 'task_list', 'task', 'milestone', 'message') );
        $clauses['join'] .= " JOIN $wpdb->posts as cpm_p ON cpm_p.ID = $wpdb->comments.comment_post_ID";
        $clauses['where'] .= " AND cpm_p.post_type NOT IN('$post_types')";
    }

    return $clauses;
}

add_filter( 'comments_clauses', 'cpm_hide_comments', 99 );

/**
 * Hide project comments from comment RSS
 *
 * @global object $wpdb
 * @param string $where
 * @return string
 */
function cpm_hide_comment_rss( $where ) {
    global $wpdb;

    $post_types = implode( "', '", array('project', 'task_list', 'task', 'milestone', 'message') );
    $where .= " AND {$wpdb->posts}.post_type NOT IN('$post_types')";

    return $where;
}

add_filter( 'comment_feed_where', 'cpm_hide_comment_rss' );


/**
 * Get the value of a settings field
 *
 * @since 0.4
 * @param string $option option field name
 * @return mixed
 */
function cpm_get_option( $option ) {

    $fields = CPM_Admin::get_settings_fields();
    $prepared_fields = array();

    //prepare the array with the field as key
    //and set the section name on each field
    foreach ($fields as $section => $field) {
        foreach ($field as $fld) {
            $prepared_fields[$fld['name']] = $fld;
            $prepared_fields[$fld['name']]['section'] = $section;
        }
    }

    // bail if option not found
    if ( !isset( $prepared_fields[$option] ) ) {
        return;
    }

    //get the value of the section where the option exists
    $opt = get_option( $prepared_fields[$option]['section'] );
    $opt = is_array($opt) ? $opt : array();

    //return the value if found, otherwise default
    if ( array_key_exists( $option, $opt ) ) {
        return $opt[$option];
    } else {
        $val = isset( $prepared_fields[$option]['default'] ) ? $prepared_fields[$option]['default'] : '';
        return $val;
    }
}

if ( !function_exists( 'get_ipaddress' ) ) {

    /**
     * Returns users current IP Address
     *
     * @since 0.4
     * @return string IP Address
     */
    function get_ipaddress() {
        if ( empty( $_SERVER["HTTP_X_FORWARDED_FOR"] ) ) {
            $ip_address = $_SERVER["REMOTE_ADDR"];
        } else {
            $ip_address = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }
        if ( strpos( $ip_address, ',' ) !== false ) {
            $ip_address = explode( ',', $ip_address );
            $ip_address = $ip_address[0];
        }
        return $ip_address;
    }

}


function cpm_settings_label() {
    $labels = array(

        'Message' => array(
            'create_message'   => __( 'Create', 'cpm' ),
            'msg_view_private' => __( 'View Private', 'cpm' ),
        ),

        'Todo List' => array(
            'create_todolist'      => __( 'Create', 'cpm' ),
            'tdolist_view_private' => __( 'View Private', 'cpm' ),
        ),

        'Todo' => array(
            'create_todo'       => __( 'Create', 'cpm' ),
            'todo_view_private' => __( 'View Private', 'cpm' ),
        ),

        'Milestone' => array(
            'create_milestone'       => __( 'Create', 'cpm' ),
            'milestone_view_private' => __( 'View Private', 'cpm' ),
        )
    );

    return apply_filters( 'cpm_project_permission',  $labels );
}

function cpm_project_user_role_pre_chache( $project_id ) {
    global $wpdb;

    $table = $wpdb->prefix . 'cpm_user_role';
    $user_id = get_current_user_id();

    $role = $wpdb->get_var( $wpdb->prepare("SELECT role FROM {$table} WHERE project_id = '%d' AND user_id='%d'", $project_id, $user_id ) );

    $project_user_role = !empty( $role ) ? $role : false;

    return $project_user_role;
}


/**
 * Get a project user role by project id
 *
 * @param int $project_id
 * @return string
 */
function cpm_project_user_role( $project_id ) {

    $user_id = get_current_user_id();
    $cache_key = 'cpm_project_user_role_' . $project_id . $user_id;
    $project_user_role = wp_cache_get( $cache_key );

    if ( $project_user_role === false ) {
        $project_user_role = cpm_project_user_role_pre_chache( $project_id );
        wp_cache_set( $cache_key, $project_user_role );
    }

    return $project_user_role;
}

function cpm_is_single_project_manager( $project_id ) {

    if ( ! cpm_is_pro() ) {
        return true;
    }

    $project_user_role = cpm_project_user_role( $project_id );

    if ( $project_user_role == 'manager' ) {
        return true;
    } else {
        return false;
    }
}

function cpm_manage_capability( $option_name = 'project_manage_role' ) {

    if ( ! cpm_is_pro() ) {
        return true;
    }

    global $current_user;

    if ( ! $current_user ) {
        return false;
    }

    $loggedin_user_role = reset( $current_user->roles );
    $manage_capability = cpm_get_option( $option_name );

    if ( array_key_exists( $loggedin_user_role, $manage_capability ) ) {
        return true;
    }

    return false;
}

function cpm_user_can_delete_edit( $project_id, $list ) {

    if ( ! cpm_is_pro() ) {
        return true;
    }

    global $current_user;

    $project_user_role  = cpm_project_user_role( $project_id );
    $loggedin_user_role = reset( $current_user->roles );
    $manage_capability  = cpm_get_option( 'project_manage_role' );
    //var_dump( $current_user->ID, $list->post_author, $project_user_role, $loggedin_user_role, $manage_capability ); die();
    // grant project manager all access
    // also if the user role has the ability to manage all projects from settings, allow him
    if ( $project_user_role == 'manager' || array_key_exists( $loggedin_user_role, $manage_capability ) || $current_user->ID == $list->post_author ) {
        return true;
    }

    return false;
}

/**
 * In the case of create use  is_cpm_user_can_access( $project_id, $section )
 *
 * In the case of view user  ! is_cpm_user_can_access( $project_id, $section )
 */

function cpm_user_can_access( $project_id, $section='' ) {

    if ( ! cpm_is_pro() ) {
        return true;
    }

    global $current_user;

    $login_user = apply_filters( 'cpm_current_user_access', $current_user, $project_id, $section );
    $project_user_role  = cpm_project_user_role( $project_id );
    $loggedin_user_role = reset( $login_user->roles );
    $manage_capability  = cpm_get_option( 'project_manage_role' );

    // grant project manager all access
    // also if the user role has the ability to manage all projects from settings, allow him
    if ( $project_user_role == 'manager' || array_key_exists( $loggedin_user_role, $manage_capability ) ) {
        return true;
    }

    // Now, if the user is not manager, check if he can access from settings
    $settings_role = get_post_meta( $project_id, '_settings', true );
    $can_access    = isset( $settings_role[$project_user_role][$section] ) ? $settings_role[$project_user_role][$section] : '';

    if( $can_access == 'yes' ) {
        return true;
    } else {
        return false;
    }
}

function cpm_user_can_access_file( $project_id, $section, $is_private ) {

    if ( ! cpm_is_pro() ) {
        return true;
    }

    if ( $is_private == 'no' ) {
        return true;
    }

    return cpm_user_can_access( $project_id, $section );
}


/**
 * Get all the orders from a specific seller
 *
 * @global object $wpdb
 * @param int $seller_id
 * @return array
 */


function cpm_project_count() {
    global $wpdb;
    $table = $wpdb->prefix . 'cpm_user_role';

    $user_id   = get_current_user_id();
    $cache_key = 'cpm_project_count';
    $count     = wp_cache_get( $cache_key, 'cpm' );

    if( isset( $_GET['project_cat'] ) && !empty( $_GET['project_cat'] ) && ( $_GET['project_cat'] != '-1' ) ) {
        $project_category      = $_GET['project_cat'];
        $project_category_join = " LEFT JOIN {$wpdb->term_relationships} as term ON term.object_id = post.ID";
        $project_category      = " AND term.term_taxonomy_id IN ($project_category)";

    } else {
        $project_category      = '';
        $project_category_join = '';
    }

    $project_category_join = apply_filters( 'cpm_project_activity_count_join', $project_category_join );
    $project_category      = apply_filters( 'cpm_project_activity_count_where', $project_category );

    if( cpm_manage_capability() == false ) {
        $role_join  = "LEFT JOIN {$table} AS role ON role.project_id = post.ID";
        $role_where = "AND role.user_id = $user_id";
    } else {
        $role_join  = '';
        $role_where = '';
    }

    if ( $count === false ) {
        $sql = "SELECT COUNT(post.ID) AS count, meta.meta_value AS type FROM {$wpdb->posts} AS post
            LEFT JOIN {$wpdb->postmeta} AS meta on meta.post_id = post.ID
            $project_category_join
            $role_join
            WHERE
                post.post_type ='project'
                AND post.post_status = 'publish'
                $project_category
                $role_where
                AND meta.meta_key = '_project_active'
                GROUP BY meta.meta_value";

        $count = $wpdb->get_results( $sql );

        wp_cache_set( $cache_key, $count, 'cpm' );
    }

    if( is_array($count) && count($count) ) {
        foreach( $count as $key=>$obj) {
            if( $obj->type == 'yes' ) {
                $active = $obj->count;
            }
            if( $obj->type == 'no' ) {
                $archive = $obj->count;
            }
        }
    }

    $count['active'] = isset( $active ) ? $active : 0;
    $count['archive'] = isset( $archive ) ? $archive : 0;

    return $count;
}


function cpm_project_actions( $project_id ) {

    if( isset( $_GET['action'] ) && $_GET['action'] == 'single' ) {
        $action = __( 'Action', 'cpm' );
        $class = 'cpm-single-action';
    } else {
        $action = '';
        $class = 'cpm-action';
    }
    ?>
    <div class="<?php echo $class; ?>">

    <div class="cpm-settings-bind cpm-settings-icon-cog"><span><?php echo $action; ?></span></div>

        <ul class="cpm-right cpm-settings" >
            <li>
                <span class="cpm-icons-cross"></span>
                <a href="<?php echo cpm_url_projects() ?>" class="cpm-project-delete-link" title="<?php esc_attr_e( 'Delete project', 'cpm' ); ?>" <?php cpm_data_attr( array('confirm' => __( 'Are you sure to delete this project?', 'cpm' ), 'project_id' => $project_id) ) ?>>
                    <span><?php _e( 'Delete', 'cpm' ); ?></span>
                </a>
            </li>
            <li>
                <span class="cpm-icons-checkmark"></span>
                <?php if ( get_post_meta( $project_id, '_project_active', true ) == 'yes' ) { ?>
                    <a class="cpm-archive" data-type="archive" data-project_id="<?php echo $project_id; ?>" href="#"><span><?php _e( 'Completed', 'cpm' ); ?></span></a>
                <?php } else { ?>
                    <a class="cpm-archive" data-type="restore" data-project_id="<?php echo $project_id; ?>" href="#"><span><?php _e( 'Restore', 'cpm' ); ?></span></a>
                <?php } ?>
            </li>
            <li>
                <span class="cpm-icons-docs"></span>
                <a class="cpm-duplicate-project" href="<?php echo add_query_arg( array('page'=>'cpm_projects') ,get_permalink() ); ?>" data-project_id="<?php echo $project_id; ?>"><span><?php _e( 'Duplicate', 'cpm' ); ?></span></a>
            </li>
        </ul>
    </div>
    <?php

}

/**
 * Check if a project is archived/completed
 *
 * @param int $project_id
 * @return boolean
 */
function cpm_is_project_archived( $project_id ) {
    $active = get_post_meta( $project_id, '_project_active', true );

    if ( $active == 'no' ) {
        return true;
    }

    return false;
}

function cpm_assigned_user( $users ) {

    if ( is_array( $users ) ) {
        foreach ($users as $user_id ) {
            echo '<span class="cpm-assigned-user">';
            echo cpm_url_user( $user_id );
            echo '</span>';
        }
    } else {
        echo '<span class="cpm-assigned-user">';
        echo cpm_url_user( $users );
        echo '</span>';
    }

}

function cpm_pagination( $total, $limit, $pagenum ) {
    $num_of_pages = ceil( $total / $limit );
    $page_links = paginate_links( array(
        'base'      => add_query_arg( 'pagenum', '%#%' ),
        'format'    => '',
        'prev_text' => __( '&laquo;', 'aag' ),
        'next_text' => __( '&raquo;', 'aag' ),
        'add_args'  => false,
        'total'     => $num_of_pages,
        'current'   => $pagenum
    ) );

    if ( $page_links ) {
        echo '<div class="tablenav"><div class="tablenav-pages">' . $page_links . '</div></div>';
    }
}

add_action( 'cpm_delete_project_prev', 'cpm_delete_project_child' );

/**
 * Delete project child elements
 *
 * Delete all child task lists, messages and milestones when
 * a project is deleted.
 *
 * @param  int  $project_id
 *
 * @since 0.5.4
 *
 * @return void
 */
function cpm_delete_project_child( $project_id ) {

    $childrens = get_posts( array( 'post_type' => array( 'task_list', 'message', 'milestone' ), 'post_pre_page' => '-1', 'post_parent' => $project_id ) );

    foreach ( $childrens as $key => $children ) {
        switch ( $children->post_type ) {
            case 'task_list':
                CPM_Task::getInstance()->delete_list( $children->ID, true );
                break;
            case 'message':
                CPM_Message::getInstance()->delete( $children->ID, true );
                break;
            case 'milestone':
                CPM_Milestone::getInstance()->delete( $children->ID, true );
                break;
        }

    }
}

/**
 * Get all manager from a project
 *
 * @param  int  $project_id
 *
 * @since 0.1
 *
 * @return array
 */

function cpm_get_all_manager_from_project( $project_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'cpm_user_role';
    $result = $wpdb->get_results( "SELECT `user_id` FROM $table WHERE project_id='$project_id' AND role='manager'" );
    return wp_list_pluck( $result, 'user_id' );
}

/**
 * Get email header
 *
 * @param  string $action
 *
 * @since 1.1
 *
 * @return void
 */

function cpm_get_email_header() {
    $file_path   = CPM_PATH . '/views/emails/header.php';
    $header_path = apply_filters( 'cpm_email_header', $file_path );

    if ( file_exists( $header_path ) ) {
        require_once $header_path;
    }
}

/**
 * Get email footer
 *
 * @param  string $action
 *
 * @since 1.1
 *
 * @return void
 */

function cpm_get_email_footer() {
    $file_path   = CPM_PATH . '/views/emails/footer.php';
    $footer_path = apply_filters( 'cpm_email_footer', $file_path );

    if ( file_exists( $footer_path ) ) {
        require_once $footer_path;
    }

}

/**
 * Get co-workers
 *
 * @since 1.1
 * @return object
 */
function cpm_get_co_worker() {
    global $wpdb;
    $table = $wpdb->prefix . 'cpm_user_role';
    return $wpdb->get_results( "SELECT DISTINCT user_id FROM $table WHERE role IN( 'manager', 'co_worker' )" );
}

/**
 * Get co-workers
 *
 * @param str $start
 * @param str $end
 * @param str $check
 *
 * @since 1.2
 * @return boolen
 */
function cpm_date_range( $start, $end, $check ) {

    $start = date( 'Y-m-d H:i:s', strtotime( $start ) );
    $end   = date( 'Y-m-d H:i:s', strtotime( $end ) );
    $check = date( 'Y-m-d H:i:s', strtotime( $check ) );

    if ( $start <= $check && $end >= $check ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Get ordinal number
 *
 * @param int $number
 *
 * @since 1.2
 * @return str
 */
function cpm_ordinal( $number ) {
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    if ((($number % 100) >= 11) && (($number%100) <= 13))
        return $number. 'th';
    else
        return $number. $ends[$number % 10];
}

/**
 * Localize script message
 *
 * @since 1.2
 * @return array
 */
function cpm_message() {
    $message = array(
        'report_frm_field_limit'       => __( 'You can not use this field more than once!', 'cpm' ),
        'report_total_frm_field_limit' => __( 'You can not create more than 4 action', 'cpm' ),
    );

    return apply_filters( 'cpm_message', $message );
}

function cpm_is_pro() {

    if ( file_exists( CPM_PATH . '/includes/pro/loader.php' ) ) {
        return true;
    }
    return false;
}

/**
 * Wrapper function for string length
 *
 * @since 1.3
 *
 * @param  string  $string
 *
 * @return int
 */
function cpm_strlen( $string ) {
    if ( function_exists( 'mb_strlen' ) ) {
        return mb_strlen( $string );
    } else {
        return strlen( $string );
    }
}
