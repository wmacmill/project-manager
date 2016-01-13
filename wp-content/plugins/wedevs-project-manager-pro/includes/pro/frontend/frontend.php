<?php
/**
 * Project Manager Frontend class
 *
 * @author Tareq Hasan <tareq@wedevs.com>
 */
class CPM_Frontend {
    private $plugin_slug = 'cpm-frontend';

    function __construct() {
        add_filter( 'cpm_settings_sections', array( $this, 'settings_section') );
        add_filter( 'cpm_settings_fields', array( $this, 'page_settings') );
        $this->includes();
        $this->instantiate();
        $this->form_actions();

        if ( is_admin() ) {
            return;
        }
        add_action( 'wp_enqueue_scripts', array( cpm(), 'admin_scripts') );
        add_action( 'wp_enqueue_scripts', array( cpmpro(), 'calender_scripts') );
        add_action( 'wp_enqueue_scripts', array( cpmpro(), 'my_task_scripts') );
        add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts') );

        add_action( 'admin_notices', array($this, 'update_notification') );

        add_shortcode( 'cpm', array($this, 'shortcode') );
        add_shortcode( 'cpm_calendar', array($this, 'calender') );
        add_shortcode( 'cpm_my_task', array($this, 'my_task') );

       

    }

    function settings_section( $section ) {
        $section[] = array(
            'id'    => 'cpm_page',
            'title' => __( 'Page Settings', 'cpmf' )
        );

        return $section;
    }

    function page_settings( $settings_fields ) {
        $pages_array = CPM_Admin::get_post_type( 'page' );

        $settings_fields['cpm_page'] = array(
            array(
                'name'    => 'project',
                'label'   => __('Project', 'cpmf'),
                'type'    => 'select',
                'options' => $pages_array,
            ),

            array(
                'name'    => 'my_task',
                'label'   => __( 'My Task', 'cpmf' ),
                'type'    => 'select',
                'options' => $pages_array,
            ),
            array(
                'name'    => 'calendar',
                'label'   => __( 'Calendar', 'cpmf' ),
                'type'    => 'select',
                'options' => $pages_array,
            ),

        );

        return $settings_fields;
    }

    function my_task($atts) {
        if ( !class_exists('WeDevs_CPM') ) {
            return __( 'Sorry, main plugin is not installed', 'cpmf');
        }
        if ( !is_user_logged_in() ) {
            return wp_login_form( array('echo' => false) );
        }
        extract( shortcode_atts( array('id' => 0), $atts ) );

        if ( !is_user_logged_in() ) {
            return wp_login_form( array('echo' => false) );
        }

        ob_start();

        require_once dirname(__FILE__)  . '/../views/task/my-task.php';
        return ob_get_clean();
    }

    function calender( $atts ) {
        if ( !class_exists('WeDevs_CPM') ) {
            return __( 'Sorry, main plugin is not installed', 'cpmf');
        }

        if ( !is_user_logged_in() ) {
            return wp_login_form( array('echo' => false) );
        }

        extract( shortcode_atts( array('id' => 0), $atts ) );
        ob_start();
        require_once dirname(__FILE__)  . '/../views/calendar/index.php';
        return ob_get_clean();
    }

    /**
     * Load styles and scripts
     *
     * @since 1.0
     */
    function enqueue_scripts() {
        wp_enqueue_style( 'cpm-frontend', plugins_url( 'css/style.css', __FILE__ ) );
    }


    /**
     * Includes all required files if the parent plugin is intalled
     *
     * @since 1.0
     */
    function includes() {


        if ( ! is_admin() ) {
           
            //require_once dirname(__FILE__)  . '/includes/functions.php';
            //require_once dirname(__FILE__)  . '/includes/urls.php';
            //require_once dirname(__FILE__)  . '/includes/html.php';
            //require_once dirname(__FILE__)  . '/includes/shortcodes.php';
        }
         require_once dirname( __FILE__)  . '/urls.php';
        // load url filters
        
    }

    /**
     * Instantiate required classes
     *
     * @since 1.0
     */
    function instantiate() {

        //instantiate the URL filter class only if it's the frontend area or
        //the request is made from frontend
        if ( ! is_admin() || isset( $_REQUEST['cpmf_url'] ) ||  ( isset( $_REQUEST['is_admin'] ) &&  $_REQUEST['is_admin']== 'no' ) ) {
            new CPM_Frontend_URLs();
        }
    }

