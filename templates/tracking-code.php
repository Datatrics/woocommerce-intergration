<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<script type="text/javascript">
	var _paq = _paq || [];
<?php
$current_user = wp_get_current_user();
if ( $current_user->exists() ) {
	echo '_paq.push(["setCustomData", { "email" : "'.$current_user->user_email .'" }]);';
}
?>	
	_paq.push(["trackPageView"]);
	_paq.push(["enableLinkTracking"]);
	(function() {
		var u=(("https:" == document.location.protocol) ? "https" : "http") + "://tr.datatrics.com/";
		_paq.push(["setTrackerUrl", u]);
		_paq.push(["setProjectId", <?php echo esc_js($this->datatrics_projectid); ?>]);
		var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0];
		g.type="text/javascript";
		g.defer=true; g.async=true; g.src=u; s.parentNode.insertBefore(g,s);
	})();

</script>