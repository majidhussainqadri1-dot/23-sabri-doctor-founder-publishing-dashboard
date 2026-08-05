( function () {
	'use strict';

	if ( ! window.SPDBDashboard || ! window.wp || ! window.wp.apiFetch ) {
		return;
	}

	var apiFetch = window.wp.apiFetch;
	var config = window.SPDBDashboard;
	apiFetch.use( apiFetch.createNonceMiddleware( config.nonce ) );

	function idempotencyKey() {
		if ( ! window.crypto || 'function' !== typeof window.crypto.getRandomValues ) { return ''; }
		var bytes = new Uint8Array( 16 );
		window.crypto.getRandomValues( bytes );
		return 'spdb_' + Array.prototype.map.call( bytes, function ( byte ) {
			return byte.toString( 16 ).padStart( 2, '0' );
		} ).join( '' );
	}

	function filtersSummary( filters ) {
		var keys = Object.keys( filters || {} );
		if ( ! keys.length ) {
			return '';
		}

		return keys.map( function ( key ) {
			var value = Array.isArray( filters[ key ] ) ? filters[ key ].join( ', ' ) : filters[ key ];
			return key + ': ' + value;
		} ).join( ' · ' );
	}

	function createListItem( view ) {
		var item = document.createElement( 'li' );
		var content = document.createElement( 'div' );
		var title = document.createElement( 'strong' );
		var details = document.createElement( 'small' );
		var button = document.createElement( 'button' );

		item.setAttribute( 'data-view-id', view.id );
		title.textContent = view.label;
		details.textContent = filtersSummary( view.filters );
		button.type = 'button';
		button.className = 'spdb-button spdb-button--secondary';
		button.setAttribute( 'data-spdb-delete-view', view.id );
		button.textContent = config.strings.deleteLabel;

		content.appendChild( title );
		content.appendChild( details );
		item.appendChild( content );
		item.appendChild( button );
		return item;
	}

	function collectFilters( formElement ) {
		var fields = [ 'status', 'provider', 'sort' ];
		var filters = {};

		fields.forEach( function ( field ) {
			var input = formElement.elements[ field ];
			if ( input && input.value ) {
				filters[ field ] = input.value;
			}
		} );

		return filters;
	}

	function initializeShell( shell ) {
		var form = shell.querySelector( '[data-spdb-saved-view-form]' );
		var list = shell.querySelector( '[data-spdb-saved-view-list]' );
		var emptyState = shell.querySelector( '.spdb-empty-state' );
		var status = shell.querySelector( '[data-spdb-status]' );

		function announce( message, isError ) {
			if ( ! status ) {
				return;
			}
			status.textContent = message || '';
			status.setAttribute( 'data-error', isError ? 'true' : 'false' );
		}

		function setEmptyState( isEmpty ) {
			if ( emptyState ) {
				emptyState.hidden = ! isEmpty;
			}
		}

		if ( ! form || ! list ) {
			return;
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			if ( 'true' === form.getAttribute( 'data-spdb-submitting' ) ) { return; }
			var submit = form.querySelector( 'button[type="submit"]' );
			var labelInput = form.elements.label;
			var label = labelInput ? labelInput.value.trim() : '';
			var requestKey = idempotencyKey();

			if ( ! label || ! submit || ! requestKey ) {
				if ( labelInput ) {
					labelInput.focus();
				}
				if ( ! requestKey ) { announce( config.strings.createFailed, true ); }
				return;
			}

			submit.disabled = true;
			form.setAttribute( 'data-spdb-submitting', 'true' );
			announce( config.strings.loading, false );

			apiFetch( {
				path: '/spdb/v1/saved-views',
				method: 'POST',
				headers: { 'Idempotency-Key': requestKey },
				data: {
					label: label,
					filters: collectFilters( form ),
					idempotency_key: requestKey
				}
			} ).then( function ( view ) {
				list.appendChild( createListItem( view ) );
				setEmptyState( false );
				form.reset();
				announce( config.strings.created, false );
			} ).catch( function ( error ) {
				announce( error && error.message ? error.message : config.strings.createFailed, true );
			} ).finally( function () {
				form.removeAttribute( 'data-spdb-submitting' );
				submit.disabled = false;
			} );
		} );

		list.addEventListener( 'click', function ( event ) {
			var target = event.target;
			if ( ! target || 'function' !== typeof target.closest ) {
				return;
			}

			var button = target.closest( '[data-spdb-delete-view]' );
			if ( ! button || ! list.contains( button ) ) {
				return;
			}

			var id = button.getAttribute( 'data-spdb-delete-view' );
			var requestKey = idempotencyKey();
			if ( ! id || ! requestKey ) {
				if ( ! requestKey ) { announce( config.strings.deleteFailed, true ); }
				return;
			}

			button.disabled = true;
			apiFetch( {
				path: '/spdb/v1/saved-views/' + encodeURIComponent( id ),
				method: 'DELETE',
				headers: { 'Idempotency-Key': requestKey }
			} ).then( function () {
				var item = button.closest( 'li' );
				if ( item ) {
					item.remove();
				}
				setEmptyState( 0 === list.children.length );
				announce( config.strings.deleted, false );
			} ).catch( function ( error ) {
				button.disabled = false;
				announce( error && error.message ? error.message : config.strings.deleteFailed, true );
			} );
		} );
	}

	document.querySelectorAll( '.spdb-shell' ).forEach( initializeShell );
}() );