    /**
     * Main shortcode handler function
     *
     * @since 1.0
     * @param array $atts
     * @param string $content
     * @return string
     */
    function shortcode( $atts, $content = null ) {
        extract( shortcode_atts( array('id' => 0), $atts ) );

        if ( !is_user_logged_in() ) {
            return wp_login_form( array('echo' => false) );
        }

        if ( $id ) {
            $project_id = $id;
        } else {
            $project_id = isset( $_GET['project_id'] ) ? intval( $_GET['project_id'] ) : 0;
        }

        ob_start();
        ?>

        <div class="cpm">
            <?php
            if ( $project_id ) {
                $this->single_project( $project_id );
            } else {
                $this->list_projects();
            }
            ?>
        </div> <!-- .cpm -->
        <?php

        return ob_get_clean();
    }

    /**
     * List all projects
     *
     * @since 1.0
     */
    function list_projects() {
        $project_obj    = CPM_Project::getInstance();
        $projects       = $project_obj->get_projects();
        $total_projects = $projects['total_projects'];
        $db_limit       = intval( cpm_get_option( 'pagination' ) );
        $limit          = $db_limit ? $db_limit : 10;
        $pagenum        = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1; 
        $status_class   = isset( $_GET['status'] ) ? $_GET['status'] : 'active';
        
        unset($projects['total_projects']);

        if ( function_exists( 'cpm_project_count' ) ) {
            $count = cpm_project_count();
        }
        ?>

        <div class="icon32" id="icon-themes"><br></div>
        <h2><?php _e( 'Project Manager', 'cpm' ); ?></h2>

        <?php     
            $category = isset( $_GET['project_cat'] ) ? $_GET['project_cat'] : '';
            $action   = isset( $_GET['status'] ) ? $_GET['status'] : '';
            $page_id  = ( ! is_admin() ) ? get_the_ID() : '';
        ?>
        <form action="" method="get" class="cpm-project-filters" id="cpm-project-filters">
            <?php echo cpm_filter_category( $category ); ?>
            <input type="hidden" name="p" value="<?php echo $page_id; ?>" />
            <input type="hidden" name="status" value="<?php echo $action; ?>" />
            <input type="hidden" name="page" value="cpm_projects" />
            <input type="submit" name="submit" id="project-filter-submit" class="button" value="<?php esc_attr_e( 'Filter', 'cpm' ); ?>">
        </form>

        <?php
        if ( function_exists( 'cpm_project_filters' ) ) {
            do_action( 'cpm_filter_project', $projects );  
        }
        ?>

        <div class="cpm-projects">

            <?php if ( function_exists( 'cpm_project_filters' ) ) { ?>
                <ul class="list-inline order-statuses-filter">
                    <li<?php echo $status_class == 'all' ? ' class="active"' : ''; ?>>
                        <a href="<?php echo cpm_url_all(); ?>"><?php _e( 'All', 'cpm' ); ?></a>
                    </li>
                    <li<?php echo $status_class == 'active' ? ' class="active"' : ''; ?>>
                        <a class="cpm-active" href="<?php echo cpm_url_active(); ?>"><?php printf( __( 'Active (%d)', 'cpm' ), $count['active'] ); ?></a>
                    </li>
                    <li<?php echo $status_class == 'archive' ? ' class="active"' : ''; ?>>
                        <a class="cpm-archive-head" href="<?php echo cpm_url_archive(); ?>"><?php printf( __( 'Completed (%d)', 'cpm' ), $count['archive'] ); ?></a>
                    </li>
                </ul>
            <?php } ?>

            <?php if ( cpm_manage_capability( 'project_create_role' ) ) { ?>
                <nav class="cpm-new-project">
                    <a href="#" id="cpm-create-project"><span><?php _e( 'New Project', 'cpm' ); ?></span></a>
                </nav>
            <?php } ?>

            <?php
            foreach ($projects as $project) {

                if ( !$project_obj->has_permission( $project ) ) {
                    continue;
                }
                ?>
                <article class="cpm-project">
                    <?php if ( cpm_is_project_archived( $project->ID ) ) { ?>
                        <div class="cpm-completed-wrap"><div class="ribbon-green"><?php _e( 'Completed', 'cpm' ); ?></div></div>
                    <?php } ?>

                    <a title="<?php echo get_the_title( $project->ID ); ?>" href="<?php echo cpm_url_project_details( $project->ID ); ?>">
                        <h5><?php echo cpm_excerpt( get_the_title( $project->ID ), 30 ); ?></h5>

                        <div class="cpm-project-detail"><?php echo cpm_excerpt( $project->post_content, 55 ); ?></div>
                        <div class="cpm-project-meta">
                            <?php echo cpm_project_summary( $project->info ); ?>
                        </div>

                        <footer class="cpm-project-people">
                            <div class="cpm-scroll">
                            <?php

                            if(count( $project->users )) {
                                foreach( $project->users as $id=>$user_meta) {
                                    echo get_avatar( $id, 48,'', $user_meta['name']);
                                }
                            }


                            ?>
                            </div>
                        </footer>
                    </a>

                    <?php
                        $progress = $project_obj->get_progress_by_tasks( $project->ID );
                        echo cpm_task_completeness( $progress['total'], $progress['completed'] );

                        if( cpm_user_can_access( $project->ID ) ) {
                            cpm_project_actions( $project->ID );
                        }
                     ?>


                </article>

            <?php } 
            cpm_pagination( $total_projects, $limit, $pagenum );
            ?>

        </div>
        <?php 
        if ( cpm_manage_capability( 'project_create_role' ) ) { ?>
            <div id="cpm-project-dialog" title="<?php _e( 'Start a new project', 'cpm' ); ?>" style="display: none;">
                    <?php cpm_project_form(); ?>
            </div>

            <div id="cpm-create-user-wrap" title="<?php _e( 'Create a new user', 'cpm' ); ?>">
                <?php cpm_user_create_form(); ?>
            </div>


            <script type="text/javascript">
                jQuery(function($) {
                    $( "#cpm-project-dialog, #cpm-create-user-wrap" ).dialog({
                        autoOpen: false,
                        modal: true,
                        dialogClass: 'cpm-ui-dialog',
                        width: 485,
                        height: 430,
                        position:['middle', 100],
                        zIndex: 9999,
                    });
                });

                jQuery(function($) {
                    $( "#cpm-create-user-wrap" ).dialog({
                        autoOpen: false,
                        modal: true,
                        dialogClass: 'cpm-ui-dialog cpm-user-ui-dialog',
                        width: 400,
                        height: 'auto',
                        position:['middle', 100],
                    });
                });
            </script>
            <?php
        }
    }

