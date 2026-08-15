<?php
/**
 * Site-wide text find/replace: live front-end rules + serialized-safe DB replace.
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class TextReplaceService
 */
class TextReplaceService {

	public const OPTION_RULES = 'seo_campaign_hub_text_replacements';

	public const MAX_RULES = 50;

	public const MIN_FIND = 3;

	/**
	 * @return void
	 */
	public function init(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		if ( $this->get_enabled_pairs() === [] ) {
			return;
		}
		add_action( 'template_redirect', [ $this, 'start_buffer' ], 2 );
	}

	/**
	 * @return void
	 */
	public function start_buffer(): void {
		if ( is_feed() || is_robots() || is_trackback() ) {
			return;
		}
		if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['elementor-preview'] ) || isset( $_GET['elementor_library'] ) ) {
			return;
		}
		ob_start( [ $this, 'filter_html' ] );
	}

	/**
	 * Replace text between HTML tags only (does not rewrite attributes or tag names).
	 *
	 * @param string $html Buffered HTML.
	 * @return string
	 */
	public function filter_html( string $html ): string {
		if ( $html === '' || stripos( $html, '<html' ) === false ) {
			return $html;
		}

		$pairs = $this->get_enabled_pairs();
		if ( $pairs === [] ) {
			return $html;
		}

		$replaced = preg_replace_callback(
			'/>[^<]*</',
			static function ( array $match ) use ( $pairs ): string {
				$chunk = $match[0];
				foreach ( $pairs as $pair ) {
					$chunk = str_replace( $pair['from'], $pair['to'], $chunk );
				}
				return $chunk;
			},
			$html
		);

		return is_string( $replaced ) ? $replaced : $html;
	}

	/**
	 * @return array<int, array{id:string,from:string,to:string,enabled:string}>
	 */
	public function get_rules(): array {
		$rules = get_option( self::OPTION_RULES, [] );
		if ( ! is_array( $rules ) ) {
			return [];
		}
		$out = [];
		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) || empty( $rule['from'] ) ) {
				continue;
			}
			$out[] = [
				'id'      => sanitize_key( (string) ( $rule['id'] ?? wp_generate_password( 8, false ) ) ),
				'from'    => (string) $rule['from'],
				'to'      => (string) ( $rule['to'] ?? '' ),
				'enabled' => ( (string) ( $rule['enabled'] ?? '1' ) === '1' ) ? '1' : '0',
			];
		}
		return $out;
	}

	/**
	 * @return array<int, array{from:string,to:string}>
	 */
	public function get_enabled_pairs(): array {
		$pairs = [];
		foreach ( $this->get_rules() as $rule ) {
			if ( $rule['enabled'] !== '1' ) {
				continue;
			}
			$pairs[] = [
				'from' => $rule['from'],
				'to'   => $rule['to'],
			];
		}
		return $pairs;
	}

	/**
	 * @param string $from Find.
	 * @param string $to   Replace (may be empty to delete the find text).
	 * @return array{ok:bool,message:string}
	 */
	public function add_rule( string $from, string $to ): array {
		$from = trim( $from );
		$to   = trim( $to );
		$err  = $this->validate_pair( $from, $to );
		if ( $err !== '' ) {
			return [ 'ok' => false, 'message' => $err ];
		}
		$rules = $this->get_rules();
		if ( count( $rules ) >= self::MAX_RULES ) {
			return [ 'ok' => false, 'message' => __( 'Maximum of 50 live rules reached.', 'seo-campaign-hub' ) ];
		}
		$rules[] = [
			'id'      => strtolower( wp_generate_password( 10, false, false ) ),
			'from'    => $from,
			'to'      => $to,
			'enabled' => '1',
		];
		update_option( self::OPTION_RULES, $rules, false );
		return [ 'ok' => true, 'message' => __( 'Live text rule saved. It applies on the public site immediately.', 'seo-campaign-hub' ) ];
	}

	/**
	 * @param string $id Rule id.
	 * @return bool
	 */
	public function delete_rule( string $id ): bool {
		$id    = sanitize_key( $id );
		$rules = array_values(
			array_filter(
				$this->get_rules(),
				static function ( array $rule ) use ( $id ): bool {
					return $rule['id'] !== $id;
				}
			)
		);
		update_option( self::OPTION_RULES, $rules, false );
		return true;
	}

	/**
	 * @param string $id Rule id.
	 * @return bool
	 */
	public function toggle_rule( string $id ): bool {
		$id    = sanitize_key( $id );
		$rules = $this->get_rules();
		foreach ( $rules as $i => $rule ) {
			if ( $rule['id'] === $id ) {
				$rules[ $i ]['enabled'] = $rule['enabled'] === '1' ? '0' : '1';
				update_option( self::OPTION_RULES, $rules, false );
				return true;
			}
		}
		return false;
	}

	/**
	 * Dry-run or apply a database-wide text replace.
	 *
	 * @param string $from Find.
	 * @param string $to   Replace.
	 * @param bool   $dry  Dry run.
	 * @return array{ok:bool,message:string,counts:array<string,int>}
	 */
	public function replace_in_database( string $from, string $to, bool $dry ): array {
		$from = trim( $from );
		$to   = trim( $to );
		$err  = $this->validate_pair( $from, $to );
		if ( $err !== '' ) {
			return [ 'ok' => false, 'message' => $err, 'counts' => [] ];
		}

		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 120 );
		}

		global $wpdb;
		$counts = [
			'posts'     => $this->replace_table_columns( $wpdb->posts, 'ID', [ 'post_title', 'post_content', 'post_excerpt' ], $from, $to, $dry ),
			'postmeta'  => $this->replace_table_columns( $wpdb->postmeta, 'meta_id', [ 'meta_value' ], $from, $to, $dry ),
			'options'   => $this->replace_options( $from, $to, $dry ),
			'comments'  => $this->replace_table_columns( $wpdb->comments, 'comment_ID', [ 'comment_content' ], $from, $to, $dry ),
			'sch_links' => $this->replace_short_links( $from, $to, $dry ),
		];

		$total = array_sum( $counts );
		if ( $dry ) {
			$msg = sprintf(
				/* translators: %d: number of matching rows */
				_n( 'Dry run: %d row would be updated. Nothing was saved.', 'Dry run: %d rows would be updated. Nothing was saved.', $total, 'seo-campaign-hub' ),
				$total
			);
		} else {
			$msg = sprintf(
				/* translators: %d: number of updated rows */
				_n( 'Updated %d row in the database.', 'Updated %d rows in the database.', $total, 'seo-campaign-hub' ),
				$total
			);
			wp_cache_flush();
		}

		return [ 'ok' => true, 'message' => $msg, 'counts' => $counts ];
	}

	/**
	 * @param string $from Find.
	 * @param string $to   Replace.
	 * @return string Error or empty.
	 */
	public function validate_pair( string $from, string $to ): string {
		$from_len = $this->text_length( $from );
		if ( $from_len < self::MIN_FIND ) {
			return __( 'Find must be at least 3 characters.', 'seo-campaign-hub' );
		}
		if ( $from === $to ) {
			return __( 'Find and replace text must be different.', 'seo-campaign-hub' );
		}
		if ( strpbrk( $from, '<>' ) !== false || strpbrk( $to, '<>' ) !== false ) {
			return __( 'HTML tags are not allowed in find or replace text.', 'seo-campaign-hub' );
		}
		if ( preg_match( '/^\s*javascript:/i', $to ) ) {
			return __( 'javascript: targets are not allowed.', 'seo-campaign-hub' );
		}
		return '';
	}

	/**
	 * @param string $value Text.
	 * @return int
	 */
	private function text_length( string $value ): int {
		if ( function_exists( 'mb_strlen' ) ) {
			return (int) mb_strlen( $value, 'UTF-8' );
		}
		return strlen( $value );
	}

	/**
	 * @param string   $table   Table.
	 * @param string   $pk      Primary key column.
	 * @param string[] $columns Columns to scan.
	 * @param string   $from    Find.
	 * @param string   $to      Replace.
	 * @param bool     $dry     Dry run.
	 * @return int Rows changed / would change.
	 */
	private function replace_table_columns( string $table, string $pk, array $columns, string $from, string $to, bool $dry ): int {
		global $wpdb;
		$like    = '%' . $wpdb->esc_like( $from ) . '%';
		$changed = 0;

		$where = [];
		$args  = [];
		foreach ( $columns as $col ) {
			$where[] = "`{$col}` LIKE %s";
			$args[]  = $like;
		}

		$sql = "SELECT `{$pk}`, `" . implode( '`, `', $columns ) . "` FROM `{$table}` WHERE " . implode( ' OR ', $where );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );
		if ( ! is_array( $rows ) ) {
			return 0;
		}

		foreach ( $rows as $row ) {
			$update = [];
			foreach ( $columns as $col ) {
				$original = (string) ( $row->{$col} ?? '' );
				$next     = $this->deep_replace( $from, $to, $original );
				if ( $next !== $original ) {
					$update[ $col ] = $next;
				}
			}
			if ( $update === [] ) {
				continue;
			}
			++$changed;
			if ( $dry ) {
				continue;
			}
			$wpdb->update( $table, $update, [ $pk => $row->{$pk} ] );
		}

		return $changed;
	}

	/**
	 * @param string $from Find.
	 * @param string $to   Replace.
	 * @param bool   $dry  Dry run.
	 * @return int
	 */
	private function replace_options( string $from, string $to, bool $dry ): int {
		global $wpdb;
		$like = '%' . $wpdb->esc_like( $from ) . '%';
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_id, option_name, option_value FROM {$wpdb->options}
				WHERE option_value LIKE %s
				AND option_name NOT LIKE %s
				AND option_name NOT LIKE %s
				AND option_name NOT IN (%s, %s, %s, %s)",
				$like,
				$wpdb->esc_like( '_transient_' ) . '%',
				$wpdb->esc_like( '_site_transient_' ) . '%',
				'cron',
				'seo_campaign_hub_google_drive_tokens',
				self::OPTION_RULES,
				'seo_campaign_hub_url_replacements'
			)
		);
		if ( ! is_array( $rows ) ) {
			return 0;
		}

		$changed = 0;
		foreach ( $rows as $row ) {
			$original = (string) $row->option_value;
			$next     = $this->deep_replace( $from, $to, $original );
			if ( $next === $original ) {
				continue;
			}
			++$changed;
			if ( $dry ) {
				continue;
			}
			$wpdb->update(
				$wpdb->options,
				[ 'option_value' => $next ],
				[ 'option_id' => (int) $row->option_id ]
			);
		}
		return $changed;
	}

	/**
	 * @param string $from Find.
	 * @param string $to   Replace.
	 * @param bool   $dry  Dry run.
	 * @return int
	 */
	private function replace_short_links( string $from, string $to, bool $dry ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'sch_links';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $exists !== $table ) {
			return 0;
		}
		return $this->replace_table_columns( $table, 'id', [ 'targeting_rules' ], $from, $to, $dry );
	}

	/**
	 * Serialized / JSON / plain string replace.
	 *
	 * @param string $from Find.
	 * @param string $to   Replace.
	 * @param mixed  $data Value.
	 * @return mixed
	 */
	private function deep_replace( string $from, string $to, $data ) {
		if ( is_string( $data ) ) {
			$trim = ltrim( $data );
			if ( $trim !== '' && ( $trim[0] === '{' || $trim[0] === '[' ) ) {
				$json = json_decode( $data, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $json ) ) {
					$replaced = $this->deep_replace( $from, $to, $json );
					$encoded  = wp_json_encode( $replaced, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
					return is_string( $encoded ) ? $encoded : $data;
				}
			}
			if ( function_exists( 'is_serialized' ) && is_serialized( $data, false ) ) {
				$un = @unserialize( $data, [ 'allowed_classes' => false ] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
				if ( $un !== false || $data === 'b:0;' ) {
					return serialize( $this->deep_replace( $from, $to, $un ) );
				}
				return $data;
			}
			return str_replace( $from, $to, $data );
		}

		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = $this->deep_replace( $from, $to, $value );
			}
			return $data;
		}

		return $data;
	}
}
