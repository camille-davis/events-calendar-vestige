<?php
/**
 * Calendar class file.
 *
 * @package Events_Calendar_Vestige
 */

namespace Events_Calendar_Vestige;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calendar class.
 */
class Calendar {

	private string $month;
	private string $year;
	private int $days_in_month;
	private int $first_weekday_of_month;
	private array $events;

	/**
	 * Initializes calendar for a given month and year. Defaults to current month/year.
	 *
	 * @param string $month Month name or 2-digit string (eg 'January' or '01').
	 * @param string $year  Year as 4-digit string (eg '2024').
	 */
	public function __construct( $month = null, $year = null ) {

		// If no month provided, default to this month as 2-digit string (eg '01').
		if ( null === $month || '' === $month ) {
			$month = wp_date( 'm' );
		}

		// If month name provided, convert it to 2-digit string (eg '01').
		if ( strlen( $month ) > 2 ) {
			$month = gmdate( 'm', strtotime( $month ) );
			if ( false === $month ) {
				echo 'Error: invalid month name.'; // TODO use WP_Error.
			}
		}

		// Save month as 2-digit string.
		$this->month = $month;

		// If no year provided, default to this year.
		if ( null === $year || '' === $year ) {
			$year = wp_date( 'Y' );
		}

		// Save year 4-digit string.
		$this->year = $year;

		// Get and save additional month data.
		$this->days_in_month          = $this->get_days_in_month();
		$this->first_weekday_of_month = gmdate(
			'N',
			strtotime(
				$this->year . '-' .
				$this->month . '-01'
			)
		);

		// Fetch Vestige core events.
		$this->events = $this->fetch_events();
	}

