<?php
//return; 
		 if ( !class_exists('WeDevs_CPM') ) {
            return __( 'Sorry, main plugin is not installed', 'cpmf');
        }

        if ( !is_user_logged_in() ) {
            return wp_login_form( array('echo' => false) );
        }

        if ( !is_user_logged_in() ) {
            return wp_login_form( array('echo' => false) );
        }

        if ( $id ) {
            $project_id = $id;
        } else {
            $project_id = isset( $_GET['project_id'] ) ? intval( $_GET['project_id'] ) : 0;
        }

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