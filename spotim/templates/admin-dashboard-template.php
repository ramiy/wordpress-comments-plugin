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
	<iframe id="spotim-dashboard" src="https://www.spot.im/?secret=<?php echo $this->get('plugin_secret') ?>" width="100%" height="640px"></iframe>
</div>