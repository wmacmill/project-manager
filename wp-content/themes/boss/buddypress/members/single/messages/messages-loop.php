<?php do_action( 'bp_before_member_messages_loop' ); ?>

<?php if ( bp_has_message_threads( bp_ajax_querystring( 'messages' ) ) ) : ?>
    
	<?php do_action( 'bp_before_member_messages_threads' ); ?>

	<form action="<?php echo bp_loggedin_user_domain() . bp_get_messages_slug() . '/' . bp_current_action() ?>/bulk-manage/" method="post" id="messages-bulk-management">
       
        <div class="messages-options-nav">
            <?php bp_messages_bulk_management_dropdown(); ?>
        </div><!-- .messages-options-nav -->
            <div id="messages-table-wrap">        
                    <table id="message-threads" class="messages-notices messages-table">
            
                        <thead>
                            <tr>
                                <th scope="col" class="thread-checkbox"><label class="bp-screen-reader-text" for="select-all-messages"><?php _e( 'Select all', 'boss' ); ?></label><input id="select-all-messages" type="checkbox"><strong></strong></th>
                                <th scope="col" class="thread-from"><?php _e( 'From', 'boss' ); ?></th>
                                <th scope="col" class="thread-info"><?php _e( 'Subject', 'boss' ); ?></th>
                                
                                <?php do_action( 'bp_messages_inbox_list_header' ); ?>

                                <th scope="col" class="thread-options"><?php _e( 'Actions', 'boss' ); ?></th>
                            </tr>
                        </thead>
            
                        <tbody>
            
                            <?php while ( bp_message_threads() ) : bp_message_thread(); ?>
            
                                <tr id="m-<?php bp_message_thread_id(); ?>" class="<?php bp_message_css_class(); ?><?php if ( bp_message_thread_has_unread() ) : ?> unread<?php else: ?> read<?php endif; ?>">
                                    <td class="thread-avatar">
                                        <input type="checkbox" name="message_ids[]" class="message-check" value="<?php bp_message_thread_id(); ?>" />
                                        <span>
                                        <?php bp_message_thread_avatar( array( 'width' => 70, 'height' => 70 ) ); ?>
                                        </span>
                                    </td>
            
                                    <?php if ( 'sentbox' != bp_current_action() ) : ?>
                                        <td class="thread-from">
                                            <span class="from"><?php _e( 'From:', 'boss' ); ?></span> <?php bp_message_thread_from(); ?>
                                            <div class="activity"><?php bp_message_thread_last_post_date(); ?></div>
                                        </td>
                                    <?php else: ?>
                                        <td class="thread-from">
                                            <span class="to"><?php _e( 'To:', 'boss' ); ?></span> <?php bp_message_thread_to(); ?>
                                            <div class="activity"><?php bp_message_thread_last_post_date(); ?></div>
                                        </td>
                                    <?php endif; ?>
            
                                    <td class="thread-info">
                                        <p><a href="<?php bp_message_thread_view_link(); ?>" title="<?php esc_attr_e( "View Message", "boss" ); ?>"><?php bp_message_thread_subject(); ?></a></p>
                                        <p class="thread-excerpt"><?php bp_message_thread_excerpt(); ?></p>
                                    </td>
            
                                    <?php do_action( 'bp_messages_inbox_list_item' ); ?>

                                    <td class="thread-options">
                                        <?php if ( bp_message_thread_has_unread() ) : ?>
                                            <a class="read" href="<?php bp_the_message_thread_mark_read_url();?>"><?php _e( 'Read', 'boss' ); ?></a>
                                        <?php else : ?>
                                            <a class="unread" href="<?php bp_the_message_thread_mark_unread_url();?>"><?php _e( 'Unread', 'boss' ); ?></a>
                                        <?php endif; ?>
                                        
                                        <a class="delete" href="<?php bp_message_thread_delete_link(); ?>"><?php _e( 'Delete', 'boss' ); ?></a>
                                        
                                        <?php if ( bp_is_active( 'messages', 'star' ) ) : ?>
                                            <div class="thread-star">
                                                <?php if ( function_exists('bp_the_message_star_action_link') ) { bp_the_message_star_action_link( array( 'thread_id' => bp_get_message_thread_id() ) ); } ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
            
                            <?php endwhile; ?>
            
                        </tbody>
            
                    </table><!-- #message-threads -->
            </div>
            <!-- /#massages-table-wrap -->

		<?php wp_nonce_field( 'messages_bulk_nonce', 'messages_bulk_nonce' ); ?>
	</form>

	<?php do_action( 'bp_after_member_messages_threads' ); ?>
	
    <div class="pagination no-ajax" id="user-pag">

		<div class="pag-count" id="messages-dir-count">
			<?php bp_messages_pagination_count(); ?>
		</div>

		<div class="pagination-links" id="messages-dir-pag">
			<?php bp_messages_pagination(); ?>
		</div>

	</div><!-- .pagination -->

	<?php do_action( 'bp_after_member_messages_pagination' ); ?>

	<?php do_action( 'bp_after_member_messages_options' ); ?>

<?php else: ?>

	<div id="message" class="info">
		<p><?php _e( 'Sorry, no messages were found.', 'boss' ); ?></p>
	</div>

<?php endif;?>

<?php do_action( 'bp_after_member_messages_loop' ); ?>
