<?php
/**
 * Guarded native operation broker.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Operation_Broker {
	private SPDB_Adapter_Registry $registry;

	public function __construct( SPDB_Adapter_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Execute one explicitly declared native operation.
	 *
	 * This broker provides File 23-wide gates. The native provider remains
	 * responsible for final ownership, current-state, policy, rate-limit, nonce,
	 * object-version, and mutation checks.
	 *
	 * @param string              $provider_key Provider key.
	 * @param string              $operation_key Operation key.
	 * @param string              $object_type   Native object type.
	 * @param string              $object_id     Native immutable identifier.
	 * @param array<string,mixed> $payload       Operation payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( string $provider_key, string $operation_key, string $object_type, string $object_id, array $payload ) {
		if ( ! SPDB_Adapter_Registry::is_canonical_key( $provider_key ) || ! SPDB_Adapter_Registry::is_canonical_key( $operation_key ) ) {
			return new WP_Error( 'spdb_invalid_operation_route', __( 'The provider or operation route is invalid.', 'sabri-publishing-dashboard' ) );
		}

		if (
			! SPDB_Adapter_Registry::is_canonical_key( $object_type )
			|| '' === $object_id
			|| trim( $object_id ) !== $object_id
			|| strlen( $object_id ) > 191
			|| preg_match( '/[\x00-\x1F\x7F]/', $object_id )
		) {
			return new WP_Error( 'spdb_invalid_object_reference', __( 'The native object reference is invalid.', 'sabri-publishing-dashboard' ) );
		}

		$adapter  = $this->registry->get( $provider_key );
		$metadata = $this->registry->metadata( $provider_key );
		if ( null === $adapter || null === $metadata ) {
			return new WP_Error( 'spdb_provider_unavailable', __( 'The provider is unavailable.', 'sabri-publishing-dashboard' ) );
		}

		if ( ! $this->registry->is_environment_write_eligible( $provider_key ) ) {
			return new WP_Error( 'spdb_provider_not_accepted', __( 'The provider is not accepted for write operations in this environment.', 'sabri-publishing-dashboard' ) );
		}

		if ( ! in_array( $object_type, $metadata['object_types'], true ) ) {
			return new WP_Error( 'spdb_unsupported_object_type', __( 'The provider does not support this object type.', 'sabri-publishing-dashboard' ) );
		}

		$definition = $metadata['operation_definitions'][ $operation_key ] ?? null;
		if ( ! is_array( $definition ) ) {
			return new WP_Error( 'spdb_unregistered_operation', __( 'The requested operation is not registered.', 'sabri-publishing-dashboard' ) );
		}

		if ( ! SPDB_Capabilities::current_user_can( $definition['required_capability'] ) ) {
			return new WP_Error( 'spdb_operation_forbidden', __( 'You are not authorized to perform this operation.', 'sabri-publishing-dashboard' ) );
		}

		if ( $definition['requires_verified_account'] && ! SPDB_Membership_Guard::current_user_is_approved() ) {
			return new WP_Error( 'spdb_membership_not_approved', __( 'An approved Sabri account is required.', 'sabri-publishing-dashboard' ) );
		}

		if ( $definition['requires_object_version'] && empty( $payload['object_version'] ) ) {
			return new WP_Error( 'spdb_object_version_required', __( 'The current native object version is required.', 'sabri-publishing-dashboard' ) );
		}

		if ( $definition['requires_idempotency_key'] && ! $this->valid_idempotency_key( $payload['idempotency_key'] ?? null ) ) {
			return new WP_Error( 'spdb_idempotency_key_required', __( 'A valid idempotency key is required.', 'sabri-publishing-dashboard' ) );
		}

		$audit_reason = trim( (string) ( $payload['audit_reason'] ?? '' ) );
		if ( '' !== $audit_reason && ( strlen( $audit_reason ) > 500 || preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $audit_reason ) ) ) {
			return new WP_Error( 'spdb_invalid_audit_reason', __( 'The audit reason is invalid or too long.', 'sabri-publishing-dashboard' ) );
		}

		if ( $definition['requires_audit_reason'] && strlen( $audit_reason ) < 10 ) {
			return new WP_Error( 'spdb_audit_reason_required', __( 'A meaningful audit reason is required.', 'sabri-publishing-dashboard' ) );
		}

		foreach ( array( 'provider_key', 'author_id', 'role', 'status', 'capability', 'environment', 'acceptance_state' ) as $reserved_key ) {
			if ( array_key_exists( $reserved_key, $payload ) ) {
				return new WP_Error( 'spdb_reserved_payload_field', __( 'The payload contains a reserved authority field.', 'sabri-publishing-dashboard' ) );
			}
		}

		try {
			$allowed = $adapter->get_allowed_operations( $object_type, $object_id );
			if ( ! is_array( $allowed ) || ! in_array( $operation_key, $allowed, true ) ) {
				return new WP_Error( 'spdb_native_operation_forbidden', __( 'The native provider denied this operation.', 'sabri-publishing-dashboard' ) );
			}

			$result = $adapter->execute_operation( $operation_key, $object_type, $object_id, $payload );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$confirmed_item = $adapter->get_item( $object_type, $object_id );
			if ( is_wp_error( $confirmed_item ) ) {
				return new WP_Error( 'spdb_native_reread_failed', __( 'The operation completed, but the native result could not be confirmed.', 'sabri-publishing-dashboard' ) );
			}

			return array(
				'provider_result' => $result,
				'confirmed_item'  => $confirmed_item,
			);
		} catch ( Throwable $throwable ) {
			return new WP_Error( 'spdb_operation_exception', __( 'The native provider failed while executing the operation.', 'sabri-publishing-dashboard' ) );
		}
	}

	/**
	 * @param mixed $value Idempotency value.
	 */
	private function valid_idempotency_key( $value ): bool {
		return is_string( $value ) && 1 === preg_match( '/^[A-Za-z0-9._:-]{16,128}$/', $value );
	}
}
