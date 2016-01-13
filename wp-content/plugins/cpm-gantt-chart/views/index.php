<?php
$task_obj = CPM_Task::getInstance();

if ( cpm_user_can_access( $project_id, 'tdolist_view_private' ) ) {
    $lists = $task_obj->get_task_lists( $project_id, true );
} else {
    $lists = $task_obj->get_task_lists( $project_id );
}

cpm_get_header( __( 'Gantt Chart', 'cpm-gantt' ), $project_id );

if ( $lists ) {

    foreach ($lists as $list) {
        $list_url = cpm_url_single_tasklist( $project_id, $list->ID );
        $start_date = get_post_meta( $list->ID, '_start', true );

        $end_date = get_post_meta( $list->ID, '_due', true );
        $start_date = ( empty( $start_date ) && empty( $end_date ) ) ? $list->post_date : $start_date;
        $start_date = empty( $start_date ) ? $end_date : $start_date;

        $list_links = get_post_meta( $list->ID, '_link', true );

        if ( is_array( $list_links ) ) {
            foreach ( $list_links as $list_link_id ) {
                if ( 'publish' == get_post_status ( $list_link_id ) ) {
                    $link_chart[] = array(
                        'source' => $list->ID,
                        'target' => $list_link_id,
                        'type' => 1
                    );
                }
            }
        }

        $tasks = $task_obj->get_tasks_by_access_role( $list->ID , $project_id );

        $tasks = cpm_tasks_filter( $tasks );

        if ( count( $tasks['pending'] ) ) {
            $lists_chart[] = array(
                'id'        => $list->ID,
                'text'      => '<a href="'.$list_url.'">'.$list->post_title.'</a>',
                'open'      => true,
                'task_list' => true
            );
        } else {
            $list_duration = gant_date_duration( $start_date, $end_date );
            $list_duration = ( $list_duration < 0 ) ? 1 : $list_duration;
            $lists_chart[] = array(
                'id'         => $list->ID,
                'text'       => '<a href="'.$list_url.'">'.$list->post_title.'</a>',
                'open'       => true,
                'task_list'  => true,
                'start_date' => date( 'd-m-Y', strtotime( $start_date ) ),
                'duration'   => $list_duration,
            );
        }

        if ( count( $tasks['pending'] ) ) {
            foreach ($tasks['pending'] as $task) {
                $task_url        = cpm_url_single_task( $project_id, $list->ID, $task->ID );
                $task_start_date = get_post_meta( $task->ID, '_start', true );
                $end_date        = get_post_meta( $task->ID, '_due', true );
                $task_start_date = ( empty( $task_start_date ) && empty( $end_date ) ) ? $task->post_date : $task_start_date;
                $task_start_date = empty( $task_start_date ) ? $end_date : $task_start_date;


                $task_links = get_post_meta( $task->ID, '_link', true );

                if ( empty( $end_date ) ) {
                    $duration = 1;
                } else {
                    $duration = gant_date_duration( $task_start_date, $end_date );
                    $duration = ( $duration < 0 ) ? 1 : $duration;
                }

                $assigend = get_post_meta( $task->ID, '_assigned', true );
                $avatar[] =  array( 'task_id' => $task->ID, 'avatar' => get_avatar($assigend, 16) );
                $tasks_chart[] = array(
                    'id'         => $task->ID,
                    'text'       => '<a href="'.$task_url.'">'.$task->post_title.'</a>',
                    'start_date' => date( 'd-m-Y', strtotime( $task_start_date ) ),
                    'duration'   => $duration,
                    'parent'     => $list->ID,
                    'progress'   => round(get_post_meta( $task->ID, '_completed', true ), 2 ),
                    'owner'      => $list->ID,
                );

                $link_chart[] = array(
                    'source' => $list->ID,
                    'target' => $task->ID,
                    'type' => 2
                );

                if ( is_array( $task_links ) ) {
                    foreach ( $task_links as $task_link_id ) {
                        if ( 'publish' == get_post_status ( $task_link_id ) ) {
                            $link_chart[] = array(
                                'source' => $task->ID,
                                'target' => $task_link_id,
                                'type'   => 1
                            );
                        }
                    }
                }
            }
        }
    }
}
$users = CPM_Project::getInstance()->get_users( $project_id );

$link_chart = isset( $link_chart ) ? $link_chart : array();
$tasks_chart = isset( $tasks_chart ) ? $tasks_chart : array();
$lists_chart = isset( $lists_chart ) ? $lists_chart : array();
$avatar = isset( $avatar ) ? $avatar : array();

foreach ( $link_chart as $key => $link_chart_val ) {
    $link_chart[$key]['id'] = $key;
}
$assign[] = array(
    'key' => '-1',
    'label' => __( '--Select--', 'cpm-gantt' )
);

foreach ( $users as $key => $user ) {
    $assign[] = array(
        'key' => $user['id'],
        'label' => $user['name']
    );
}

$link = json_encode( $link_chart );
$todo = json_encode( $lists_chart );
$task = json_encode( $tasks_chart );
$assigned_user = json_encode( array_values( $assign ) );
$avatars = json_encode( $avatar );

?>
<style>
.gantt_task_progress{
    text-align:left;
    padding-left:10px;
    box-sizing: border-box;
    color:white;
    font-weight: bold;
}
.gantt-wrap .gantt_task a {
    color: white;
}
.gantt_add {
    content: ' ';
    height: 20px;
    width: 20px;
}
.gantt-wrap .avatar {
    margin: 0 10px 0 0  ;
}
</style>
<div class="gantt-wrap" style="width:100%; height:400px; margin-top: 10px;"></div>

<script>
    window.gantt_link = <?php echo $link; ?>;
    window.gantt_todo = <?php echo $todo; ?>;
    window.gantt_task = <?php echo $task; ?>;
    window.assigned_user = <?php echo $assigned_user; ?>;
    window.avatars = <?php echo $avatars; ?>;

</script>
