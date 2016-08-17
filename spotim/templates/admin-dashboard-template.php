<div class="wrap">
	<div id="icon-themes" class="icon32"></div>
	<h2 class="spotim-page-title">
		<?php esc_html_e( 'Spot.IM Dashboard', 'wp-spotim' ); ?>
	</h2>
	<?php
		if ($this->get('display_welcome') === 'display') {
			SpotIM_Options::get_instance()->update('display_welcome', '');
			$this->require_welcome('index.php');
		}
	?>
	<iframe id="spotim-dashboard" src="<?php echo SPOT_IM_REST_API ?>host-panel-login-by-plugin-secret?plugin_secret=<?php echo $this->get('plugin_secret') ?>"
			style="width:100%; height:300px; background:no-repeat center 30px url(<?php echo admin_url('images/wpspin_light-2x.gif') ?>)"></iframe>
	<script>
		function getStyle(id,styleProp) {
			var elem = document.getElementById(id);
			if (elem.currentStyle)
				return elem.currentStyle[styleProp];
			else if (window.getComputedStyle)
				return document.defaultView.getComputedStyle(elem,null).getPropertyValue(styleProp);
			return undefined;
		}
		window.onload = function() {
			var dashboard = document.getElementById('spotim-dashboard');
			var dashboardHeight = document.body.scrollHeight -
				dashboard.getBoundingClientRect().top -
				parseInt(getStyle('wpbody-content', 'padding-bottom'));
			dashboard.style.height = parseInt(dashboardHeight)+'px';
		};
	</script>
</div>