	/**
	 * Fetches events (Vestige custom post type) to display in Calendar, including:
	 * - Events starting between two months ago and the end of this month
	 *   (aka this month's events + tail end of recurring events)
	 * - Events ending this month - we will append '(End)' to these
	 *
	 * Note: If recurring events span more than 2 months, this function will need an update.
	 */
	private function fetch_events() {

		// Get date for two months ago (first day of the month).
		$two_months_ago = intval( $this->month ) - 2;
		if ( $two_months_ago > 0 ) {
			$date_two_months_ago = $this->year . '-' . strval( $two_months_ago ) . '-01 00:00';
		} else {
			$last_year           = intval( $this->year ) - 1;
			$two_months_ago     += 12;
			$date_two_months_ago = $last_year . '-' . strval( $two_months_ago ) . '-01 00:00';
		}

		// Get date for the end of this month.
		$end_of_this_month = $this->year . '-' . $this->month . '-' . $this->days_in_month . ' 23:59';

		// Fetch events.
		$events = new \WP_Query(
			array(
				'post_type'      => 'event',
				'meta_key'       => 'imic_event_start_dt',
				'meta_query'     => array(
					'relation' => 'OR',

					// Get events starting between two months ago and the end of this month.
					array(
						'key'     => 'imic_event_start_dt',
						'type'    => 'DATETIME',
						'compare' => 'BETWEEN',
						'value'   => array(
							$date_two_months_ago,
							$end_of_this_month,
						),
					),

					// Get events ending this month.
					array(
						'key'     => 'imic_event_frequency_end',
						'type'    => 'DATETIME',
						'compare' => 'BETWEEN',
						'value'   => array(
							$this->year . '-' . $this->month . '-01 00:00',
							$end_of_this_month,
						),
					),
				),
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'posts_per_page' => -1,
			)
		);

		// Build array of events by day to return.
		$events_by_day = array();
		while ( $events->have_posts() ) {
			$events->the_post(); // Gets current post in loop.

			// Get post attributes.
			$id         = get_the_ID();
			$title      = get_the_title();
			$url        = get_the_permalink();
			$title_lang = get_post_meta( $id, 'title_language', true );
			$start_meta = get_post_meta( $id, 'imic_event_start_dt', true );
			$frequency  = get_post_meta( $id, 'imic_event_frequency', true );
			$occurences = get_post_meta( $id, 'imic_event_frequency_count', true );

			// Save data that will be shown in calendar.
			$event_data = array(
				'url'        => $url,
				'title'      => $title,
				'title_lang' => $title_lang,
			);

			// Get date information.
			$start_parsed = date_parse( $start_meta );
			$start_year   = $start_parsed['year'];
			$start_month  = $start_parsed['month'];
			$start_day    = $start_parsed['day'];
			$start_date   = $start_year . '-' . $start_month . '-' . $start_day;

			// If it's a recurring event, add all this month's occurences and continue.
			if ( $occurences ) {

				// Get Unix time for first occurrence.
				$datetime = new \DateTime( $start_meta, new \DateTimeZone( 'America/Los_Angeles' ) );
				$i        = intval( $datetime->format( 'U' ) );

				// Get Unix time for end of this month.
				$month_end         = $this->year . '-' . $this->month . '-' . $this->days_in_month . ' 23:59';
				$time_at_month_end = new \DateTime( $month_end, new \DateTimeZone( 'America/Los_Angeles' ) );
				$time_at_month_end = intval( $time_at_month_end->format( 'U' ) );

				// Add occurrences until end of this month.
				$occurence = 0;
				while ( $i <= $time_at_month_end ) {
					++$occurence;
					if ( $occurence > $occurences ) {
						break;
					}

					// If occurrence isn't this month, continue to next occurrence.
					if ( intval( wp_date( 'm', $i ) ) !== intval( $this->month ) ) {
						$i += $frequency * 86400;
						continue;
					}

					// Add occurence to $events_by_day and continue.
					$day = wp_date( 'j', $i );
					if ( ! isset( $events_by_day[ $day ] ) ) {
						$events_by_day[ $day ] = array( $event_data );
					} else {
						array_push( $events_by_day[ $day ], $event_data );
					}
					$i += $frequency * 86400;
				}
				continue;
			}

			// Event is not recurring. If starts this month, add it.
			if ( intval( $start_month ) === intval( $this->month ) ) {
				if ( ! isset( $events_by_day[ $start_day ] ) ) {
					$events_by_day[ $start_day ] = array( $event_data );
				} else {
					array_push( $events_by_day[ $start_day ], $event_data );
				}
			}

			// If event is multi-day and ends this month, add the event end.
			$end_meta   = get_post_meta( $id, 'imic_event_end_dt', true ) . ' America/Los_Angeles';
			$end_parsed = date_parse( $end_meta );
			$end_year   = $end_parsed['year'];
			$end_month  = $end_parsed['month'];
			$end_day    = $end_parsed['day'];
			$end_date   = $end_year . '-' . $end_month . '-' . $end_day;
			if ( ( intval( $end_month ) === intval( $this->month ) ) && ( $end_date !== $start_date ) ) {
				$event_data['title'] .= ' (End)'; // Indicate event ending in title.
				if ( ! isset( $events_by_day[ $end_day ] ) ) {
					$events_by_day[ $end_day ] = array( $event_data );
				} else {
					array_push( $events_by_day[ $end_day ], $event_data );
				}
			}
		}

		return $events_by_day;
	}

