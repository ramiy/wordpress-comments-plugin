<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SPOTIM_API_URL', 'https://www.spot.im/api/open-api/v1/' );
define( 'SPOTIM_EXPORT_URL', SPOTIM_API_URL . 'export/wordpress' );

/**
 * SpotIM_Import
 *
 * Plugin import class.
 *
 * @since 3.0.0
 */
class SpotIM_Import {

    /**
     * Options
     *
     * @since 3.0.0
     *
     * @access private
     *
     * @var SpotIM_Options
     */
    private $options;

    /**
     * Posts Per Request
     *
     * @since 3.0.0
     *
     * @access private
     *
     * @var int
     */
    private $posts_per_request;

    /**
     * Page Number
     *
     * @since 3.0.0
     *
     * @access private
     *
     * @var int
     */
    private $page_number;

    /**
     * Constructor
     *
     * Get things started.
     *
     * @since 3.0.0
     *
     * @access public
     *
     * @param SpotIM_Options $options Plugin options.
     */
    public function __construct( $options ) {

        $this->options = $options;

        // Set default values - if not defined by the user in the settings page
        $this->posts_per_request = 50;
        $this->page_number = 0;

    }

    /**
     * Start
     *
     * Start the import.
     *
     * @since 3.0.0
     *
     * @access public
     *
     * @param int $spot_id Sport ID.
     * @param string $import_token Import token,
     * @param int $page_number Page number.
     * @param int $posts_per_request Posts Per Request.
     *
     * @return void
     */
    public function start( $spot_id, $import_token, $page_number = 0, $posts_per_request = 1 ) {

        // save spot_id and import_token in plugin's options meta
        $this->options->update( 'spot_id', $spot_id );
        $this->options->update( 'import_token', $import_token );

        $this->page_number = $this->options->update(
            'page_number', absint( $page_number )
        );

        $this->posts_per_request = $this->options->update(
            'posts_per_request', absint( $posts_per_request )
        );

        $post_ids = $this->get_post_ids( $this->posts_per_request, $this->page_number );

        // fetch, merge comments and return a response
        $this->pull_comments( $post_ids );

        // return a response to client via json
        $this->finish();
    }

    /**
     * Pull Comments
     *
     * Import comments from Spot.IM and merge them.
     *
     * @since 3.0.0
     *
     * @access private
     *
     * @param array $post_ids An array of post IDs.
     *
     * @return void
     */
    private function pull_comments( $post_ids = array() ) {
        if ( ! empty( $post_ids ) ) {
            // import comments data from Spot.IM
            $streams = array();
            $streams = $this->fetch_comments( $post_ids );

            // sync comments data with wordpress comments
            $this->merge_comments( $streams );
        }
    }

    /**
     * Fetch Comments
     *
     * Import comments from Spot.IM.
     *
     * @since 3.0.0
     *
     * @access private
     *
     * @param array $post_ids An array of post IDs.
     *
     * @return array $streams An array of streams.
     */
    private function fetch_comments( $post_ids = array() ) {
        $streams = array();

        while ( ! empty( $post_ids ) ) {
            $post_id = array_shift( $post_ids );
            $post_etag = get_post_meta( $post_id, 'spotim_etag', true );

            $stream = $this->request( array(
                'spot_id' => $this->options->get( 'spot_id' ),
                'post_id' => $post_id,
                'etag' => absint( $post_etag ),
                'count' => 1000,
                'token' => $this->options->get( 'import_token' )
            ) );

            if ( $stream->is_ok ) {
                $streams[] = $stream->body;
            } else {
                $this->response( array(
                    'status' => 'error',
                    'message' => $stream->body
                ) );
            }
        }

        return $streams;
    }

    /**
     * Merge Comments
     *
     * Sync comments data with wordpress comments.
     *
     * @since 3.0.0
     *
     * @access private
     *
     * @param array $streams An array of streams.
     *
     * @return void
     */
    private function merge_comments( $streams = array() ) {
        while ( ! empty( $streams ) ) {
            $stream = array_shift( $streams );

            if ( $stream->from_etag < $stream->new_etag ) {
                if ( ! empty( $stream->events ) ) {
                    $sync_status = SpotIM_Comment::sync(
                        $stream->events,
                        $stream->users,
                        $stream->post_id
                    );

                    if ( ! $sync_status ) {
                        $translated_error = esc_html__( 'Could not import comments of from this url: %s', 'spotim-comments' );

                        $this->response( array(
                            'status' => 'error',
                            'message' => sprintf( $translated_error, esc_attr( $stream->url ) )
                        ) );
                    }
                }

                update_post_meta(
                    absint( $stream->post_id ),
                    'spotim_etag',
                    absint( $stream->new_etag ),
                    absint( $stream->from_etag )
                );


                $this->pull_comments( array( $stream->post_id ) );
            }
        }
    }

