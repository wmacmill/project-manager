<?php

/**
 * BuddyPress Groups integration functions
 */


/**
 * Implementation of BP_Group_Extension
 *
 * @since 0.1
 */
if ( class_exists( 'BP_Group_Extension' ) ) :

    class CPM_BP_Group_Extension extends BP_Group_Extension {

        private $parent_path;

        /**
         * Constructor
         * @since 0.1
         */
        function __construct() {

            $this->parent_path = CPM_PATH;
    
            $this->name = __( 'Projects', 'cpmbp' );
            $this->slug = sanitize_title( __( cpm_bp_slug_name(), 'cpmbp' ) );

            $this->includes();
            $this->form_actions();

        }

        /**
         * Attach fom actions in every form in frontend
         *
         * @since 1.0
         * @return void
         */
        function form_actions() {
            if ( ! bp_is_group()  ) {
                return;
            }
            if ( is_admin() && ! isset( $_POST['cpm_bp_url'] ) ) {
                return;
            }

            // run `form_hidden_input`
            $form_actions = array(
                'cpm_project_form',
                'cpm_message_form',
                'cpm_tasklist_form',
                'cpm_task_new_form',
                'cpm_milestone_form',
                'cpm_comment_form',
                'cpm_project_duplicate'
            );

            foreach ( $form_actions as $action ) {
                add_action( $action, array( $this, 'form_hidden_input' ) );
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

            if ( isset( $_POST['cpm_bp_url'] ) && ! empty( $_POST['cpm_bp_url'] ) ) {
                $url = $_POST['cpm_bp_url'];
            } else {
                $url = bp_get_group_permalink( groups_get_current_group() );
            }

            printf( '<input type="hidden" name="cpm_bp_url" value="%s" />', $url );
        }

        /**
         * Includes all required files if the parent plugin is intalled
         *
         * @since 1.0
         */
        function includes() {

            if ( ! is_admin() ) {

                require_once $this->parent_path . '/includes/functions.php';
                require_once $this->parent_path . '/includes/urls.php';
                require_once $this->parent_path . '/includes/html.php';
                require_once $this->parent_path . '/includes/shortcodes.php';
            }

            $base_url = isset( $_REQUEST['cpm_bp_url'] ) ? $_REQUEST['cpm_bp_url'] : bp_get_group_permalink( groups_get_current_group() );

            // load url filters
            if ( bp_is_group() && bp_is_active( 'groups' ) ) {
                require_once dirname( __FILE__ ) . '/urls.php';
                new CPM_BP_Frontend_URLs( $base_url, $this->slug );

            } 

            if ( isset( $_REQUEST['cpm_bp_url'] ) ) {
                require_once dirname( __FILE__ ) . '/urls.php';
                new CPM_BP_Frontend_URLs( $base_url, $this->slug );
            }

        }

        /**
         * Loads the content of the tab
         *
         * This function does a few things. First, it loads the subnav, which is visible on every
         * CP BP subtab. Then, it decides which template should be loaded, based on the current
         * view (determined by the URL). It then checks to see whether the template in question
         * has been overridden in the active theme or its parent, using locate_template(). Finally,
         * the proper template is loaded.
         *
         * @package    CollabPress
         * @subpackage CP BP
         * @since      1.2
         */
        function display( $group_id = NULL ) {

            if ( ! class_exists( 'WeDevs_CPM' ) ) {
                return __( 'Sorry, main plugin is not installed', 'cpmf' );
            }

            if ( ! is_user_logged_in() ) {
                return wp_login_form( array( 'echo' => false ) );
            }

            if ( ! is_user_logged_in() ) {
                return wp_login_form( array( 'echo' => false ) );
            }

            if ( ! groups_is_user_member( get_current_user_id(), $group_id )) {
                echo '<div id="message" class="info"><p>';
                _e( 'Only group members are authorized to access this page.', 'cpmf' );
                echo '</p></div>';
                return;
            }

            $project_id = isset( $_GET['project_id'] ) ? intval( $_GET['project_id'] ) : 0;
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
            $limit          = 10;
            $pagenum        = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;

            unset( $projects['total_projects'] );
            $status_class = isset( $_GET['status'] ) ? $_GET['status'] : 'active';

            if ( function_exists( 'cpm_project_count' ) ) {
                $count = cpm_project_count();
            }
            ?>

            <div class="icon32" id="icon-themes"><br></div>
            <h2><?php _e( 'Project Manager', 'cpm' ); ?></h2>

            <?php
            if ( function_exists( 'cpm_project_filters' ) ) {
                cpm_project_filters();
            }
            ?>

            <div class="cpm-projects">

                <?php if ( function_exists( 'cpm_project_filters' ) ) { ?>
                    <ul class="list-inline order-statuses-filter">
                        <li<?php echo $status_class == 'all' ? ' class="active"' : ''; ?>>
                            <a href="<?php echo cpm_url_all(); ?>"><?php _e( 'All', 'cpm' ); ?></a>
                        </li>
                        <li<?php echo $status_class == 'active' ? ' class="active"' : ''; ?>>
                            <a class="cpm-active"
                               href="<?php echo cpm_url_active(); ?>"><?php printf( __( 'Active (%d)', 'cpm' ), $count['active'] ); ?></a>
                        </li>
                        <li<?php echo $status_class == 'archive' ? ' class="active"' : ''; ?>>
                            <a class="cpm-archive-head"
                               href="<?php echo cpm_url_archive(); ?>"><?php printf( __( 'Completed (%d)', 'cpm' ), $count['archive'] ); ?></a>
                        </li>
                    </ul>
                <?php } ?>

                <?php if ( cpm_manage_capability( 'project_create_role' ) ) { ?>
                    <nav class="cpm-new-project">
                        <a href="#" id="cpm-create-project"><span><?php _e( 'New Project', 'cpm' ); ?></span></a>
                    </nav>
                <?php } ?>

                <?php
                foreach ( $projects as $project ) {

                    if ( ! $project_obj->has_permission( $project ) ) {
                        continue;
                    }
                    ?>
                    <article class="cpm-project">
                        <?php if ( cpm_is_project_archived( $project->ID ) ) { ?>
                            <div class="cpm-completed-wrap">
                                <div class="ribbon-green"><?php _e( 'Completed', 'cpm' ); ?></div>
                            </div>
                        <?php } ?>

                        <a title="<?php echo get_the_title( $project->ID ); ?>" href="<?php echo cpm_url_project_details( $project->ID ); ?>">
                            
                            <h5><?php echo cpm_excerpt( get_the_title( $project->ID ), 30 ); ?></h5>

                            <div
                                class="cpm-project-detail"><?php echo cpm_excerpt( $project->post_content, 55 ); ?></div>
                            <div class="cpm-project-meta">
                                <?php echo cpm_project_summary( $project->info ); ?>
                            </div>

                            <footer class="cpm-project-people">
                                <div class="cpm-scroll">
                                    <?php

                                    if ( count( $project->users ) ) {
                                        foreach ( $project->users as $id => $user_meta ) {
                                            echo get_avatar( $id, 48, '', $user_meta['name'] );
                                        }
                                    }


                                    ?>
                                </div>
                            </footer>
                        </a>

                        <?php
                        $progress = $project_obj->get_progress_by_tasks( $project->ID );
                        echo cpm_task_completeness( $progress['total'], $progress['completed'] );

                        if ( cpm_user_can_access( $project->ID ) ) {
                            cpm_project_actions( $project->ID );
                        }
                        ?>


                    </article>

                <?php }
                cpm_pagination( $total_projects, $limit, $pagenum );
                ?>

            </div>
            <?php if ( cpm_manage_capability( 'project_create_role' ) ) { ?>
                <div id="cpm-project-dialog" title="<?php _e( 'Start a new project', 'cpm' ); ?>" style="display: none;">
                    <?php cpm_project_form(); ?>
                </div>

                <div id="cpm-create-user-wrap" title="<?php _e( 'Create a new user', 'cpm' ); ?>">
                    <?php cpm_user_create_form(); ?>
                </div>


                <script type="text/javascript">
                    jQuery(function ($) {
                        $("#cpm-project-dialog, #cpm-create-user-wrap").dialog({
                            autoOpen: false,
                            modal: true,
                            dialogClass: 'cpm-ui-dialog',
                            width: 485,
                            height: 430,
                            position: ['middle', 100],
                            zIndex: 9999,
                        });
                    });

                    jQuery(function ($) {
                        $("#cpm-create-user-wrap").dialog({
                            autoOpen: false,
                            modal: true,
                            dialogClass: 'cpm-ui-dialog cpm-user-ui-dialog',
                            width: 400,
                            height: 'auto',
                            position: ['middle', 100],
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
         *
         * @param int $project_id
         */
        function single_project( $project_id ) {
            remove_filter( 'comments_clauses', 'cpm_hide_comments', 99 );

            $pro_obj    = CPM_Project::getInstance();
            $activities = $pro_obj->get_activity( $project_id, array() );

            $tab    = isset( $_GET['tab'] ) ? $_GET['tab'] : 'activity';
            $action = isset( $_GET['action'] ) ? $_GET['action'] : 'index';

            switch ( $tab ) {

                case 'activity':
                    cpm_get_header( __( 'Activity', 'cpm' ), $project_id );

                    $this->project_activity( $project_id );
                    break;

                case 'settings':
                    cpm_get_header( __( 'Settings', 'cpm' ), $project_id );

                    $this->project_settings( $project_id );
                    break;

                case 'message':

                    switch ( $action ) {
                        case 'single':
                            $message_id = isset( $_GET['message_id'] ) ? intval( $_GET['message_id'] ) : 0;
                            $this->message_single( $project_id, $message_id );

                            break;
                        default:
                            $this->message_index( $project_id );
                            break;
                    }

                    break;

                case 'task':

                    switch ( $action ) {
                        case 'single':
                            $list_id = isset( $_GET['list_id'] ) ? intval( $_GET['list_id'] ) : 0;

                            $this->tasklist_single( $project_id, $list_id );
                            break;

                        case 'todo':
                            $list_id = isset( $_GET['list_id'] ) ? intval( $_GET['list_id'] ) : 0;
                            $task_id = isset( $_GET['task_id'] ) ? intval( $_GET['task_id'] ) : 0;

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
            add_filter( 'comments_clauses', 'cpm_hide_comments', 99 );
        }

        function message_index( $project_id ) {
            require_once $this->parent_path . '/views/message/index.php';
        }

        function message_single( $project_id, $message_id ) {
            require_once $this->parent_path . '/views/message/single.php';
        }

        function tasklist_index( $project_id ) {
            require_once $this->parent_path . '/views/task/index.php';
        }

        function tasklist_single( $project_id, $tasklist_id ) {
            require_once $this->parent_path . '/views/task/single.php';
        }

        function task_single( $project_id, $tasklist_id, $task_id ) {
            require_once $this->parent_path . '/views/task/task-single.php';
        }

        function milestone_index( $project_id ) {
            require_once $this->parent_path . '/views/milestone/index.php';
        }

        function files_index( $project_id ) {
            require_once $this->parent_path . '/views/files/index.php';
        }

        function project_settings( $project_id ) {
            $file = CPM_PRO_PATH. '/views/project/settings.php';
          
            if ( file_exists( $file ) ) {
                include_once $file;
            } else {
                _e( 'Settings file does not exist', 'cpm' );
            }   
        }

        /**
         * Display activities for a project
         *
         * @since 1.0
         *
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

                $count      = get_comment_count( $project_id );
                $activities = $pro_obj->get_activity( $project_id, array() );

                if ( $activities ) {
                    echo cpm_activity_html( $activities );
                }
                ?>
            </ul>

            <?php if ( $count['approved'] > count( $activities ) ) { ?>
                <a href="#" <?php cpm_data_attr( array(
                    'project_id' => $project_id,
                    'start'      => count( $activities ) + 1,
                    'total'      => $count['approved']
                ) ); ?> class="button cpm-load-more"><?php _e( 'Load More...', 'cpm' ); ?></a>
            <?php } ?>

        <?php
        }

    }

endif;
