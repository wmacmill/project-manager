;(function ($) {
	var cpm_bp = {

		init: function() {
			var cpm_form = $('.cpm-project-form'),
				group_id_field = cpm_form.find( 'input[name="group_id"]' );
			
			if ( !group_id_field.length ) {
				return;
			}
			
			cpm_form.find('.cpm-project-coworker').remove();
		},
	}
	cpm_bp.init();

})(jQuery);