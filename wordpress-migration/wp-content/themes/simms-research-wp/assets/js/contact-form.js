/**
 * Contact + affiliate forms — progressive enhancement.
 *
 * The page templates POST natively to admin-post.php and render a server-side
 * confirmation/error state from a query arg. That round-trip can be swallowed
 * by full-page edge caching, so when this script runs it submits via AJAX and
 * swaps in the confirmation client-side — instant feedback, no reload, no cache
 * in the way. If anything is missing it bails and the native POST takes over.
 */
( function () {
	var cfg = window.simmsContactForm;

	if ( ! cfg || ! cfg.forms || typeof window.fetch !== 'function' ) {
		return;
	}

	var forms = document.querySelectorAll( '[data-simms-form]' );
	if ( ! forms.length ) {
		return;
	}

	function enhance( form ) {
		var type    = form.getAttribute( 'data-simms-form' );
		var formCfg = cfg.forms[ type ];
		if ( ! formCfg ) {
			return;
		}

		var panel   = form.closest( '[data-simms-form-panel]' ) || form.parentNode;
		var area    = form.closest( '[data-simms-form-area]' );
		var confirm = panel.querySelector( '[data-simms-confirmation]' );
		var errorBox  = form.querySelector( '[data-simms-error]' );
		var errorText = form.querySelector( '[data-simms-error-text]' );
		var submit  = form.querySelector( '[type="submit"]' );
		var sending = false;

		function showError( msg ) {
			if ( errorText ) {
				errorText.textContent = msg;
			}
			if ( errorBox ) {
				errorBox.hidden = false;
				if ( typeof errorBox.focus === 'function' ) {
					errorBox.focus();
				} else {
					errorBox.scrollIntoView( { behavior: 'smooth', block: 'center' } );
				}
			}
		}

		function setSending( on ) {
			sending = on;
			if ( ! submit ) {
				return;
			}
			submit.disabled = on;
			submit.classList.toggle( 'is-loading', on );
			if ( on ) {
				submit.setAttribute( 'data-label', submit.textContent );
				submit.textContent = ( cfg.i18n && cfg.i18n.sending ) || 'Sending…';
			} else if ( submit.getAttribute( 'data-label' ) ) {
				submit.textContent = submit.getAttribute( 'data-label' );
			}
		}

		function succeed() {
			if ( area ) {
				area.hidden = true;
			} else {
				form.hidden = true;
			}
			if ( confirm ) {
				confirm.hidden = false;
				if ( typeof confirm.focus === 'function' ) {
					confirm.focus();
				}
				confirm.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			}
		}

		form.addEventListener( 'submit', function ( event ) {
			if ( sending ) {
				event.preventDefault();
				return;
			}
			// Let the browser run native required-field validation first.
			if ( typeof form.checkValidity === 'function' && ! form.checkValidity() ) {
				return;
			}

			event.preventDefault();

			if ( errorBox ) {
				errorBox.hidden = true;
			}
			setSending( true );

			var body = new URLSearchParams( new FormData( form ) );
			body.set( 'action', formCfg.action );
			body.set( 'nonce', formCfg.nonce );

			window.fetch( cfg.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body.toString()
			} ).then( function ( response ) {
				return response.json().catch( function () {
					return null;
				} );
			} ).then( function ( res ) {
				setSending( false );
				if ( res && res.success ) {
					succeed();
					return;
				}
				showError( ( res && res.data && res.data.message ) || ( cfg.i18n && cfg.i18n.network ) || 'Something went wrong. Please try again.' );
			} ).catch( function () {
				setSending( false );
				showError( ( cfg.i18n && cfg.i18n.network ) || 'Network error. Please try again.' );
			} );
		} );
	}

	Array.prototype.forEach.call( forms, enhance );
}() );
