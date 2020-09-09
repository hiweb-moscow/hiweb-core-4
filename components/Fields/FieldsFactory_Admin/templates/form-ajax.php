<?php
	
	$ajax_tags = get_array( [
		'data-fields-query-id' => md5( json_encode( $field_query ) ),
		'data-fields-query' => $field_query,
		'data-scripts-done' => wp_scripts()->done,
		'data-form-options' => $form_options
	] );

?>
<div class="hiweb-components-form-ajax-wrap preloading" <?= $ajax_tags->get_param_html_tags() ?>>
	<div class="hiweb-components-form-ajax-inner"></div>
</div>