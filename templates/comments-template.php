<div class="spot-im-comments comments-area">
    <div class="spot-im-frame-inpage"
        data-post-id="<?php echo esc_attr( apply_filters( 'spotim_comments_post_id', get_the_ID() ) ); ?>"
        data-post-url="<?php echo esc_attr( apply_filters( 'spotim_comments_post_url', get_permalink() ) ); ?>"
        data-messages-count="<?php $options = SpotIM_Options::get_instance(); echo esc_attr( $options->get( 'comments_per_page' ) ); ?>"
        data-wp-import-endpoint="<?php echo esc_url( apply_filters( 'spotim_comments_feed_link', get_post_comments_feed_link( get_the_id(), 'spotim' ) ) ); ?>"
        data-facebook-url="<?php echo esc_url( apply_filters( 'spotim_comments_facebook_url', get_permalink() ) ); ?>"
        data-disqus-url="<?php echo esc_url( apply_filters( 'spotim_comments_disqus_url', get_permalink() ) ); ?>"
        data-disqus-short-url="<?php echo esc_url( apply_filters( 'spotim_comments_disqus_short_url', esc_url( site_url( '/?p=' . get_the_id() ) ) ) ); ?>"
        data-disqus-identifier="<?php echo apply_filters( 'spotim_comments_disqus_identifier',  ( get_the_id() . ' ' . esc_url( site_url( '/?p=' . get_the_id() ) ) ) ); ?>"
        data-community-question="<?php echo esc_attr( apply_filters( 'spotim_comments_community_question',  get_post_meta( get_the_id(), 'spotim_display_question', true ) ) ); ?>">
    </div>
</div>