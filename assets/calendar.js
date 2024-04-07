/**
 * Calendar js.
 */

jQuery( document ).ready( function () {
	const open = ( $cell ) => {
		const $dropdown = $cell.find( '.events' );

		// Removes inline height after animation end.
		const reset = () => {
			$dropdown.css( 'height', '' );
			$dropdown.off( 'transitionend', reset );
		};
		$dropdown.on( 'transitionend', reset );

		// Set style to 0 to prepare for css transition.
		$dropdown.css( 'height', '0' );

		// Open the date.
		$cell.addClass( 'open' );
		$dropdown.attr( 'aria-hidden', 'false' );
		$dropdown.find( 'a' ).each( ( index, a ) => {
			a.removeAttribute( 'tabindex' );
		} );
		$cell.find( '.number a' ).attr( 'aria-expanded', 'true' );
		window.requestAnimationFrame( () => {
			window.setTimeout( () => {
				const fullHeight = getFullHeight( $dropdown[ 0 ] ) + 'px';
				$dropdown.css( 'height', fullHeight );
			} );
		} );
	};

	// Closes a date's events.
	const close = ( $cell ) => {
		const $dropdown = $cell.find( '.events' );

		// Removes inline height after animation end.
		const reset = () => {
			$dropdown.css( 'height', '' );
			$dropdown.off( 'transitionend', reset );
		};
		$dropdown.on( 'transitionend', reset );

		// Set style to full height to prepare for css transition.
		const fullHeight = getFullHeight( $dropdown[ 0 ] ) + 'px';
		$dropdown.css( 'height', fullHeight );

		// Close the date.
		$cell.removeClass( 'open' );
		$dropdown.attr( 'aria-hidden', 'false' );
		$dropdown.find( 'a' ).each( ( index, a ) => {
			jQuery( a ).attr( 'tabindex', '-1' );
		} );
		$cell.find( '.number a' ).attr( 'aria-expanded', 'false' );
		window.requestAnimationFrame( () => {
			window.setTimeout( () => {
				$dropdown.css( 'height', '0' );
			} );
		} );
	};

	const getFullHeight = ( el ) => {
		// TODO jQuery?
		let fullHeight = 0;
		for ( let i = 0; i < el.children.length; i++ ) {
			fullHeight += el.children[ i ].offsetHeight;
			const style = getComputedStyle( el.children[ i ] );
			fullHeight +=
				parseInt( style.marginTop ) + parseInt( style.marginBottom );
		}
		const parentStyle = getComputedStyle( el );
		fullHeight +=
			parseInt( parentStyle.paddingTop ) +
			parseInt( parentStyle.paddingBottom );
		return fullHeight;
	};

	// Aligns event list right if it's going off the page.
	const align = ( $cell ) => {
		const link = $cell.find( '.number a' )[ 0 ];
		const $dropdown = $cell.find( '.events' );
		const rect = link.getBoundingClientRect();
		if ( rect.x + 200 > document.body.clientWidth ) {
			$dropdown.addClass( 'rightAlign' );
		} else {
			$dropdown.removeClass( 'rightAlign' );
		}
	};

	const initCalendar = ( $calendar ) => {
		// Get every date that has events.
		$calendar.find( '.has-events .date' ).each( ( index, cell ) => {
			const $cell = jQuery( cell );

			// Hide events list from keyboard and screen readers.
			const $dropdown = $cell.find( '.events' );
			$dropdown.attr( 'aria-hidden', 'true' );
			$dropdown.find( 'a' ).each( ( indexOfA, a ) => {
				jQuery( a ).attr( 'tabindex', '-1' );
			} );

			// Create the toggle link.
			const $link = jQuery( document.createElement( 'a' ) );
			$link.append( $cell.find( '.inner-date' ) ); // Use existing date text.
			$link.attr( 'href', '#' );
			$link
				.attr( 'aria-haspopup', 'true' )
				.attr( 'aria-expanded', 'false' );
			$cell.find( '.number' ).append( $link );

			// Add toggle functionality.
			$cell.on( 'click', ( e ) => {
				e.preventDefault();
				if ( ! $cell.hasClass( 'open' ) ) {
					open( $cell );
					return;
				}
				close( $cell );
			} );

			// Add hover functionality.
			$cell.hover(
				() => {
					open( $cell );
				},
				() => {
					close( $cell );
				}
			);

			// Press 'Esc' to close.
			jQuery( document ).on( 'keyup', ( e ) => {
				if ( e.key === 'Escape' ) {
					close( $cell );
				}
			} );

			// Align event lists right if they're going off the page.
			align( $cell );
			jQuery( window ).on( 'resize', () => {
				align( $cell );
			} );
		} );
	};

	// Get every calendar and initialize js functionality.
	jQuery( '.ecv-calendar' ).each( ( index, calendar ) => {
		initCalendar( jQuery( calendar ) );
	} );
} );
