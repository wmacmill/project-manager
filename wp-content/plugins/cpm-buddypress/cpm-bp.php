<?php
/**
 * Plugin Name: WP Project Manager pro - BuddyPress Integration
 * Plugin URI: http://wedevs.com/plugin/wp-project-manager/
 * Description: BuddyPress integration add-on for WP Project Manager.
 * Author: weDevs Team
 * Author URI: http://weDevs.com
 * Version: 1.1.3
 * License: GPL2
 */

/**
 * Copyright (c) 2015 weDevs Team (email: info@wedevs.com). All rights reserved.
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
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

if ( is_admin() ) {
    require_once dirname( __FILE__ ) . '/lib/wedevs-updater.php';

    new WeDevs_Plugin_Update_Checker( plugin_basename( __FILE__ ) );
}

/**
 * CPM BuddyPress Integration class
 *
 * @author weDevs
 */
class CPM_BP {

    static $cpm;
    static $buddypress;

    public function __construct() {

        add_action( 'wp_enqueue_scripts', array( 'WeDevs_CPM', 'admin_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'self_scripts' ) );

        add_action( 'cpm_project_form', array( $this, 'project_form_extend' ) );
        add_filter( 'cpm_assign_user_role', array( $this, 'add_group_user' ), 10, 3 );

        add_filter( 'cpm_project_new', array( $this, 'new_project_record_group_id' ), 10, 2 );
        add_filter( 'cpm_get_projects_argument', array( $this, 'filter_project_current_group' ) );
        add_action( 'groups_member_after_save', array( $this, 'buddypress_action' ) );

        add_filter( 'cpm_project_new', array( $this, 'new_project_record_group_id' ), 10, 2 );
        add_filter( 'cpm_get_projects_argument', array( $this, 'filter_project_current_group' ) );

        add_filter( 'cpm_project_activity_count_join', array( $this, 'cpm_project_activity_count_join' ) );
        add_filter( 'cpm_project_activity_count_where', array( $this, 'cpm_project_activity_count_where' ) );
        add_filter( 'cpm_all_project_search_query_arg', array( $this, 'cpm_all_project_search_query_arg' ), 10, 2 );
        add_filter( 'cpm_pre_user_query_where', array( $this, 'cpm_pre_user_query_where' ), 10, 2 );
        add_action( 'groups_leave_group', array($this, 'after_leave_group'), 10, 2 );
        add_action( 'groups_remove_member', array( $this, 'groups_remove_member' ), 10, 2 );
    }

    /**
     * Remove group member
     *
     * @since 1.0
     *
     * @param int $group_id
     * @param int $user_id
     */
    function groups_remove_member( $group_id, $user_id ) {
        $this->band_user( $group_id, $user_id );
    }

    /**
     * active after leave group
     *
     * @since 1.0
     *
     * @param int $group_id
     * @param int $user_id
     */
    function after_leave_group( $group_id, $user_id ) {
        $this->band_user( $group_id, $user_id );
    }

    /**
     * Filter all projects search by client name
     *
     * @since 1.0
     *
     * @param array $args
     *
     * @return array
     */
    function cpm_pre_user_query_where( $where, $self ) {
        if ( ! bp_is_group() ) {
            return $where;
        }

        global $bp;

        $group_id    = $bp->groups->current_group->id;
        $projects    = $this->get_projects_from_group_id( $group_id );
        $porjects_id = wp_list_pluck( $projects->posts, 'ID' );
        $porjects_id = implode( ',', $porjects_id );
        $where       = " AND cpu.project_id IN ($porjects_id)";

        return $where;
    }

    /**
     * Filter all projects search query for bp group project
     *
     * @since 1.0
     *
     * @param array $args
     *
     * @return array
     */
    function cpm_all_project_search_query_arg( $args, $item ) {
        if ( ! bp_is_group() ) {
            return $args;
        }

        global $bp;

        $group_id = $bp->groups->current_group->id;

        $meta_arg = array(
            array(
                'key'     => '_bp_group_id',
                'value'   => $group_id,
                'compare' => '='
            )
        );

        $meta_query         = $args['meta_query'] ? array_merge( $args['meta_query'], $meta_arg ) : $meta_arg;
        $args['meta_query'] = $meta_query;

        return $args;
    }

    /**
     * Filter join query for count all, active and completed project
     *
     * @since 1.0
     *
     * @param string $join
     *
     * @return string
     */
    function cpm_project_activity_count_join( $join ) {
        if ( ! bp_is_group() ) {
            return $join;
        }

        global $wpdb;

        $join .= " LEFT JOIN {$wpdb->postmeta} as mt ON mt.post_id = post.ID";

        return $join;
    }

    /**
     * Filter conditional query for count all, active and completed project
     *
     * @since 1.0
     *
     * @param string $where
     *
     * @return string
     */
    function cpm_project_activity_count_where( $where ) {
        if ( ! bp_is_group() ) {
            return $where;
        }

        global $wpdb, $bp;

        $group_id = $bp->groups->current_group->id;
        $where .= " AND mt.meta_key = '_bp_group_id' AND mt.meta_value=$group_id";

        return $where;
    }

    /**
     * Group activity update
     *
     * @since 1.0
     *
     * @param obj $self
     *
     * @return void
     */
    function buddypress_action( $self ) {
        if ( $self->is_banned ) {
            $this->band_user( $self->group_id, $self->user->id );
        } else {
            $this->check_member_status( $self );
        }
    }

    /**
     * Check member is new or update
     *
     * @since 1.0
     *
     * @param obj $self
     *
     * @return void
     */
    function check_member_status( $self ) {

        if ( ! groups_is_user_member( $self->user_id, $self->group_id ) ) {
            return;
        }
        $projects = $this->get_projects_from_group_id( $self->group_id );

        foreach ( $projects->posts as $key => $project ) {
            $member = $this->is_member_exist( $project->ID, $self->user_id );

            if ( ! $member ) {
                $this->insert_member( $project->ID, $self->user_id, $self->is_admin );
            } else {
                $this->update_member( $project->ID, $self->user_id, $self->is_admin, $member );
            }
        }
    }

    /**
     * Update member into project
     *
     * @since 1.0
     *
     * @param int     $project_id
     * @param int     $user_id
     * @param boolean $is_admin
     * @param obj     $member
     *
     * @return void
     */
    function update_member( $project_id, $user_id, $is_admin, $member ) {

        if ( $member->role == 'manager' && $is_admin ) {
            return true;
        }

        if ( $member->role == 'client' && ! $is_admin ) {
            return true;
        }

        if ( $member->role == 'co_worker' && ! $is_admin ) {
            return true;
        }

        if ( $is_admin ) {
            CPM_Project::getInstance()->update_user_role( $project_id, $user_id, 'manager' );
        } else {
            CPM_Project::getInstance()->update_user_role( $project_id, $user_id, 'co_worker' );
        }

    }

    /**
     * New member
     *
     * @since 1.0
     *
     * @param int $project_id
     * @param int $user_id
     * @param object $self
     *
     * @return void
     */
    function insert_member( $project_id, $user_id, $is_admin ) {
        $role = $is_admin ? 'manager' : 'co_worker';

        CPM_Project::getInstance()->insert_user( $project_id, $user_id, $role );
    }

    /**
     * Is member exist
     *
     * @since 1.0
     *
     * @param int $project_id
     * @param int $user_id
     *
     * @return boolean
     */
    function is_member_exist( $project_id, $user_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'cpm_user_role';

        $result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE project_id='%d' AND user_id='%d'", $project_id, $user_id ) );

        if ( is_object( $result ) ) {
            return $result;
        }

        return false;
    }

    /**
     * Remove user from project task and subtask
     *
     * @since 1.0
     *
     * @param int $group_id
     * @param int $user_id
     *
     * @return void
     */
    function band_user( $group_id, $user_id ) {
        $projects = $this->get_projects_from_group_id( $group_id );
        $projects = $projects->posts;

        foreach ( $projects as $key => $project ) {
            $this->remove_user( $project->ID, $user_id );
            $this->ban_user_from_task( $project->ID, $user_id );
        }
    }

    /**
     * Ban user form task
     *
     * @since 1.0
     *
     * @param int $project_id
     *
     * @return void
     */
    function ban_user_from_task( $project_id, $user_id ) {
        $task_lists = CPM_Task::getInstance()->get_task_lists( $project_id );

        foreach ( $task_lists as $task_list ) {
            $tasks = CPM_Task::getInstance()->get_tasks( $task_list->ID );

            foreach ( $tasks as $task ) {
                delete_post_meta( $task->ID, '_assigned', $user_id );
                $this->ban_user_from_subtask( $task->ID, $user_id );
            }
        }
    }

    /**
     * Ban user form subtask
     *
     * @since 1.0
     *
     * @param int $task_id
     *
     * @return void
     */
    function ban_user_from_subtask( $task_id ) {
        $args      = array( 'post_parent' => $task_id, 'numberposts' => - 1, 'post_type' => 'sub_task' );
        $sub_tasks = get_children( $args );

        foreach ( $sub_tasks as $sub_task ) {
            delete_post_meta( $sub_task->ID, '_assigned', $user_id );
        }
    }

    /**
     * Get projects by group id
     *
     * @since 1.0
     *
     * @param int $group_id
     *
     * @return void
     */
    function get_projects_from_group_id( $group_id ) {
        $args = array(
            'post_type'      => 'project',
            'post_status'    => 'any',
            'posts_per_page' => '-1',
            'meta_query'     => array(
                array(
                    'key'     => '_bp_group_id',
                    'value'   => $group_id,
                    'compare' => '='
                )
            ),
        );

        return new WP_Query( $args );
    }

    /**
     * Remove member form project
     *
     * @since 1.0
     *
     * @param int $project_id
     * @param int $user_id
     *
     * @return void()
     */
    function remove_user( $project_id, $user_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'cpm_user_role';
        $wpdb->delete( $table, array( 'project_id' => $project_id, 'user_id' => $user_id ) );
    }

    /**
     * Filter project for current group
     *
     * @since 1.0
     *
     * @param array $args
     *
     * @return array
     */
    function filter_project_current_group( $args ) {
        global $bp;

        if ( ! isset( $bp->groups->current_group->id ) ) {
            return $args;
        }

        $group_id = $bp->groups->current_group->id;

        $meta_arg = array(
            array(
                'key'     => '_bp_group_id',
                'value'   => $group_id,
                'compare' => '='
            )
        );

        $meta_query         = $args['meta_query'] ? array_merge( $args['meta_query'], $meta_arg ) : $meta_arg;
        $args['meta_query'] = $meta_query;

        return $args;
    }

    /**
     * Group user insert into new projec
     *
     * @since 1.0
     *
     * @param int   $project_id
     * @param array $data
     *
     * @return void
     */
    function new_project_record_group_id( $project_id, $data ) {
        $group_id = isset( $_POST['group_id'] ) ? $_POST['group_id'] : 0;
        update_post_meta( $project_id, '_bp_group_id', $group_id );
    }

    /**
     * Group user insert into new projec
     *
     * @since 1.0
     *
     * @param array $users_role
     * @param array $posted
     * @param int   $project_id
     *
     * @return void
     */
    function add_group_user( $users_role, $posted, $project_id ) {
        if ( ! empty( $posted['project_id'] ) ) {
            return $users_role;
        }

        if ( ! $posted['cpm_bp_url'] ) {
            return $users_role;
        }

        global $bp;

        $group_id                 = isset( $posted['group_id'] ) ? $posted['group_id'] : 0;
        $groups_member            = BP_Groups_Member::get_all_for_group( $group_id );
        $groups_admin             = BP_Groups_Member::get_group_administrator_ids( $group_id );
        $current_unser_id         = get_current_user_id();

        $groups_member['members'] = isset( $groups_member['members'] ) ? $groups_member['members'] : array();
        $members                  = array();

        $groups_member['members'] = isset( $groups_member['members'] ) ? $groups_member['members'] : array();
        $members                  = array();

        foreach ( $groups_member['members'] as $key => $group_member ) {
            $members[ $group_member->user_id ] = 'co_worker';
        }

        foreach ( $groups_admin as $key => $group_admin ) {
            if ( $current_unser_id == $group_admin->user_id ) {
                continue;
            }

            $members[ $group_admin->user_id ] = 'manager';
        }

        return $members;

    }

    /**
     * Load the plugin scripts
     *
     * @since 1.0
     */
    function self_scripts() {
        wp_enqueue_script( 'cpm-bp-script', plugins_url( 'assets/js/cpmbp.js', __FILE__ ), array( 'jquery' ), false, true );
    }

    /**
     * Load the plugin scripts
     * Insert extra group id field in project create form
     *
     * @since 1.0
     */
    function project_form_extend( $project ) {

        if ( ! bp_is_group() ) {
            return;
        }

        global $bp;

        $current_group_id = $bp->groups->current_group->id;
        ?>
        <input type="hidden" name="group_id" value="<?php echo $current_group_id; ?>">
    <?php
    }

    /**
     * Load styles
     *
     * @since 1.0
     */
    function enqueue_scripts() {
        wp_enqueue_style( 'cpm-bp-style', plugins_url( 'assets/css/style.css', __FILE__ ) );
    }

    /**
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    static function notice() {

        if ( self::$cpm ) {
            echo '<div class="error">
                <p><strong>WP Project Manager PRO</strong> plugin is not installed. Install the plugin first</p>
            </div>';
        }

        if ( self::$buddypress ) {
            echo '<div class="error">
                <p><strong>BuddyPress</strong> plugin is not installed. Install the plugin first</p>
            </div>';
        }

    }
}


/**
 * Initialize the BuddyPress integration
 *
 * @return void
 */
function cpm_bp_init() {

    if ( !class_exists( 'WeDevs_CPM' ) ) {

        CPM_BP::$cpm = true;
        add_action( 'admin_notices', array( 'CPM_BP', 'notice' ) );
        return;
    }

    if ( !class_exists( 'BuddyPress' ) ) {

        CPM_BP::$buddypress = true;
        add_action( 'admin_notices', array( 'CPM_BP', 'notice' ) );
        return;
    }

    // Load the buddypress Groups integration, if active
    if ( bp_is_active( 'groups' ) ) {

        require_once dirname( __FILE__ ) . '/classes/group.php';

        bp_register_group_extension( 'CPM_BP_Group_Extension' );
    }

    new CPM_BP();
}

add_action( 'plugins_loaded', 'cpm_bp_init', 20 );

/**
 * Project slug for groups
 *
 * @return string
 */
function cpm_bp_slug_name() {
    return 'projects';
}
