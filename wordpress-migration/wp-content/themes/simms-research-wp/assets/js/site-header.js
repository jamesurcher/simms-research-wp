/**
 * Mobile navigation drawer toggle.
 */
( function () {
	var root = document.documentElement;
	var toggle = document.querySelector( '[data-simms-nav-toggle]' );
	var nav = document.getElementById( 'simms-mobile-nav' );

	if ( ! toggle || ! nav ) {
		return;
	}

	var OPEN_CLASS = 'simms-nav-open';

	function isOpen() {
		return root.classList.contains( OPEN_CLASS );
	}

	function open() {
		root.classList.add( OPEN_CLASS );
		toggle.setAttribute( 'aria-expanded', 'true' );
	}

	function close() {
		root.classList.remove( OPEN_CLASS );
		toggle.setAttribute( 'aria-expanded', 'false' );
	}

	toggle.addEventListener( 'click', function () {
		if ( isOpen() ) {
			close();
		} else {
			open();
		}
	} );

	document.querySelectorAll( '[data-simms-nav-close]' ).forEach( function ( el ) {
		el.addEventListener( 'click', close );
	} );

	// Close when a navigation link is followed.
	nav.addEventListener( 'click', function ( event ) {
		if ( event.target.closest( 'a' ) ) {
			close();
		}
	} );

	// Close on Escape.
	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && isOpen() ) {
			close();
		}
	} );

	// Reset when resizing back up to the desktop layout.
	window.addEventListener( 'resize', function () {
		if ( window.innerWidth > 989 && isOpen() ) {
			close();
		}
	} );
}() );
