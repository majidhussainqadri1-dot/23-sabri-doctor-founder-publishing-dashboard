<?php
/**
 * Guarded native operation broker.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Operation_Broker {
	private const MAX_PAYLOAD_DEPTH = 8;
	private const MAX_PAYLOAD_NODES = 500;
	private const MAX_STRING_LENGTH = 8192;

	private SPDB_Adapter_Registry $registry;

	public function __construct( SPDB_Adapter_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Execute one explicitly declared native operation.
	 *
	 * File 23 validates the cross-cutting request envelope. The native provider
	 * remains responsible for final ownership, current state, business policy,
	 * rate limiting, native version conflicts and the actual mutation.
	 *
	 * @param array<string,mixed> $payload Operation payload.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( string $provider_key, string $operation_key, string $object_type, string $object_id, array $payload ) {
		if ( ! SPDB_Adapter_Registry::is_canonical_key( $provider_key ) || ! SPDB_Adapter_Registry::is_canonical_key( $operation_key ) ) {
			return new WP_Error( 'spdb_invalid_operation_route', __( 'The provider or operation route is invalid.', 'sabri-publishing-dashboard' ) );
		}

		if ( ! self::valid_reference( $object_type, $object_id ) ) {
			return new WP_Error( 'spdb_invalid_object_reference', __( 'The native object reference is invalid.', 'sabri-publishing-dashboard' ) );
		}

		$payload_check = self::validate_payload( $payload );
		if ( is_wp_error( $payload_check ) ) {
			return $payload_check;
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
		if ( ! SPDB_Capabilities::current_user_can( (string) $definition['required_capability'] ) ) {
			return new WP_Error( 'spdb_operation_forbidden', __( 'You are not authorized to perform this operation.', 'sabri-publishing-dashboard' ) );
		}
		if ( ! empty( $definition['requires_verified_account'] ) && ! SPDB_Membership_Guard::current_user_is_approved() ) {
			return new WP_Error( 'spdb_membership_not_approved', __( 'An approved Sabri account is required.', 'sabri-publishing-dashboard' ) );
		}

		if ( ! empty( $definition['requires_object_version'] ) ) {
			$version = $payload['object_version'] ?? null;
			if ( ! is_scalar( $version ) || ! self::valid_version( (string) $version ) ) {
				return new WP_Error( 'spdb_object_version_required', __( 'A valid current native object version is required.', 'sabri-publishing-dashboard' ) );
			}
		}
		if ( ! empty( $definition['requires_idempotency_key'] ) && ! self::valid_idempotency_key( $payload['idempotency_key'] ?? null ) ) {
			return new WP_Error( 'spdb_idempotency_key_required', __( 'A valid idempotency key is required.', 'sabri-publishing-dashboard' ) );
		}

		$audit_reason = isset( $payload['audit_reason'] ) && is_scalar( $payload['audit_reason'] )
			? trim( wp_strip_all_tags( (string) $payload['audit_reason'] ) )
			: '';
		if ( '' !== $audit_reason && ! self::valid_audit_reason( $audit_reason ) ) {
			return new WP_Error( 'spdb_invalid_audit_reason', __( 'The audit reason is invalid, sensitive or too long.', 'sabri-publishing-dashboard' ) );
		}
		if ( ! empty( $definition['requires_audit_reason'] ) && self::text_length( $audit_reason ) < 10 ) {
			return new WP_Error( 'spdb_audit_reason_required', __( 'A meaningful audit reason is required.', 'sabri-publishing-dashboard' ) );
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
			if ( is_wp_error( $confirmed_item ) || ! is_array( $confirmed_item ) ) {
				return new WP_Error( 'spdb_native_reconciliation_required', __( 'The native operation requires reconciliation because the authoritative result could not be confirmed.', 'sabri-publishing-dashboard' ) );
			}
			if (
				! isset( $confirmed_item['object_type'], $confirmed_item['object_id'] )
				|| ! hash_equals( $object_type, (string) $confirmed_item['object_type'] )
				|| ! hash_equals( $object_id, (string) $confirmed_item['object_id'] )
			) {
				return new WP_Error( 'spdb_native_reconciliation_required', __( 'The native operation returned a mismatched authoritative object and requires reconciliation.', 'sabri-publishing-dashboard' ) );
			}

			return array(
				'provider_result' => $result,
				'confirmed_item'  => $confirmed_item,
			);
		} catch ( Throwable $throwable ) {
			return new WP_Error( 'spdb_operation_exception', __( 'The native provider failed while executing the operation.', 'sabri-publishing-dashboard' ) );
		}
	}

	private static function valid_reference( string $object_type, string $object_id ): bool {
		return SPDB_Adapter_Registry::is_canonical_key( $object_type )
			&& '' !== $object_id
			&& trim( $object_id ) === $object_id
			&& self::text_length( $object_id ) <= 191
			&& ! preg_match( '/[\x00-\x1F\x7F]/', $object_id );
	}

	private static function valid_version( string $value ): bool {
		return '' !== $value
			&& trim( $value ) === $value
			&& self::text_length( $value ) <= 191
			&& ! preg_match( '/[\x00-\x1F\x7F]/', $value );
	}

	/** @return true|WP_Error */
	public static function validate_payload( array $payload ) {
		$nodes = 0;
		return self::walk_payload( $payload, 0, $nodes );
	}

	/** @return true|WP_Error */
	private static function walk_payload( $value, int $depth, int &$nodes ) {
		++$nodes;
		if ( $depth > self::MAX_PAYLOAD_DEPTH || $nodes > self::MAX_PAYLOAD_NODES ) {
			return new WP_Error( 'spdb_operation_payload_too_large', __( 'The operation payload exceeds the safe complexity limit.', 'sabri-publishing-dashboard' ) );
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$key_name = is_string( $key ) ? strtolower( $key ) : '';
				if ( '' !== $key_name ) {
					if ( self::reserved_key( $key_name ) ) {
						return new WP_Error( 'spdb_reserved_payload_field', __( 'The payload contains a reserved authority field.', 'sabri-publishing-dashboard' ) );
					}
					if ( self::sensitive_key( $key_name ) ) {
						return new WP_Error( 'spdb_sensitive_payload_field', __( 'The payload contains data outside File 23 publishing-operation boundaries.', 'sabri-publishing-dashboard' ) );
					}
					if ( self::text_length( $key_name ) > 128 ) {
						return new WP_Error( 'spdb_operation_payload_too_large', __( 'A payload field name exceeds the safe limit.', 'sabri-publishing-dashboard' ) );
					}
				}
				$result = self::walk_payload( $item, $depth + 1, $nodes );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
			return true;
		}
		if ( is_string( $value ) ) {
			return self::text_length( $value ) <= self::MAX_STRING_LENGTH && ! preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value )
				? true
				: new WP_Error( 'spdb_operation_payload_too_large', __( 'A payload value exceeds the safe limit.', 'sabri-publishing-dashboard' ) );
		}
		return is_scalar( $value ) || null === $value
			? true
			: new WP_Error( 'spdb_operation_payload_invalid', __( 'The operation payload contains an unsupported value.', 'sabri-publishing-dashboard' ) );
	}

	private static function reserved_key( string $key ): bool {
		return in_array(
			$key,
			array( 'provider_key', 'author_id', 'actor_id', 'user_id', 'role', 'roles', 'status', 'capability', 'capabilities', 'environment', 'acceptance_state', 'principal_user_id', 'delegate_user_id' ),
			true
		);
	}

	private static function sensitive_key( string $key ): bool {
		if ( in_array( $key, array( 'message_body', 'conversation_body', 'identity_document', 'national_id', 'access_token', 'refresh_token' ), true ) ) {
			return true;
		}
		$parts = array_filter( explode( '_', $key ), static fn( $part ) => '' !== $part );
		return (bool) array_intersect( $parts, array( 'patient', 'clinical', 'diagnosis', 'prescription', 'remedy', 'potency', 'payment', 'billing', 'card', 'cvv', 'bank', 'passport', 'secret', 'password' ) );
	}

	/** @param mixed $value */
	private static function valid_idempotency_key( $value ): bool {
		return is_string( $value ) && 1 === preg_match( '/^[A-Za-z0-9][A-Za-z0-9._:-]{15,127}$/', $value );
	}

	private static function valid_audit_reason( string $value ): bool {
		return self::text_length( $value ) <= 500
			&& ! preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value )
			&& ! preg_match( '/password|secret|token|nonce|otp|cvv|cookie|bearer|api[ _-]?key|patient[ _-]?(?:name|id)|message[ _-]?body|diagnosis|prescription/i', $value )
			&& ! preg_match( '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $value )
			&& ! preg_match( '/\b\+?\d[\d ()-]{7,}\d\b/', $value );
	}

	private static function text_length( string $value ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value, 'UTF-8' ) : strlen( $value );
	}
}
