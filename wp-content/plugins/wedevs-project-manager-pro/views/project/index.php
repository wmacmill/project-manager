<?php
$project_obj        = CPM_Project::getInstance();
$projects           = $project_obj->get_projects();
$total_projects     = $projects['total_projects'];
$pagenum            = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
$db_limit           = intval( cpm_get_option( 'pagination' ) );
$limit              = $db_limit ? $db_limit : 10;
$status_class       = isset( $_GET['status'] ) ? $_GET['status'] : 'active';
$count              = cpm_project_count();
$can_create_project = cpm_manage_capability( 'project_create_role' );
$class              = $can_create_project ? '' : ' cpm-no-nav';

unset( $projects['total_projects'] );
?>

<h2><?php _e( 'Project Manager', 'cpm' ); ?></h2>

<div class="cpm-projects<?php echo $class; ?>">

    <div class="cpm-project-filter">
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

        <?php
            $category   = isset( $_GET['project_cat'] ) ? $_GET['project_cat'] : '';
            $status     = isset( $_GET['project_status'] ) ? $_GET['project_status'] : '';
            $action     = isset( $_GET['status'] ) ? $_GET['status'] : '';
            $searchitem = isset( $_GET['searchitem'] ) ? $_GET['searchitem'] : '';
            $page_id    = ( !is_admin() ) ? get_the_ID() : '';
        ?>

        <form action="" method="get" class="cpm-project-filters" id="cpm-project-filters">
            <?php echo cpm_filter_category( $category ); ?>
            <input type="hidden" name="p" value="<?php echo $page_id; ?>" />
            <input type="hidden" name="status" value="<?php echo $action; ?>" />
            <input type="hidden" name="page" value="cpm_projects" />
            <input type="submit" name="submit" id="project-filter-submit" class="button" value="<?php esc_attr_e( 'Filter', 'cpm' ); ?>">
        </form>

        <?php do_action( 'cpm_filter_project', $projects );  ?>
    </div>

    <?php if ( $can_create_project ) { ?>
        <nav class="cpm-new-project">
            <a href="#" id="cpm-create-project"><span><?php _e( 'New Project', 'cpm' ); ?></span></a>
        </nav>
    <?php } ?>

    <?php if ( $projects ) { ?>

        <?php foreach ($projects as $project) { ?>
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
                            if ( count( $project->users ) ) {
                                foreach ($project->users as $id => $user_meta) {

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

        <?php } ?>

        <?php cpm_pagination( $total_projects, $limit, $pagenum ); ?>

    <?php } else { ?>

        <h3><?php _e( 'No projects found!', 'cpm' ); ?></h3>

    <?php } ?>

</div>

<?php if ( $can_create_project ) { ?>

    <div id="cpm-project-dialog" style="display:none; z-index:999;" title="<?php _e( 'Start a new project', 'cpm' ); ?>">
        <?php cpm_project_form(); ?>
    </div>

    <div id="cpm-create-user-wrap" title="<?php _e( 'Create a new user', 'cpm' ); ?>">
        <?php cpm_user_create_form(); ?>
    </div>

    <script type="text/javascript">
        jQuery(function($) {
            $( "#cpm-project-dialog" ).dialog({
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
<?php } ?>