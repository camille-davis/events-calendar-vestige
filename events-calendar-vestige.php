<?php
/**
 * Plugin Name: Events Calendar (Extension for Vestige Core)
 * Version: 1.0.0
 * Description: Display events in a dynamic calendar using a shortcode. Usage: [ecv_calendar month="January" year="2024"]
 * Author: Camille Davis
 *
 * @package Events_Calendar_Vestige
 **/

namespace Events_Calendar_Vestige;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode callback to display calendar for a given month and year.
 *
 * @param array $atts User defined attributes in shortcode tag.
 */
function ecv_calendar( $atts ) {

	// Load plugin class file and css.
	include_once 'includes/class-calendar.php';
	wp_enqueue_style( 'ecv-calendar', plugins_url( '/assets/calendar.css', __FILE__ ) );

	$attributes = shortcode_atts(
		array(
			'month' => '',
			'year'  => '',
		),
		$atts
	);

	// Sanitize shortcode attributes.
	$month = preg_replace( '/\s*,\s*/', ',', sanitize_text_field( $attributes['month'] ) );
	$year  = preg_replace( '/\s*,\s*/', ',', sanitize_text_field( $attributes['year'] ) );

	// Generate calendar for given month and year.
	$calendar = new Calendar( $month, $year );

	// Display calendar html.
	echo wp_kses(
		$calendar->create_html(),
		array(
			'a'     => array(
				'href' => array(),
				'lang' => array(),
			),
			'div'   => array(
				'class' => array(),
			),
			'h2'    => array(
				'class' => array(),
			),
			'h3'    => array(
				'class' => array(),
			),
			'li'    => array(
				'class' => array(),
			),
			'span'  => array(
				'class' => array(),
			),
			'table' => array(
				'class' => array(),
			),
			'tbody' => array(),
			'td'    => array(
				'class'  => array(),
				'valign' => array(),
			),
			'th'    => array(
				'scope' => array(),
			),
			'tr'    => array(),
			'ul'    => array(
				'class' => array(),
			),
		)
	);

	// Load calendar js.
	wp_enqueue_script( 'ecv-calendar', plugins_url( '/assets/calendar.js', __FILE__ ), array( 'jquery' ), null, true ); // Load in footer.
}

add_shortcode( 'ecv_calendar', __NAMESPACE__ . '\\ecv_calendar' );
