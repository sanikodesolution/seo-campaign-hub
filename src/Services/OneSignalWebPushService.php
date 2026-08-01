<?php
/**
 * OneSignal Web Push (visitor subscribe + send notifications).
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OneSignalWebPushService
 */
class OneSignalWebPushService {

	public const META_SKIP = '_sch_webpush_skip';
	public const META_SENT = '_sch_webpush_sent_at';

	/**
	 * Register hooks.
	 */
	public function init(): void {
		add_action( 'transition_post_status', [ $this, 'maybe_auto_notify' ], 20, 3 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend' ], 20 );
		add_action( 'init', [ $this, 'serve_service_worker' ], 0 );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_options(): array {
		$options = get_option( 'seo_campaign_hub_options', [] );
		return is_array( $options ) ? $options : [];
	}

	public function is_enabled(): bool {
		$o = $this->get_options();
		if ( empty( $o['enable_web_push'] ) || (string) $o['enable_web_push'] !== '1' ) {
			return false;
		}
		return $this->get_app_id() !== '';
	}

	public function get_app_id(): string {
		$o = $this->get_options();
		return isset( $o['onesignal_app_id'] ) ? trim( (string) $o['onesignal_app_id'] ) : '';
	}

	public function get_rest_api_key(): string {
		$o = $this->get_options();
		return isset( $o['onesignal_rest_api_key'] ) ? trim( (string) $o['onesignal_rest_api_key'] ) : '';
	}

	public function is_auto_notify_enabled(): bool {
		$o = $this->get_options();
		if ( ! array_key_exists( 'web_push_auto_notify', $o ) ) {
			return true;
		}
		return (string) $o['web_push_auto_notify'] === '1';
	}

	public function is_soft_prompt_enabled(): bool {
		$o = $this->get_options();
		if ( ! array_key_exists( 'web_push_soft_prompt', $o ) ) {
			return true;
		}
		return (string) $o['web_push_soft_prompt'] === '1';
	}

	public function get_soft_prompt_delay(): int {
		$o     = $this->get_options();
		$delay = isset( $o['web_push_soft_prompt_delay'] ) ? (int) $o['web_push_soft_prompt_delay'] : 8;
		return max( 0, min( 120, $delay ) );
	}

	public function is_configured(): bool {
		return $this->get_app_id() !== '' && $this->get_rest_api_key() !== '';
	}

	/**
	 * Serve OneSignal service worker at site root path.
	 */
	public function serve_service_worker(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path        = (string) parse_url( $request_uri, PHP_URL_PATH );
		$path        = untrailingslashit( $path );

		if ( $path !== '/OneSignalSDKWorker.js' && $path !== '/OneSignalSDK.sw.js' ) {
			return;
		}

		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Service-Worker-Allowed: /' );
		header( 'Cache-Control: public, max-age=0, must-revalidate' );
		echo 'importScripts("https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.sw.js");'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Enqueue OneSignal SDK + soft prompt on the front end.
	 */
	public function enqueue_frontend(): void {
		if ( is_admin() || ! $this->is_enabled() ) {
			return;
		}

		$app_id = $this->get_app_id();
		if ( $app_id === '' ) {
			return;
		}

		$version = defined( 'SEO_CAMPAIGN_HUB_VERSION' ) ? SEO_CAMPAIGN_HUB_VERSION : '1.0.0';
		$url     = defined( 'SEO_CAMPAIGN_HUB_PLUGIN_URL' ) ? SEO_CAMPAIGN_HUB_PLUGIN_URL : '';

		wp_enqueue_style(
			'seo-campaign-hub-onesignal-prompt',
			$url . 'assets/public/css/onesignal-prompt.css',
			[],
			$version
		);

		wp_enqueue_script(
			'onesignal-sdk',
			'https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js',
			[],
			null,
			true
		);
		wp_script_add_data( 'onesignal-sdk', 'strategy', 'defer' );

		wp_enqueue_script(
			'seo-campaign-hub-onesignal',
			$url . 'assets/public/js/onesignal-init.js',
			[ 'onesignal-sdk' ],
			$version,
			true
		);

		wp_localize_script(
			'seo-campaign-hub-onesignal',
			'schOneSignal',
			[
				'appId'           => $app_id,
				'softPrompt'      => $this->is_soft_prompt_enabled(),
				'delay'           => $this->get_soft_prompt_delay(),
				'serviceWorkerPath' => 'OneSignalSDKWorker.js',
				'i18n'            => [
					'title'   => __( 'Get deal alerts', 'seo-campaign-hub' ),
					'message' => __( 'Allow notifications to hear about new posts and offers.', 'seo-campaign-hub' ),
					'allow'   => __( 'Allow', 'seo-campaign-hub' ),
					'later'   => __( 'Not now', 'seo-campaign-hub' ),
				],
			]
		);
	}

	/**
	 * Auto-notify when a post is first published.
	 *
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post object.
	 */
	public function maybe_auto_notify( string $new_status, string $old_status, $post ): void {
		if ( ! ( $post instanceof \WP_Post ) ) {
			return;
		}
		if ( $post->post_type !== 'post' || $new_status !== 'publish' || $old_status === 'publish' ) {
			return;
		}
		if ( ! $this->is_enabled() || ! $this->is_auto_notify_enabled() || ! $this->is_configured() ) {
			return;
		}
		if ( get_post_meta( $post->ID, self::META_SKIP, true ) === '1' ) {
			return;
		}

		$this->send_for_post( (int) $post->ID );
	}

	/**
	 * Build and send a notification for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array{ok:bool,message:string,id?:string}
	 */
	public function send_for_post( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! ( $post instanceof \WP_Post ) || $post->post_type !== 'post' || $post->post_status !== 'publish' ) {
			return [
				'ok'      => false,
				'message' => __( 'Only published posts can be pushed.', 'seo-campaign-hub' ),
			];
		}

		$title   = wp_strip_all_tags( get_the_title( $post ) );
		$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 24 );
		$url     = get_permalink( $post );
		$image   = get_the_post_thumbnail_url( $post, 'large' );

		$result = $this->send_notification(
			[
				'title'   => $title !== '' ? $title : __( 'New post', 'seo-campaign-hub' ),
				'message' => $excerpt !== '' ? $excerpt : $title,
				'url'     => is_string( $url ) ? $url : home_url( '/' ),
				'image'   => is_string( $image ) ? $image : '',
			]
		);

		if ( ! empty( $result['ok'] ) ) {
			update_post_meta( $post_id, self::META_SENT, gmdate( 'c' ) );
		}

		return $result;
	}

	/**
	 * Send a custom notification via OneSignal REST API.
	 *
	 * @param array{title?:string,message?:string,url?:string,image?:string} $args Args.
	 * @return array{ok:bool,message:string,id?:string}
	 */
	public function send_notification( array $args ): array {
		if ( ! $this->is_configured() ) {
			return [
				'ok'      => false,
				'message' => __( 'Add your OneSignal App ID and REST API Key first.', 'seo-campaign-hub' ),
			];
		}

		$title   = isset( $args['title'] ) ? sanitize_text_field( (string) $args['title'] ) : '';
		$message = isset( $args['message'] ) ? sanitize_textarea_field( (string) $args['message'] ) : '';
		$url     = isset( $args['url'] ) ? esc_url_raw( (string) $args['url'] ) : '';
		$image   = isset( $args['image'] ) ? esc_url_raw( (string) $args['image'] ) : '';

		if ( $title === '' || $message === '' ) {
			return [
				'ok'      => false,
				'message' => __( 'Title and message are required.', 'seo-campaign-hub' ),
			];
		}

		if ( $url === '' ) {
			$url = home_url( '/' );
		}

		$payload = [
			'app_id'             => $this->get_app_id(),
			'target_channel'     => 'push',
			'included_segments'  => [ 'All Subscribers' ],
			'headings'           => [ 'en' => $title ],
			'contents'           => [ 'en' => $message ],
			'url'                => $url,
		];

		if ( $image !== '' ) {
			$payload['chrome_web_image'] = $image;
			$payload['firefox_icon']     = $image;
		}

		$response = wp_remote_post(
			'https://api.onesignal.com/notifications',
			[
				'timeout' => 20,
				'headers' => [
					'Content-Type'  => 'application/json; charset=utf-8',
					'Authorization' => 'Key ' . $this->get_rest_api_key(),
				],
				'body'    => wp_json_encode( $payload ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'ok'      => false,
				'message' => $response->get_error_message(),
			];
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			$body = [];
		}

		if ( $code < 200 || $code >= 300 || empty( $body['id'] ) ) {
			$err = '';
			if ( ! empty( $body['errors'] ) && is_array( $body['errors'] ) ) {
				$err = implode( ' ', array_map( 'strval', $body['errors'] ) );
			} elseif ( ! empty( $body['error'] ) ) {
				$err = (string) $body['error'];
			}
			return [
				'ok'      => false,
				'message' => $err !== '' ? $err : sprintf(
					/* translators: %d: HTTP status */
					__( 'OneSignal request failed (HTTP %d).', 'seo-campaign-hub' ),
					$code
				),
			];
		}

		return [
			'ok'      => true,
			'message' => __( 'Notification sent.', 'seo-campaign-hub' ),
			'id'      => (string) $body['id'],
		];
	}
}
