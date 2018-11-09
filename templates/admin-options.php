<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<link rel="stylesheet" href="<?php echo plugin_dir_url(realpath(__DIR__)) . 'assets/css/admin.css'; ?>" type="text/css" media="all" />
<p>
	<?php echo isset( $this->method_description ) ? wpautop( $this->method_description ) : ''; ?>
</p>
<div class="clear"></div>
<h3 class="title title-custom">Setup Datatrics</h3>
<hr>
<table class="form-table">
	<?php $this->generate_settings_html( $this->form_fields ); ?>
</table>

<!-- Section -->
<div><input type="hidden" name="section" value="<?php echo $this->id; ?>"/></div>