    /**
     * Display a single project
     *
     * @since 1.0
     * @param int $project_id
     */
    function single_project( $project_id ) {
        remove_filter('comments_clauses', 'cpm_hide_comments', 99 );

        $pro_obj = CPM_Project::getInstance();
        $activities = $pro_obj->get_activity( $project_id, array() );

        $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'activity';
        $action = isset( $_GET['action'] ) ? $_GET['action'] : 'index';

        switch ($tab) {

            case 'activity':
                cpm_get_header( __( 'Activity', 'cpm' ), $project_id );

                $this->project_activity( $project_id );
                break;

            case 'settings':
                cpm_get_header( __( 'Settings', 'cpm' ), $project_id );

                $this->project_settings( $project_id );
                break;

            case 'message':

                switch ($action) {
                    case 'single':
                        $message_id = isset( $_GET['message_id'] ) ? intval( $_GET['message_id']) : 0;
                        $this->message_single( $project_id, $message_id );

                        break;

                    default:
                        $this->message_index( $project_id );
                        break;
                }

                break;

            case 'task':

                switch ($action) {
                    case 'single':
                        $list_id = isset( $_GET['list_id'] ) ? intval( $_GET['list_id']) : 0;

                        $this->tasklist_single( $project_id, $list_id );
                        break;

                    case 'todo':
                        $list_id = isset( $_GET['list_id'] ) ? intval( $_GET['list_id']) : 0;
                        $task_id = isset( $_GET['task_id'] ) ? intval( $_GET['task_id']) : 0;

                        $this->task_single( $project_id, $list_id, $task_id );
                        break;

                    default:
                        cpm_get_header( __( 'To-do Lists', 'cpm' ), $project_id );
                        $this->tasklist_index( $project_id );
                        break;
                }

                break;

            case 'milestone':
                $this->milestone_index( $project_id );
                break;

            case 'files':
                $this->files_index( $project_id );
                break;

            default:
                break;
        }

        do_action( 'cpmf_project_tab', $project_id, $tab, $action );

        // add the filter again
        add_filter('comments_clauses', 'cpm_hide_comments', 99);
    }

    function mytask_front_end() {
       require_once CPM_PRO_PATH . '/views/task/my-task.php';
    }

    function project_settings($project_id) {

        require_once CPM_PRO_PATH . '/views/project/settings.php';
    }

    /**
     * Display activities for a project
     *
     * @since 1.0
     * @param int $project_id
     */
    function project_activity( $project_id ) {
        $pro_obj = CPM_Project::getInstance();
        ?>
        <h3 class="cpm-nav-title">
            <?php
            _e( 'Project Activity', 'cpm' );
            
            if ( cpm_user_can_access( $project_id ) ) {
                cpm_project_actions( $project_id );
            }
            ?>
        </h3>
        <ul class="cpm-activity dash">
            <?php

            $count = get_comment_count( $project_id );
            $activities = $pro_obj->get_activity( $project_id, array() );

            if ( $activities ) {
                echo cpm_activity_html( $activities );
            }
            ?>
        </ul>

        <?php if ( $count['approved'] > count( $activities ) ) { ?>
            <a href="#" <?php cpm_data_attr( array('project_id' => $project_id, 'start' => count( $activities ) + 1, 'total' => $count['approved']) ); ?> class="button cpm-load-more"><?php _e( 'Load More...', 'cpm' ); ?></a>
        <?php } ?>

        <?php
    }

