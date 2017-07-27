<?php
$options = SpotIM_Options::get_instance();
switch( $options->get( 'disqus_identifier' ) ) {
    case 'id':
        $disqus_identifier = get_the_id();
        break;
    case 'short_url':
        $disqus_identifier = esc_url( site_url( '/?p=' . get_the_id() ) );
        break;
    case 'id_short_url':
    default:
        $disqus_identifier = get_the_id() . ' ' . esc_url( site_url( '/?p=' . get_the_id() ) );
}
?>
<div class="spot-im-comments <?php echo esc_attr( apply_filters( 'spotim_comments_class', $options->get( 'class' ) ) ); ?>">
    <div class="spot-im-frame-inpage"
        data-post-id="<?php echo esc_attr( apply_filters( 'spotim_comments_post_id', get_the_ID() ) ); ?>"
        data-post-url="<?php echo esc_url( apply_filters( 'spotim_comments_post_url', get_permalink() ) ); ?>"
        data-short-url="<?php echo esc_url( apply_filters( 'spotim_comments_disqus_short_url', site_url( '/?p=' . get_the_id() ) ) ); ?>"
        data-messages-count="<?php echo esc_attr( apply_filters( 'spotim_comments_messages_count', $options->get( 'comments_per_page' ) ) ); ?>"
        data-wp-import-endpoint="<?php echo esc_url( apply_filters( 'spotim_comments_feed_link', get_post_comments_feed_link( get_the_id(), 'spotim' ) ) ); ?>"
        data-facebook-url="<?php echo esc_url( apply_filters( 'spotim_comments_facebook_url', get_permalink() ) ); ?>"
		data-disqus-shortname="<?php echo apply_filters( 'spotim_comments_disqus_shortname', $options->get( 'disqus_shortname' ) ); ?>"
		data-disqus-url="<?php echo esc_url( apply_filters( 'spotim_comments_disqus_url', get_permalink() ) ); ?>"
        data-disqus-identifier="<?php echo apply_filters( 'spotim_comments_disqus_identifier', $disqus_identifier ); ?>"
        data-community-question="<?php echo esc_attr( apply_filters( 'spotim_comments_community_question',  get_post_meta( get_the_id(), 'spotim_display_question', true ) ) ); ?>"
        data-seo-enabled="<?php echo apply_filters( 'spotim_comments_seo_enabled', $options->get( 'enable_seo' ) ); ?>"
        >
    </div>
</div>
