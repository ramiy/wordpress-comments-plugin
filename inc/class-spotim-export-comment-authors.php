<?php
class SpotIM_ExportCommentAuthors {

    public function __construct() {
        add_action('plugins_loaded', array($this, 'spotim_export_comment_authors'));
    }

    public function spotim_export_comment_authors() {
        if ((isset($_REQUEST['download']) && $_REQUEST['download'] == 'comment-authors') && current_user_can('manage_options')) {
            global $wpdb;
            $comment_table = $wpdb->prefix . "comments";
            $authors = $wpdb->get_results("SELECT comment_author, comment_author_email FROM $comment_table WHERE comment_author_email != '' AND comment_author != '' AND comment_approved = '1' GROUP BY comment_author");
            $stringData = '';
            if ($authors) {
                foreach ($authors as $author) {
                    $stringData .= $author->comment_author . ',' . $author->comment_author_email . "\r\n";
                }
            }
            $file_name = "comment_authors.txt";
            header("Content-type: text/text");
            header("Content-Disposition: attachment; filename=$file_name");
            header("Pragma: no-cache");
            header("Expires: 0");
            echo $stringData;
            exit();
        }
    }
}

new SpotIM_ExportCommentAuthors();