<?php
/**
 * Schedules cloud backups via WP-Cron.
 *
 * @package SEO_Campaign_Hub\Services
 */

namespace SEO_Campaign_Hub\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BackupSchedulerService
 */
class BackupSchedulerService {

	private const HOOK = 'seo_campaign_hub_cloud_backup_cron';

	private GoogleDriveService $drive;

	/**
	 * @param GoogleDriveService $drive Drive settings.
	 */
	public function __construct( GoogleDriveService $drive ) {
		$this->drive = $drive;
	}

	/**
	 * @return void
	 */
	public function init(): void {
		add_filter( 'cron_schedules', [ $this, 'add_weekly_schedule' ] );
	}

	/**
	 * @param array<string, array<string, int|string>> $schedules Schedules.
	 * @return array<string, array<string, int|string>>
	 */
	public function add_weekly_schedule( array $schedules ): array {
		if ( ! isset( $schedules['weekly'] ) ) {
			$schedules['weekly'] = [
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Once Weekly', 'seo-campaign-hub' ),
			];
		}
		return $schedules;
	}

	/**
	 * Clear and optionally reschedule cron from settings.
	 *
	 * @return void
	 */
	public function reschedule(): void {
		wp_clear_scheduled_hook( self::HOOK );

		$settings = $this->drive->get_settings();
		if ( empty( $settings['schedule_enabled'] ) || '1' !== (string) $settings['schedule_enabled'] ) {
			return;
		}

		$frequency = (string) ( $settings['schedule_frequency'] ?? 'daily' );
		$recurrence = 'weekly' === $frequency ? 'weekly' : 'daily';

		$timestamp = time() + HOUR_IN_SECONDS;
		wp_schedule_event( $timestamp, $recurrence, self::HOOK );
	}

	/**
	 * @return void
	 */
	public function clear(): void {
		wp_clear_scheduled_hook( self::HOOK );
	}
}