	/**
	 * Creates Calendar html.
	 *
	 * @return string
	 */
	public function create_html() {

		// Open wrapper div.
		$content = '<div class="month ecv-calendar">';

		// Create header.
		$month_name = gmdate( 'F', strtotime( $this->year . '-' . $this->month . '-01' ) );
		$content   .= '<div class="header">' .
			'<h2 class="title">' . $month_name . ' Events</h2>' .
			'</div>';

		// Open table.
		$content .= '<table class="' . $month_name . '"><tbody>';

		// Create weekday labels.
		$content   .= '<tr>';
		$day_labels = array( 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' );
		foreach ( $day_labels as $index => $label ) {
			$content .= '<th scope="col">' . $label . '</th>';
		}
		$content .= '</tr>';

		// Create each week.
		$weeks_in_month = $this->get_weeks_in_month();
		for ( $i = 1; $i <= $weeks_in_month; $i++ ) {
			$content .= '<tr>';
			for ( $j = 1; $j <= 7; $j++ ) {
				$cell_number = ( $i - 1 ) * 7 + $j;

				// If cell isn't in month, create an empty cell.
				if ( ( $cell_number < $this->first_weekday_of_month )
				|| ( $cell_number > $this->first_weekday_of_month + $this->days_in_month - 1 ) ) {
					$content .= '<td valign="top" class="has-no-events"><div class="date"></div></td>';
					continue;
				}

				// Create a cell with day html and events.
				$content .= $this->create_day_html( $cell_number );
			}
			$content .= '</tr>';
		}

		// Close table and wrapper div.
		$content .= '</tbody></table>';
		$content .= '</div>';

		return $content;
	}

	/**
	 * Creates html for a day and its events.
	 *
	 * @param int $cell_number Table cell number in loop (starts at 1).
	 * @return string
	 */
	private function create_day_html( $cell_number ) {

		// Get day from cell number.
		$day = $cell_number - $this->first_weekday_of_month + 1;

		// Cell without events.
		if ( ! isset( $this->events[ strval( $day ) ] ) ) {
			return '<td valign="top" class="has-no-events"><div class="date">' .
				'<h3 class="number">' . $day . '</h3>' .
				'</div></td>';
		}

		// Cell with events.
		$weekdays     = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
		$weekday      = $weekdays[ $cell_number % 7 ];
		$cell_content = '<td valign="top" class="has-events"><div class="date">' .
			'<h3 class="number"><span class="inner-weekday">' . $weekday . '</span> ' .
			'<span class="inner-date">' . $day . '</span></h3>';

		// If that day has multiple events, add them as a list.
		if ( count( $this->events[ strval( $day ) ] ) > 1 ) {
			$cell_content .= '<ul class="events">';
			foreach ( $this->events[ strval( $day ) ] as $event ) {
					$cell_content .= '<li class="many">' .
						'<a lang="' . $event['title_lang'] . '" href="' . $event['url'] . '">' .
						$event['title'] . '</a>' .
						'</li>';
			}
			$cell_content .= '</ul>';
		} else {

			// Otherwise add event as div.
			$event         = $this->events[ strval( $day ) ][0];
			$cell_content .= '<div class="events"><div class="single">' .
				'<a lang="' . $event['title_lang'] . '" href="' . $event['url'] . '">' .
				$event['title'] . '</a>' .
				'</div></div>';
		}

		$cell_content .= '</td>';
		return $cell_content;
	}

	/**
	 * Calculate number of days in month.
	 *
	 * @return int
	 */
	private function get_days_in_month() {
		$month = intval( $this->month );
		$year  = intval( $this->year );
		return 2 === $month ? ( $year % 4 ? 28 : ( $year % 100 ? 29 : ( $year % 400 ? 28 : 29 ) ) ) : ( ( $month - 1 ) % 7 % 2 ? 30 : 31 );
	}

	/**
	 * Calculate number of weeks in month.
	 *
	 * @return int
	 */
	private function get_weeks_in_month() {
		$num_of_weeks    = ( 0 === $this->days_in_month % 7 ? 0 : 1 ) + intval( $this->days_in_month / 7 );
		$month_end_day   = gmdate( 'N', strtotime( $this->year . '-' . $this->month . '-' . $this->days_in_month ) );
		$month_start_day = gmdate( 'N', strtotime( $this->year . '-' . $this->month . '-01' ) );
		if ( $month_end_day < $month_start_day ) {
			++$num_of_weeks;
		}
		return $num_of_weeks;
	}
}