    function message_index( $project_id ) {
        require_once CPM_PATH . '/views/message/index.php';
    }

    function message_single( $project_id, $message_id ) {
        require_once CPM_PATH . '/views/message/single.php';
    }

    function tasklist_index( $project_id ) {
        require_once CPM_PATH . '/views/task/index.php';
    }

    function tasklist_single( $project_id, $tasklist_id ) {
        require_once CPM_PATH . '/views/task/single.php';
    }

    function task_single( $project_id, $tasklist_id, $task_id ) {
        require_once CPM_PATH . '/views/task/task-single.php';
    }

    function milestone_index( $project_id ) {
        require_once CPM_PATH . '/views/milestone/index.php';
    }

    function files_index( $project_id ) {
        require_once CPM_PATH . '/views/files/index.php';
    }

    /**
     * Attach fom actions in every form in frontend
     *
     * @since 1.0
     * @return void
     */
    function form_actions() {
        if ( is_admin() && ! isset( $_POST['cpmf_url'] )) {
            return;
        }

        // run `form_hidden_input`
        $form_actions = array('cpm_project_form', 'cpm_message_form', 'cpm_tasklist_form',
            'cpm_task_new_form', 'cpm_milestone_form', 'cpm_comment_form', 'cpm_project_duplicate');

        foreach ($form_actions as $action) {
            add_action( $action, array($this, 'form_hidden_input') );
        }
    }

    /**
     * Adds a hidden input on frontend forms
     *
     * This function adds a hidden permalink input in all forms in the frontend
     * to apply url filters correctly when doing ajax request.
     *
     * @since 1.0
     */
    function form_hidden_input() {

        printf( '<input type="hidden" name="cpmf_url" value="%s" />', get_permalink() );
    }

    /**
     * Check if any updates found of this plugin
     *
     * @global string $wp_version
     * @return bool
     */
    function update_check() {
        global $wp_version, $wpdb;

        require_once ABSPATH . '/wp-admin/includes/plugin.php';

        $plugin_data = get_plugin_data( __FILE__ );

        $plugin_name = $plugin_data['Name'];
        $plugin_version = $plugin_data['Version'];

        $version = get_transient( $this->plugin_slug . '_update_plugin' );
        $duration = 60 * 60 * 12; //every 12 hours

        if ( $version === false ) {

            if ( is_multisite() ) {
                $wp_install = network_site_url();
            } else {
                $wp_install = home_url( '/' );
            }

            $params = array(
                'timeout' => 20,
                'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url( '/' ),
                'body' => array(
                    'name' => $plugin_name,
                    'slug' => $this->plugin_slug,
                    'type' => 'plugin',
                    'version' => $plugin_version,
                    'site_url' => $wp_install
                )
            );

            $url = 'http://wedevs.com/?action=wedevs_update_check';
            $response = wp_remote_post( $url, $params );
            $update = wp_remote_retrieve_body( $response );

            if ( is_wp_error( $response ) || $response['response']['code'] != 200 ) {
                return false;
            }

            $json = json_decode( trim( $update ) );
            $version = array(
                'name' => isset( $json->name ) ? $json->name : '',
                'latest' => isset( $json->latest ) ? $json->name : '',
                'msg' => isset( $json->msg ) ? $json->name : '',
            );

            set_site_transient( $this->plugin_slug . '_update_plugin', $version, $duration );
        }

        if ( version_compare( $plugin_version, $version['latest'], '<' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Shows the update notification if any update founds
     */
    function update_notification() {
        $version = get_site_transient( $this->plugin_slug . '_update_plugin' );

        if ( $this->update_check() ) {
            $version = get_site_transient( $this->plugin_slug . '_update_plugin' );

            if ( current_user_can( 'update_core' ) ) {
                $msg = sprintf( __( '<strong>%s</strong> version %s is now available! %s.', 'cpmf' ), $version['name'], $version['latest'], $version['msg'] );
            } else {
                $msg = sprintf( __( '%s version %s is now available! Please notify the site administrator.', 'cpmf' ), $version['name'], $version['latest'], $version['msg'] );
            }

            echo "<div class='update-nag'>$msg</div>";
        }
    }

}
new CPM_Frontend();




