<script type="text/javascript">
    // v2.1.0
    window.SPOTIM = {
        spotId: <?php echo wp_json_encode( $this->get( 'spot_id' ) ); ?>,
        parentElement: document.body,
        options: {}
    };
</script>
<script type="text/javascript" async src="//www.spot.im/launcher/bundle.js"></script>