    /**
     * Finish
     *
     * Return a response to client via json.
     *
     * @since 3.0.0
     *
     * @access private
     *
     * @return void
     */
    private function finish() {
        $response_args = array(
            'status' => '',
            'message' => ''
        );

        $total_posts_count = count( $this->get_post_ids() );
        $current_posts_count = $this->posts_per_request;

        if ( 0 < $this->page_number ) {
            $current_posts_count = $current_posts_count + ( $this->posts_per_request * $this->page_number );
        }

        if ( 0 === $total_posts_count ) {
            $response_args['status'] = 'success';
            $response_args['message'] = esc_html__( 'Your website doesn\'t have any published posts.', 'spotim-comments' );
        } else if ( $current_posts_count < $total_posts_count ) {
            $translated_message = esc_html__( '%d / %d posts are synchronize comments.', 'spotim-comments' );
            $parsed_message = sprintf( $translated_message, $current_posts_count, $total_posts_count );

            $response_args['status'] = 'continue';
            $response_args['message'] = $parsed_message;
        } else {
            $response_args['status'] = 'success';
            $response_args['message'] = esc_html__( 'Your comments are up to date.', 'spotim-comments' );

            $this->options->reset( 'page_number' );
        }

        $this->response( $response_args );
    }

    /**
     * Get Post IDs
     *
     * Retrieve an array of post IDs.
     *
     * @since 3.0.0
     *
     * @access private
     *
     * @param int $posts_per_page Posts per page.
     * @param int $page_number Page number.
     *
     * @return array
     */
    private function get_post_ids( $posts_per_page = -1, $page_number = 0 ) {
        $args = array(
            'posts_per_page' => $posts_per_page,
            'post_type' => array( 'post' ),
            'post_status' => 'publish',
            'orderby' => 'id',
            'order' => 'ASC',
            'fields' => 'ids'
        );

        if ( -1 !== $posts_per_page ) {
            $args['offset'] = $posts_per_page * $page_number;
        }

        if ( 1 === $this->options->get( 'enable_comments_on_page' ) ) {
            $args['post_type'][] = 'page';
        }

        return get_posts( $args );
    }

    /**
     * Request
     *
     * Retrieve data from a remote server.
     *
     * @since 3.0.0
     *
     * @access private
     *
     * @param string|array $query_args Either a query variable key, or an associative array of query variables.
     *
     * @return object
     */
    private function request( $query_args ) {
        $url = add_query_arg( $query_args, SPOTIM_EXPORT_URL );

        $result = new stdClass();
        $result->is_ok = false;

        $response = wp_remote_get( $url, array( 'sslverify' => true ) );

        if ( ! is_wp_error( $response ) &&
             'OK' === wp_remote_retrieve_response_message( $response ) &&
             200 === wp_remote_retrieve_response_code( $response ) ) {

            $response_body = json_decode( wp_remote_retrieve_body( $response ) );

            if ( isset( $response_body->success ) && false === $response_body->success ) {
                $result->is_ok = false;
            } else {
                $result->is_ok = true;
                $result->body = $response_body;
                $result->body->url = $url;
            }
        }

        if ( ! $result->is_ok ) {
            $translated_error = esc_html__( 'Retriving data failed from this url: %s', 'spotim-comments' );

            $result->body = sprintf( $translated_error, esc_attr( $url ) );
        }

        return $result;
    }

    /**
     * Response
     *
     * Retrieve an array of post IDs.
     *
     * @since 3.0.0
     *
     * @access public
     *
     * @param array $args An associative array of query variables.
     *
     * @return void
     */
    public function response( $args = array() ) {
        $statuses_list = array( 'continue', 'success', 'cancel', 'error' );

        $defaults = array(
            'status' => '',
            'message' => ''
        );

        if ( ! empty( $args ) ) {
            $args = array_merge( $defaults, $args );

            if ( ! empty( $args['status'] ) ) {
                $args['message'] = sanitize_text_field( $args['message'] );

                if ( in_array( $args['status'], $statuses_list ) ) {
                    wp_send_json( $args );
                }
            }
        }
    }
}