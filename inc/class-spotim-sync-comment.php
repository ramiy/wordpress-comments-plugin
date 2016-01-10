<?php

class SpotIM_Sync_Comment extends SpotIM_Sync_Comment_API {

    public $spot_id;
    public $spot_token;
    public $perpage = 5;

    public function __construct() {
        add_action('wp_ajax_spotim_sync_comment', array($this, 'handle_sync_comment'));
        add_filter('single_template', array($this, 'sync_comment_single_post'));
    }

    public function handle_sync_comment() {
        $this->spot_id = WP_SpotIM::instance()->admin->get_option('spot_id', 'sp_foo');
        $this->sync_comment();
    }

    public function sync_comment() {
        $postData = $_POST;
        $page = (isset($postData['offset']) && $postData['offset'] > 0) ? $postData['offset'] : 1;
        $offset = (($page - 1) * $this->perpage);
        $urls = $this->get_post_api_urls($this->perpage, $offset);
        $total_items = ((isset($postData['total_items']) && $postData['total_items'] != '') ? $postData['total_items'] : $this->get_post_count());
        if ($urls) {
            $this->get_comment_feed($urls, 'run_sync_comment');
            $page++;
            if (((int) ceil($total_items / $this->perpage)) < $page) {
                echo json_encode(array('hasMore' => false, 'total_items' => $total_items));
            } else {
                echo json_encode(array('hasMore' => true, 'offset' => $page, 'total_items' => $total_items));
            }
        }
        die;
    }

    private function get_comment_feed($urls, $callback, $custom_options = null) {
        $comment_success_count = 0;
        $rolling_window = count($urls);
        $master = curl_multi_init();
        $std_options = array(CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5);
        $options = ($custom_options) ? ($std_options + $custom_options) : $std_options;
        for ($i = 0; $i < $rolling_window; $i++) {
            $ch = curl_init();
            $options[CURLOPT_URL] = $urls[$i];
            curl_setopt_array($ch, $options);
            curl_multi_add_handle($master, $ch);
        }

        do {
            while (($execrun = curl_multi_exec($master, $running)) == CURLM_CALL_MULTI_PERFORM);
            if ($execrun != CURLM_OK)
                break;
            while ($done = curl_multi_info_read($master)) {
                $info = curl_getinfo($done['handle']);
                if ($info['http_code'] == 200) {
                    $output = curl_multi_getcontent($done['handle']);
                    $comment_success_count = ($comment_success_count + $this->$callback($output));
                    $ch = curl_init();
                    $options[CURLOPT_URL] = $urls[$i++];
                    curl_setopt_array($ch, $options);
                    curl_multi_add_handle($master, $ch);
                    curl_multi_remove_handle($master, $done['handle']);
                }
            }
        } while ($running);

        curl_multi_close($master);
        return $comment_success_count;
    }

    private function get_post_api_urls($perpage, $offset) {
        global $wpdb;
        $urls = array();
        $post_table = $wpdb->prefix . WP_SpotIM::instance()->admin->post_table;
        $posts = $wpdb->get_results("SELECT ID FROM $post_table WHERE post_type = 'post' AND post_status = 'publish' LIMIT $offset, $perpage");
        if ($posts) {
            foreach ($posts as $post) {
                $etag = ((($etag = get_post_meta($post->ID, 'etag', true)) != '') ? $etag : 0);
                $urls[] = "https://www.spot.im/api/open-api/v1/export/wordpress?spot_id=$this->spot_id&post_id=$post->ID&etag=$etag&count=20";
            }
        }
        return $urls;
    }

    private function get_post_count() {
        global $wpdb;
        $post_table = $wpdb->prefix . WP_SpotIM::instance()->admin->post_table;
        $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type = 'post' AND post_status = 'publish'");
        return $total_posts;
    }

    private function get_posts($perpage) {
        return get_posts(array('post_type' => 'post', 'posts_per_page' => $perpage));
    }

    public function sync_comment_single_post($single_template) {
        global $post;
        if ($post->post_type == 'post') {
            $timestamp = current_time('timestamp');
            $timestamp_current_day = date('d', $timestamp);
            $si_syn_timestamp = get_post_meta($post->ID, 'si_syn_timestamp', true);
            $si_syn_timestamp_day = ($si_syn_timestamp != '') ? date('d', $si_syn_timestamp) : '';
            if ($timestamp_current_day != $si_syn_timestamp_day) {
                $this->spot_id = WP_SpotIM::instance()->admin->get_option('spot_id', 'sp_foo');
                update_post_meta($post->ID, 'si_syn_timestamp', $timestamp);
                $etag = ((($etag = get_post_meta($post->ID, 'etag', true)) != '') ? $etag : 0);
                $urls[] = "https://www.spot.im/api/open-api/v1/export/wordpress?spot_id=$this->spot_id&post_id=$post->ID&etag=$etag&count=20";
                $this->get_comment_feed($urls, 'run_sync_comment');
            }
        }
        return $single_template;
    }

}

global $spotimSyncObj;
$spotimSyncObj = new SpotIM_Sync_Comment();
