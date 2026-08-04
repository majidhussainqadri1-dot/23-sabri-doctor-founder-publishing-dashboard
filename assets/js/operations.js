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
		if ( /^-?\d+$/.test( value ) ) { return Number( value ); }
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
		var bytes = new Uint8Array( Math.ceil( length / 2 ) );
		if ( window.crypto && window.crypto.getRandomValues ) {
			window.crypto.getRandomValues( bytes );
		} else {
			for ( var i = 0; i < bytes.length; i++ ) {
				bytes[ i ] = Math.floor( Math.random() * 256 );
			}
		}
		return Array.prototype.map.call( bytes, function ( byte ) {
			return byte.toString( 16 ).padStart( 2, '0' );
		} ).join( '' ).slice( 0, length );
	}

	function idempotencyKey( form ) {
		var existing = String( form.getAttribute( 'data-spdb-idempotency-key' ) || '' );
		if ( existing ) { return existing; }
		var key = 'spdb_' + randomHex( 32 );
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
			if ( 'true' === form.getAttribute( 'data-confirm' ) && ! window.confirm( config.strings.confirm ) ) {
				return;
			}
			var submit = form.querySelector( 'button[type="submit"]' );
			if ( submit ) { submit.disabled = true; }
			form.setAttribute( 'data-spdb-submitting', 'true' );
			announce( form, config.strings.working, false );

			var requestKey = idempotencyKey( form );
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
