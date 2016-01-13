<?php
if ( $completed_status == 1 ) {
    $ul_class = 'cpmst-complete-status';
} else {
    $ul_class = 'cpmst-uncomplete-status';
}
?>
<ul class="cpm-todos <?php echo $ul_class; ?> cpm-sub-task">
    <li><h3><?php _e( 'Sub Task', 'cpmst' ); ?></h3></li>
    <?php
    $child_tasks = $this->get_tasks( $parent_task_id );

    $child_tasks = cpm_tasks_filter( $child_tasks );

    if ( $child_tasks['pending'] ) {
        foreach ($child_tasks['pending'] as $child_task) {
            ?>
            <li>
                <?php echo $this->cpm_task_html( $child_task, $project_id, $parent_task_id, false, $completed_status ); ?>
            </li>
            <?php
        }
    }
    ?>
</ul>
<ul class="cpm-todo-completed cpm-sub-task"> 
    <?php
    if ( $child_tasks['completed'] ) {
        foreach ($child_tasks['completed'] as $child_task) {
            ?>
            <li>
                <?php echo $this->cpm_task_html( $child_task, $project_id, $parent_task_id, false, $completed_status ); ?>
            </li>
            <?php
        }
    }
    ?>
</ul>
<?php
if ( $completed_status == 0 ) {
    ?>
    <ul class="cpm-todos-new cpmst-todolist">
        <?php if ( cpm_user_can_access( $project_id, 'create_todo' ) ) {
            ?>
            <li class="cpm-new-btn">
                <a href="#" class="cpm-btn cpm-add-subtask add-task button-primary"><?php _e( 'Add Sub Task', 'cpm' ); ?></a>
            </li>
        <?php } ?>

        <li class="cpm-todo-form cpm-todos-new cpm-hide">
            <?php $this->cpm_task_new_form( $parent_task_id, $project_id ); ?>
        </li>

    </ul> 
    <?php
}
?>





