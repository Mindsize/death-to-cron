<?php
namespace Mindsize\Commands;

if ( ! ( defined( 'ABSPATH' ) && defined( 'WP_CLI' ) && WP_CLI ) ) {
	return;
}

use WP_CLI;
use WP_CLI_Command;

/**
 * Implements command to kill all cron events with specified hook names
 */
class Clear_All_Scheduled_Hooks extends WP_CLI_Command {
	/**
	 * Removes all scheduled events with given hook name, regardless of arguments
	 *
	 * ## OPTIONS
	 *
	 * [<hook>...]
	 * : ids of the hook you wish to completely remove
	 *
	 * ## EXAMPLES
	 *
	 *     wp cron event killall woocommerce_deliver_webhook_async some_other_hook
	 *
	 * @param mixed $args        positional arguments (hooks)
	 * @param mixed $assoc_args  accepted arguments (empty in this case)
	 */
	public function killall( $args, $assoc_args ) {
		$crons = _get_cron_array();

		if ( empty( $crons ) ) {
			WP_CLI::warning( 'There were no cron events on the site, exiting...' );
			WP_CLI::halt( 0 );
		}

		$starting_number_of_events = count( $crons );
		$total_removed = 0;
		$summary = [];
		$t0 = microtime( true );

		foreach ( $args as $hook ) {
			// set up some analytics variables
			$events_removed = 0;
			$t1 = microtime( true );

			// remove our crons from the cron list. All of them. Regardless of arguments
			foreach ( $crons as $timestamp => $cron ) {
				if ( isset( $cron[ $hook ] ) ) {
					unset( $crons[ $timestamp ][ $hook ] );
					$events_removed++;
				}
			}

			if ( 0 === $events_removed ) {
				$hook_data = ['name' => $hook, 'removed' => 0, 'time' => sprintf( '%dms', number_format( ( microtime( true ) - $t1 ) * 1000, 0 ) ) ];
				$summary[] = $hook_data;
				continue;
			}

			$total_removed += $events_removed;

			// Make sure we have no empty timestamps
			$crons = array_filter( $crons );

			// set up some more analytics variables
			$c3 = count( $crons );
			$t2 = microtime( true );

			// save our new cron overlord
			$hook_data = [ 'name' => $hook, 'removed' => $events_removed, 'time' => sprintf( '%dms', number_format( ( $t2 - $t1 ) * 1000, 0 ) ) ];
			$summary[] = $hook_data;
		}

		_set_cron_array( $crons );
		$t4 = microtime( true );
		$total = ['name' => 'Total:', 'removed' => $total_removed, 'time' => sprintf( '%dms', number_format( ( $t4 - $t0 ) * 1000, 0 ) ) ];
		$summary[] = $total;
		WP_CLI\Utils\format_items( 'table', $summary, array( 'name', 'removed', 'time' ) );

		WP_CLI::success( 'All good :)' );
	}
}

WP_CLI::add_command( 'mindsize cron', __NAMESPACE__ . '\\Clear_All_Scheduled_Hooks' );

