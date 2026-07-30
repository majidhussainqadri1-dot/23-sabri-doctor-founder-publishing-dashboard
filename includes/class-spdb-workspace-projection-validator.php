<?php
/**
 * Validate role-specific Founder and Doctor workspace projections.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Workspace_Projection_Validator {
	public const MAX_CARDS    = 24;
	public const MAX_ACTIONS  = 12;
	public const MAX_ACTIVITY = 20;
	public const MAX_ALERTS   = 10;

	/** @return array{mutating:bool,founder_only:bool,required_capability:string}|null */
	public static function action_contract( string $action_type ): ?array {
		$contracts = array(
			'official_create'        => array( 'mutating' => true,  'founder_only' => true,  'required_capability' => 'spdb_manage_own_content' ),
			'professional_create'    => array( 'mutating' => true,  'founder_only' => false, 'required_capability' => 'spdb_manage_own_content' ),
			'continue_draft'         => array( 'mutating' => true,  'founder_only' => false, 'required_capability' => 'spdb_manage_own_content' ),
			'open_changes_requested' => array( 'mutating' => true,  'founder_only' => false, 'required_capability' => 'spdb_manage_own_content' ),
			'edit_profile'           => array( 'mutating' => true,  'founder_only' => false, 'required_capability' => 'spdb_manage_own_content' ),
			'open_knowledge'         => array( 'mutating' => true,  'founder_only' => false, 'required_capability' => 'spdb_manage_own_content' ),
			'view_profile'           => array( 'mutating' => false, 'founder_only' => false, 'required_capability' => 'spdb_view_own_content' ),
			'view_public_profile'    => array( 'mutating' => false, 'founder_only' => false, 'required_capability' => 'spdb_view_own_content' ),
		);
		return $contracts[ $action_type ] ?? null;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function normalize( $raw, string $provider_key, array $metadata, array $context ) {
		if ( ! is_array( $raw ) ) {
			return self::error( 'spdb_workspace_projection_invalid', 'The provider returned an invalid workspace projection.' );
		}
		$provider_name    = self::safe_text_or_error( $metadata['provider_name'] ?? '', 160, 'spdb_workspace_provider_name_invalid' );
		$provider_version = self::safe_text_or_error( $metadata['provider_version'] ?? '', 64, 'spdb_workspace_provider_version_invalid' );
		if ( is_wp_error( $provider_name ) || is_wp_error( $provider_version ) ) {
			return is_wp_error( $provider_name ) ? $provider_name : $provider_version;
		}
		$cards     = self::normalize_cards( $raw['cards'] ?? array(), $provider_key, $context );
		$actions   = self::normalize_actions( $raw['actions'] ?? array(), $provider_key, $metadata, $context );
		$activity  = self::normalize_activity( $raw['activity'] ?? array(), $provider_key, $context );
		$alerts    = self::normalize_alerts( $raw['alerts'] ?? array(), $provider_key, $context );
		$profile   = self::normalize_profile( $raw['profile'] ?? null, $provider_key, $context );
		$knowledge = self::normalize_knowledge( $raw['knowledge'] ?? null, $provider_key, $context );
		foreach ( array( $cards, $actions, $activity, $alerts, $profile, $knowledge ) as $part ) {
			if ( is_wp_error( $part ) ) {
				return $part;
			}
		}
		return array( 'provider_key' => $provider_key, 'provider_name' => $provider_name, 'provider_version' => $provider_version, 'cards' => $cards, 'actions' => $actions, 'activity' => $activity, 'alerts' => $alerts, 'profile' => $profile, 'knowledge' => $knowledge );
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	private static function normalize_cards( $raw, string $provider_key, array $context ) {
		if ( null === $raw || array() === $raw ) {
			return array();
		}
		if ( ! is_array( $raw ) || count( $raw ) > self::MAX_CARDS ) {
			return self::error( 'spdb_workspace_cards_invalid', 'The provider returned too many or malformed workspace cards.' );
		}
		$result = array();
		$seen = array();
		foreach ( $raw as $card ) {
			if ( ! is_array( $card ) ) {
				return self::error( 'spdb_workspace_card_invalid', 'A workspace card is malformed.' );
			}
			$key = self::unique_key( $card['key'] ?? '', $seen, 'spdb_workspace_card_key_invalid' );
			$scope = self::normalize_scope( $card['scope'] ?? 'own', $card['owner_user_id'] ?? 0, $context );
			$data_status = self::exact_key( $card['data_status'] ?? 'unavailable', array( 'measured', 'unavailable' ) );
			$priority = self::exact_key( $card['priority'] ?? 'information', array( 'information', 'warning', 'critical' ) );
			foreach ( array( $key, $scope, $data_status, $priority ) as $value ) {
				if ( is_wp_error( $value ) ) {
					return $value;
				}
			}
			$value = '—';
			$timestamp = '';
			if ( 'measured' === $data_status ) {
				$value = self::measured_value( $card['value'] ?? null );
				$timestamp = self::timestamp( $card['source_timestamp'] ?? '' );
				if ( is_wp_error( $value ) || is_wp_error( $timestamp ) || '' === $timestamp ) {
					return is_wp_error( $value ) ? $value : self::error( 'spdb_workspace_card_timestamp_invalid', 'A measured workspace card requires an absolute source timestamp.' );
				}
			}
			$label = self::safe_text_or_error( $card['label'] ?? '', 80, 'spdb_workspace_card_label_invalid' );
			$note = self::safe_text_or_error( $card['note'] ?? '', 240, 'spdb_workspace_card_note_invalid', true );
			if ( is_wp_error( $label ) || is_wp_error( $note ) ) {
				return is_wp_error( $label ) ? $label : $note;
			}
			$result[] = array( 'provider_key' => $provider_key, 'key' => $key, 'label' => $label, 'value' => $value, 'note' => $note, 'priority' => $priority, 'data_status' => $data_status, 'source_timestamp' => $timestamp, 'scope' => $scope['scope'], 'owner_user_id' => $scope['owner_user_id'] );
		}
		return $result;
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	private static function normalize_actions( $raw, string $provider_key, array $metadata, array $context ) {
		if ( null === $raw || array() === $raw ) {
			return array();
		}
		if ( ! is_array( $raw ) || count( $raw ) > self::MAX_ACTIONS ) {
			return self::error( 'spdb_workspace_actions_invalid', 'The provider returned too many or malformed workspace actions.' );
		}
		$result = array();
		$seen = array();
		$declared = is_array( $metadata['supported_capabilities'] ?? null ) ? $metadata['supported_capabilities'] : array();
		foreach ( $raw as $action ) {
			if ( ! is_array( $action ) ) {
				return self::error( 'spdb_workspace_action_invalid', 'A workspace action is malformed.' );
			}
			$key = self::unique_key( $action['key'] ?? '', $seen, 'spdb_workspace_action_key_invalid' );
			$scope = self::normalize_scope( $action['scope'] ?? 'own', $action['owner_user_id'] ?? 0, $context );
			$action_type = self::canonical_key( $action['action_type'] ?? '' );
			$contract = is_wp_error( $action_type ) ? null : self::action_contract( $action_type );
			if ( is_wp_error( $key ) || is_wp_error( $scope ) ) {
				return is_wp_error( $key ) ? $key : $scope;
			}
			if ( null === $contract ) {
				return self::error( 'spdb_workspace_action_type_invalid', 'A workspace action type is invalid.' );
			}
			$capability = self::canonical_key( $action['required_capability'] ?? '' );
			$mutating = $action['mutating'] ?? null;
			$founder_only = $action['founder_only'] ?? null;
			if ( is_wp_error( $capability ) || ! is_bool( $mutating ) || ! is_bool( $founder_only ) || $capability !== $contract['required_capability'] || $mutating !== $contract['mutating'] || $founder_only !== $contract['founder_only'] ) {
				return self::error( 'spdb_workspace_action_contract_mismatch', 'A workspace action contradicts the canonical action contract.' );
			}
			if ( ! in_array( $capability, SPDB_Capabilities::all(), true ) || ! in_array( $capability, $declared, true ) ) {
				return self::error( 'spdb_workspace_action_capability_invalid', 'A workspace action references an undeclared or invalid capability.' );
			}
			$destination = SPDB_Safe_Destination::normalize( $action['destination'] ?? '' );
			$label = self::safe_text_or_error( $action['label'] ?? '', 100, 'spdb_workspace_action_label_invalid' );
			$description = self::safe_text_or_error( $action['description'] ?? '', 240, 'spdb_workspace_action_description_invalid', true );
			foreach ( array( $destination, $label, $description ) as $value ) {
				if ( is_wp_error( $value ) ) {
					return $value;
				}
			}
			$result[] = array( 'provider_key' => $provider_key, 'key' => $key, 'label' => $label, 'description' => $description, 'action_type' => $action_type, 'destination' => $destination, 'required_capability' => $capability, 'mutating' => $mutating, 'founder_only' => $founder_only, 'scope' => $scope['scope'], 'owner_user_id' => $scope['owner_user_id'] );
		}
		return $result;
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	private static function normalize_activity( $raw, string $provider_key, array $context ) {
		return self::normalize_records( $raw, $provider_key, $context, 'activity', self::MAX_ACTIVITY, 'label', 'occurred_at', 200 );
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	private static function normalize_alerts( $raw, string $provider_key, array $context ) {
		return self::normalize_records( $raw, $provider_key, $context, 'alert', self::MAX_ALERTS, 'message', '', 260 );
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	private static function normalize_records( $raw, string $provider_key, array $context, string $kind, int $limit, string $text_field, string $time_field, int $text_limit ) {
		if ( null === $raw || array() === $raw ) {
			return array();
		}
		if ( ! is_array( $raw ) || count( $raw ) > $limit ) {
			return self::error( 'spdb_workspace_' . $kind . 's_invalid', 'The provider returned too many or malformed workspace records.' );
		}
		$result = array();
		$seen = array();
		foreach ( $raw as $record ) {
			if ( ! is_array( $record ) ) {
				return self::error( 'spdb_workspace_' . $kind . '_invalid', 'A workspace record is malformed.' );
			}
			$key = self::unique_key( $record['key'] ?? '', $seen, 'spdb_workspace_' . $kind . '_key_invalid' );
			$scope = self::normalize_scope( $record['scope'] ?? 'own', $record['owner_user_id'] ?? 0, $context );
			$level = self::exact_key( $record['level'] ?? 'information', array( 'information', 'warning', 'critical' ) );
			$text = self::safe_text_or_error( $record[ $text_field ] ?? '', $text_limit, 'spdb_workspace_' . $kind . '_' . $text_field . '_invalid' );
			foreach ( array( $key, $scope, $level, $text ) as $value ) {
				if ( is_wp_error( $value ) ) {
					return $value;
				}
			}
			$normalized = array( 'provider_key' => $provider_key, 'key' => $key, 'level' => $level, $text_field => $text, 'scope' => $scope['scope'], 'owner_user_id' => $scope['owner_user_id'] );
			if ( '' !== $time_field ) {
				$timestamp = self::timestamp( $record[ $time_field ] ?? '' );
				if ( is_wp_error( $timestamp ) || '' === $timestamp ) {
					return self::error( 'spdb_workspace_' . $kind . '_timestamp_invalid', 'A workspace activity record requires an absolute timestamp.' );
				}
				$normalized[ $time_field ] = $timestamp;
			}
			$result[] = $normalized;
		}
		return $result;
	}

	/** @return array<string,mixed>|null|WP_Error */
	private static function normalize_profile( $raw, string $provider_key, array $context ) {
		if ( null === $raw || array() === $raw ) {
			return null;
		}
		if ( ! is_array( $raw ) ) {
			return self::error( 'spdb_workspace_profile_invalid', 'The provider returned an invalid profile projection.' );
		}
		$scope = self::normalize_scope( 'own', $raw['owner_user_id'] ?? 0, $context );
		if ( is_wp_error( $scope ) ) {
			return $scope;
		}
		$completion = $raw['completion_percent'] ?? null;
		if ( null !== $completion && ( ! is_int( $completion ) || $completion < 0 || $completion > 100 ) ) {
			return self::error( 'spdb_workspace_profile_completion_invalid', 'Profile completion must be an integer from 0 through 100.' );
		}
		$eligibility = self::exact_key( $raw['eligibility'] ?? 'unavailable', array( 'eligible', 'pending', 'restricted', 'suspended', 'unavailable' ) );
		$verification = self::optional_canonical_key( $raw['verification_state'] ?? '' );
		$edit = self::optional_destination( $raw['edit_destination'] ?? '' );
		$public = self::optional_destination( $raw['public_destination'] ?? '' );
		$timestamp = self::timestamp( $raw['source_timestamp'] ?? '' );
		foreach ( array( $eligibility, $verification, $edit, $public, $timestamp ) as $value ) {
			if ( is_wp_error( $value ) ) {
				return $value;
			}
		}
		if ( '' === $timestamp ) {
			return self::error( 'spdb_workspace_profile_timestamp_invalid', 'A profile projection requires an absolute source timestamp.' );
		}
		return array( 'provider_key' => $provider_key, 'owner_user_id' => $scope['owner_user_id'], 'completion_percent' => $completion, 'eligibility' => $eligibility, 'verification_state' => $verification, 'edit_destination' => $edit, 'public_destination' => $public, 'source_timestamp' => $timestamp );
	}

	/** @return array<string,mixed>|null|WP_Error */
	private static function normalize_knowledge( $raw, string $provider_key, array $context ) {
		if ( null === $raw || array() === $raw ) {
			return null;
		}
		if ( ! is_array( $raw ) ) {
			return self::error( 'spdb_workspace_knowledge_invalid', 'The provider returned an invalid knowledge projection.' );
		}
		$scope = self::normalize_scope( 'own', $raw['owner_user_id'] ?? 0, $context );
		if ( is_wp_error( $scope ) ) {
			return $scope;
		}
		$counts = array();
		foreach ( array( 'linked_items', 'unlinked_items', 'successful_cases' ) as $field ) {
			$value = $raw[ $field ] ?? null;
			if ( null !== $value && ( ! is_int( $value ) || $value < 0 || $value > 1000000000 ) ) {
				return self::error( 'spdb_workspace_knowledge_count_invalid', 'Knowledge counts must be bounded non-negative integers or unavailable.' );
			}
			$counts[ $field ] = $value;
		}
		$destination = self::optional_destination( $raw['destination'] ?? '' );
		$timestamp = self::timestamp( $raw['source_timestamp'] ?? '' );
		if ( is_wp_error( $destination ) || is_wp_error( $timestamp ) ) {
			return is_wp_error( $destination ) ? $destination : $timestamp;
		}
		if ( '' === $timestamp ) {
			return self::error( 'spdb_workspace_knowledge_timestamp_invalid', 'A knowledge projection requires an absolute source timestamp.' );
		}
		return array_merge( array( 'provider_key' => $provider_key, 'owner_user_id' => $scope['owner_user_id'], 'destination' => $destination, 'source_timestamp' => $timestamp ), $counts );
	}

	/** @return array{scope:string,owner_user_id:int}|WP_Error */
	private static function normalize_scope( $raw_scope, $raw_owner, array $context ) {
		if ( ! is_scalar( $raw_scope ) || ! is_int( $raw_owner ) ) {
			return self::error( 'spdb_workspace_scope_shape_invalid', 'A workspace scope or owner has an invalid shape.' );
		}
		$scope = (string) $raw_scope;
		$owner = $raw_owner;
		$user = (int) ( $context['user_id'] ?? 0 );
		$is_founder = ! empty( $context['is_founder'] );
		if ( 'own' === $scope && ( $user < 1 || $owner !== $user ) ) {
			return self::error( 'spdb_workspace_owner_mismatch', 'An own-scope workspace projection does not belong to the current user.' );
		}
		if ( 'institution' === $scope && ( ! $is_founder || 0 !== $owner ) ) {
			return self::error( 'spdb_workspace_institution_scope_forbidden', 'Institution scope is reserved for the server-verified Founder.' );
		}
		if ( ! in_array( $scope, array( 'own', 'institution' ), true ) ) {
			return self::error( 'spdb_workspace_scope_invalid', 'A workspace scope is invalid.' );
		}
		return array( 'scope' => $scope, 'owner_user_id' => $owner );
	}

	/** @return string|WP_Error */
	private static function unique_key( $raw, array &$seen, string $code ) {
		$key = self::canonical_key( $raw );
		if ( is_wp_error( $key ) || isset( $seen[ $key ] ) ) {
			return self::error( $code, 'A workspace key is invalid or duplicated.' );
		}
		$seen[ $key ] = true;
		return $key;
	}

	/** @return string|WP_Error */
	private static function canonical_key( $raw ) {
		if ( ! is_scalar( $raw ) ) {
			return self::error( 'spdb_workspace_key_shape_invalid', 'A workspace key has an invalid shape.' );
		}
		$key = (string) $raw;
		return SPDB_Adapter_Registry::is_canonical_key( $key ) && sanitize_key( $key ) === $key ? $key : self::error( 'spdb_workspace_key_invalid', 'A workspace key is not canonical.' );
	}

	/** @return string|WP_Error */
	private static function exact_key( $raw, array $allowed ) {
		if ( ! is_scalar( $raw ) ) {
			return self::error( 'spdb_workspace_enum_shape_invalid', 'A workspace enum value has an invalid shape.' );
		}
		$value = (string) $raw;
		return in_array( $value, $allowed, true ) ? $value : self::error( 'spdb_workspace_enum_invalid', 'A workspace enum value is invalid.' );
	}

	/** @return string|WP_Error */
	private static function optional_canonical_key( $raw ) {
		return null === $raw || '' === $raw ? '' : self::canonical_key( $raw );
	}

	/** @return string|WP_Error */
	private static function measured_value( $raw ) {
		if ( is_string( $raw ) && ( strlen( $raw ) > 32 || 1 !== preg_match( '/^-?\d+(?:\.\d+)?$/', $raw ) ) ) {
			return self::error( 'spdb_workspace_card_value_invalid', 'A measured workspace card value is invalid.' );
		}
		if ( ! is_int( $raw ) && ! is_float( $raw ) && ! is_string( $raw ) ) {
			return self::error( 'spdb_workspace_card_value_invalid', 'A measured workspace card must contain a numeric value.' );
		}
		$numeric = (float) $raw;
		if ( ! is_finite( $numeric ) || abs( $numeric ) > 1000000000000000 ) {
			return self::error( 'spdb_workspace_card_value_invalid', 'A measured workspace card value is outside the accepted range.' );
		}
		return (string) $raw;
	}

	/** @return string|WP_Error */
	private static function timestamp( $raw ) {
		if ( ! is_scalar( $raw ) ) {
			return self::error( 'spdb_workspace_timestamp_shape_invalid', 'A workspace timestamp has an invalid shape.' );
		}
		$value = trim( (string) $raw );
		if ( '' === $value ) {
			return '';
		}
		if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/', $value ) ) {
			return self::error( 'spdb_workspace_timestamp_invalid', 'Workspace timestamps must be absolute RFC 3339 values.' );
		}
		try {
			$date = new DateTimeImmutable( $value );
		} catch ( Exception $exception ) {
			return self::error( 'spdb_workspace_timestamp_invalid', 'A workspace timestamp is invalid.' );
		}
		return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d\TH:i:s\Z' );
	}

	/** @return string|WP_Error */
	private static function optional_destination( $raw ) {
		return null === $raw || '' === $raw ? '' : SPDB_Safe_Destination::normalize( $raw );
	}

	/** @return string|WP_Error */
	private static function safe_text_or_error( $raw, int $limit, string $code, bool $allow_empty = false ) {
		if ( ! is_scalar( $raw ) ) {
			return self::error( $code, 'A workspace text field has an invalid shape.' );
		}
		$text = trim( sanitize_text_field( (string) $raw ) );
		if ( strlen( $text ) > $limit ) {
			return self::error( $code, 'A workspace text field exceeds its maximum length.' );
		}
		if ( ( ! $allow_empty && '' === $text ) || self::contains_sensitive_pattern( $text ) ) {
			return self::error( $code, 'A workspace text field is empty, invalid, or contains sensitive identifiers.' );
		}
		return $text;
	}

	private static function contains_sensitive_pattern( string $text ): bool {
		return '' !== $text && 1 === preg_match( '~(?:https?://|www\.|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|\+?\d[\d\s().-]{6,}\d|(?:patient|mrn|cnic|passport)[\s:_-]*\d)~i', $text );
	}

	private static function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 422 ) );
	}
}
