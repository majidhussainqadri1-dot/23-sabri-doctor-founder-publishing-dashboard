( function () {
	'use strict';

	if ( ! window.SPDBOperations || ! window.wp || ! window.wp.apiFetch ) {
		return;
	}

	var apiFetch = window.wp.apiFetch;
	var config = window.SPDBOperations;
	apiFetch.use( apiFetch.createNonceMiddleware( config.nonce ) );

	function scalarValue( value ) {
		if ( 'true' === value ) { return true; }
		if ( 'false' === value ) { return false; }
		// Generic form fields can be textual even when they contain digits.
		// Number() would strip leading zeroes and round integers > 2^53.
		// Native REST field validators own numeric parsing, not this serializer.
		return value;
	}

	function serialize( form ) {
		var data = {};
		var formData = new window.FormData( form );
		formData.forEach( function ( rawValue, rawKey ) {
			var key = String( rawKey );
			var value = scalarValue( String( rawValue ) );
			if ( /\[\]$/.test( key ) ) {
				key = key.replace( /\[\]$/, '' );
				if ( ! Array.isArray( data[ key ] ) ) { data[ key ] = []; }
				data[ key ].push( value );
				return;
			}
			data[ key ] = value;
		} );

		form.querySelectorAll( 'input[type="checkbox"]' ).forEach( function ( input ) {
			if ( /\[\]$/.test( input.name ) ) { return; }
			data[ input.name ] = input.checked;
		} );
		return data;
	}

	function announce( form, message, error ) {
		var shell = form.closest( '.spdb-shell' ) || document;
		var target = shell.querySelector( '[data-spdb-operation-status]' );
		if ( ! target ) { return; }
		target.textContent = message || '';
		target.setAttribute( 'data-error', error ? 'true' : 'false' );
	}

	function endpoint( form ) {
		var path = String( form.getAttribute( 'data-endpoint' ) || '' ).replace( /^\/+/, '' );
		return '/spdb/v1/' + path;
	}

	function randomHex( length ) {
		// Operational idempotency must never degrade to Math.random().
		// No CSPRNG means no privileged mutation request.
		if ( ! window.crypto || 'function' !== typeof window.crypto.getRandomValues ) {
			return null;
		}
		var bytes = new Uint8Array( Math.ceil( length / 2 ) );
		try {
			window.crypto.getRandomValues( bytes );
		} catch ( error ) {
			return null;
		}
		return Array.prototype.map.call( bytes, function ( byte ) {
			return byte.toString( 16 ).padStart( 2, '0' );
		} ).join( '' ).slice( 0, length );
	}

	function idempotencyKey( form ) {
		var existing = String( form.getAttribute( 'data-spdb-idempotency-key' ) || '' );
		if ( existing ) { return existing; }
		var entropy = randomHex( 32 );
		if ( ! entropy ) {
			return null;
		}
		var key = 'spdb_' + entropy;
		form.setAttribute( 'data-spdb-idempotency-key', key );
		return key;
	}

	function initialize( form ) {
		function clearIdempotencyKey() {
			if ( 'true' !== form.getAttribute( 'data-spdb-submitting' ) ) {
				form.removeAttribute( 'data-spdb-idempotency-key' );
			}
		}
		form.addEventListener( 'input', clearIdempotencyKey );
		form.addEventListener( 'change', clearIdempotencyKey );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			// Enter/keyboarding can emit another submit even while disabled.
			// One logical submission must only dispatch once until settlement.
			if ( 'true' === form.getAttribute( 'data-spdb-submitting' ) ) {
				return;
			}
			if ( 'true' === form.getAttribute( 'data-confirm' ) && ! window.confirm( config.strings.confirm ) ) {
				return;
			}
			var requestKey = idempotencyKey( form );
			if ( ! requestKey ) {
				announce( form, config.strings.secureUnavailable || config.strings.failed, true );
				return;
			}
			var submit = form.querySelector( 'button[type="submit"]' );
			if ( submit ) { submit.disabled = true; }
			form.setAttribute( 'data-spdb-submitting', 'true' );
			announce( form, config.strings.working, false );

			apiFetch( {
				path: endpoint( form ),
				method: form.getAttribute( 'data-method' ) || 'POST',
				headers: { 'Idempotency-Key': requestKey },
				data: serialize( form )
			} ).then( function ( response ) {
				form.removeAttribute( 'data-spdb-idempotency-key' );
				if ( /ai-assistance$/.test( endpoint( form ) ) && response && response.suggestion ) {
					var shell = form.closest( '.spdb-shell' ) || document;
					var target = shell.querySelector( '[data-spdb-operation-status]' );
					if ( target ) {
						target.textContent = '';
						var heading = document.createElement( 'strong' );
						heading.textContent = config.strings.saved;
						var suggestion = document.createElement( 'p' );
						suggestion.textContent = String( response.suggestion );
						target.appendChild( heading );
						target.appendChild( suggestion );
						if ( Array.isArray( response.citations ) && response.citations.length ) {
							var list = document.createElement( 'ul' );
							response.citations.forEach( function ( citation ) {
								var item = document.createElement( 'li' );
								item.textContent = String( citation.title || citation.source_id || '' );
								list.appendChild( item );
							} );
							target.appendChild( list );
						}
					}
					return;
				}
				var message = response && response.status === 'queued' ? config.strings.queued : config.strings.saved;
				announce( form, message, false );
				window.setTimeout( function () { window.location.reload(); }, 450 );
			} ).catch( function ( error ) {
				announce( form, error && error.message ? error.message : config.strings.failed, true );
			} ).finally( function () {
				form.removeAttribute( 'data-spdb-submitting' );
				if ( submit ) { submit.disabled = false; }
			} );
		} );
	}

	document.querySelectorAll( '[data-spdb-operation-form]' ).forEach( initialize );
}() );
