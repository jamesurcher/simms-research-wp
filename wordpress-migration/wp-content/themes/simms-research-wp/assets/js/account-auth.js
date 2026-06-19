/**
 * Passwordless OTP sign-in gate — progressive enhancement.
 *
 * The form-login.php template works on its own via native POST. When this runs,
 * it turns the two forms into an in-place AJAX flow (email -> code -> redirect)
 * with no page reloads. If anything is missing it bails and the native POST
 * fallback takes over.
 */
( function () {
	var cfg  = window.simmsAccountAuth;
	var root = document.querySelector( '[data-simms-auth]' );

	if ( ! cfg || ! root ) {
		return;
	}

	var emailForm = root.querySelector( '[data-simms-auth-email-form]' );
	var codeForm  = root.querySelector( '[data-simms-auth-code-form]' );
	var emailIn   = root.querySelector( '[data-simms-auth-email]' );
	var codeIn    = root.querySelector( '[data-simms-auth-code]' );
	var codeEmail = root.querySelector( '[data-simms-auth-code-email]' );
	var target    = root.querySelector( '[data-simms-auth-target]' );
	var errorBox  = root.querySelector( '[data-simms-auth-error]' );
	var resendBtn = root.querySelector( '[data-simms-auth-resend]' );
	var changeBtn = root.querySelector( '[data-simms-auth-change]' );
	var sending   = false;

	function showError( msg ) {
		if ( ! errorBox ) {
			return;
		}
		errorBox.textContent = msg || '';
		errorBox.hidden      = ! msg;
	}

	function setStep( step ) {
		root.setAttribute( 'data-active-step', String( step ) );
		showError( '' );
		if ( 2 === step && codeIn ) {
			codeIn.focus();
		} else if ( 1 === step && emailIn ) {
			emailIn.focus();
		}
	}

	function post( action, data ) {
		var body = new URLSearchParams();
		body.append( 'action', action );
		body.append( 'nonce', cfg.nonce );
		Object.keys( data ).forEach( function ( key ) {
			body.append( key, data[ key ] );
		} );

		return fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} ).then( function ( response ) {
			return response.json();
		} );
	}

	function startResendCooldown() {
		if ( ! resendBtn ) {
			return;
		}
		var remaining = parseInt( cfg.resendWait, 10 ) || 45;
		var label     = resendBtn.getAttribute( 'data-label' ) || resendBtn.textContent;
		resendBtn.setAttribute( 'data-label', label );
		resendBtn.disabled = true;

		( function tick() {
			if ( remaining <= 0 ) {
				resendBtn.textContent = label;
				resendBtn.disabled    = false;
				return;
			}
			resendBtn.textContent = 'Resend in ' + remaining + 's';
			remaining -= 1;
			window.setTimeout( tick, 1000 );
		}() );
	}

	function requestCode( email ) {
		if ( sending ) {
			return;
		}
		sending = true;
		showError( '' );

		post( cfg.requestAction, { email: email } ).then( function ( res ) {
			sending = false;
			if ( res && res.success ) {
				if ( target ) {
					target.textContent = email;
				}
				if ( codeEmail ) {
					codeEmail.value = email;
				}
				setStep( 2 );
				startResendCooldown();
			} else {
				showError( ( res && res.data && res.data.message ) || 'Something went wrong. Please try again.' );
			}
		} ).catch( function () {
			sending = false;
			showError( 'Network error. Please try again.' );
		} );
	}

	if ( emailForm ) {
		emailForm.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var email = ( ( emailIn && emailIn.value ) || '' ).trim();
			if ( ! email ) {
				showError( 'Enter your email address.' );
				return;
			}
			requestCode( email );
		} );
	}

	if ( codeForm ) {
		codeForm.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			var submit = codeForm.querySelector( '[type="submit"]' );
			var code   = ( ( codeIn && codeIn.value ) || '' ).replace( /\D/g, '' );
			var email  = ( codeEmail && codeEmail.value ) || '';

			if ( code.length < 6 ) {
				showError( 'Enter the 6-digit code.' );
				return;
			}

			if ( submit ) {
				submit.disabled = true;
			}
			showError( '' );

			post( cfg.verifyAction, { email: email, code: code } ).then( function ( res ) {
				if ( res && res.success && res.data && res.data.redirect ) {
					window.location.assign( res.data.redirect );
					return;
				}
				// Privileged accounts get a redirect to wp-login even on "failure".
				if ( res && res.data && res.data.redirect ) {
					window.location.assign( res.data.redirect );
					return;
				}
				if ( submit ) {
					submit.disabled = false;
				}
				if ( codeIn ) {
					codeIn.value = '';
					codeIn.focus();
				}
				showError( ( res && res.data && res.data.message ) || 'That code is incorrect or has expired.' );
			} ).catch( function () {
				if ( submit ) {
					submit.disabled = false;
				}
				showError( 'Network error. Please try again.' );
			} );
		} );
	}

	if ( resendBtn ) {
		resendBtn.addEventListener( 'click', function () {
			var email = ( codeEmail && codeEmail.value ) || '';
			if ( email ) {
				requestCode( email );
			}
		} );
	}

	if ( changeBtn ) {
		changeBtn.addEventListener( 'click', function () {
			setStep( 1 );
		} );
	}

	// If the gate rendered at step 2 after a no-JS round-trip, focus the code.
	if ( '2' === root.getAttribute( 'data-active-step' ) && codeIn ) {
		codeIn.focus();
	}
}() );
