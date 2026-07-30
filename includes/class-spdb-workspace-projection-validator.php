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

	/**
	 * @param mixed               $raw          Native provider projection.
	 * @param string              $provider_key Registered provider key.
	 * @param array<string,mixed> $metadata     Registry metadata.
	 * @param array<string,mixed> $context      Server-derived workspace context.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function normalize( $raw, string $provider_key, array $metadata, array $context ) {
		if ( ! is_array( $raw ) ) {
			return self::error( 'spdb_workspace_projection_invalid', 'The provider returned an invalid workspace projection.' );
		}

		$cards = self::normalize_cards( $raw['cards'] ?? array(), $provider_key, $context );
		if ( is_wp_error( $cards ) ) {
			return $cards;
		}

		$actions = self::normalize_actions( $raw['actions'] ?? array(), $provider_key, $context );
		if ( is_wp_error( $actions ) ) {
			return $actions;
		}

		$activity = self::normalize_activity( $raw['activity'] ?? array(), $provider_key, $context );
		if ( is_wp_error( $activity ) ) {
			return $activity;
		}

		$alerts = self::normalize_alerts( $raw['alerts'] ?? array(), $provider_key, $context );
		if ( is_wp_error( $alerts ) ) {
			return $alerts;
		}

		$profile = self::normalize_profile( $raw['profile'] ?? null, $provider_key, $context );
		if ( is_wp_error( $profile ) ) {
			return $profile;
		}

		$knowledge = self::normalize_knowledge( $raw['knowledge'] ?? null, $provider_key, $context );
		if ( is_wp_error( $knowledge ) ) {
			return $knowledge;
		}

		return array(
			'provider_key'     => $provider_key,
			'provider_name'    => self::safe_text( $metadata['provider_name'] ?? $provider_key, 160, 'spdb_workspace_provider_name_invalid' ),
			'provider_version' => self::safe_text( $metadata['provider_version'] ?? '', 64, 'spdb_workspace_provider_version_invalid' ),
			'cards'            => $cards,
			'actions'          => $actions,
			'activity'         => $activity,
			'alerts'           => $alerts,
			'profile'          => $profile,
			'knowledge'        => $knowledge,
		);
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	private static function normalize_cards( $raw, string $provider_key, array $context ) {
		if ( null === $raw || array() === $raw ) {
			return array();
		}
		if ( ! is_array( $raw ) || count( $raw ) > self::MAX_CARDS ) {
			return self::error( 'spdb_workspace_cards_invalid', 'The provider returned too many or malformed workspace cards.' );
		}

		$cards = array();
		$seen  = array();
		foreach ( $raw as $card ) {
			if ( ! is_array( $card ) ) {
				return self::error( 'spdb_workspace_card_invalid', 'A workspace card is malformed.' );
			}

			$key = self::canonical_key( $card['key'] ?? '' );
			if ( is_wp_error( $key ) || isset( $seen[ $key ] ) ) {
				return self::error( 'spdb_workspace_card_key_invalid', 'A workspace card key is invalid or duplicated.' );
			}
			$seen[ $key ] = true;

			$scope = self::normalize_scope( $card['scope'] ?? 'own', $card['owner_user_id'] ?? 0, $context );
			if ( is_wp_error( $scope ) ) {
				return $scope;
			}

			$data_status = self::exact_key( $card['data_status'] ?? 'unavailable', array( 'measured', 'unavailable' ) );
			if ( is_wp_error( $data_status ) ) {
				return self::error( 'spdb_workspace_card_status_invalid', 'A workspace card data status is invalid.' );
			}

			$value = $card['value'] ?? null;
			$timestamp = self::timestamp( $card['source_timestamp'] ?? '' );
			if ( 'measured' === $data_status ) {
				if ( ! is_int( $value ) && ! is_float( $value ) && ! ( is_string( $value ) && preg_match( '/^-?\d+(?:\.\d+)?$/', $value ) ) ) {
					return self::error( 'spdb_workspace_card_value_invalid', 'A measured workspace card must contain a numeric value.' );
				}
				if ( is_wp_error( $timestamp ) || '' === $timestamp ) {
					return self::error( 'spdb_workspace_card_timestamp_invalid', 'A measured workspace card requires an absolute source timestamp.' );
				}
				$value = (string) $value;
			} else {
				$value     = '—';
				$timestamp = '';
			}

			$priority = self::exact_key( $card['priority'] ?? 'information', array( 'information', 'warning', 'critical' ) );
			if ( is_wp_error( $priority ) ) {
				return self::error( 'spdb_workspace_card_priority_invalid', 'A workspace card priority is invalid.' );
			}

			$cards[] = array(
				'provider_key'    => $provider_key,
				'key'             => $key,
				'label'           => self::safe_text_or_error( $card['label'] ?? '', 80, 'spdb_workspace_card_label_invalid' ),
				'value'           => $value,
				'note'            => self::safe_text_or_error( $card['note'] ?? '', 240, 'spdb_workspace_card_note_invalid', true ),
				'priority'        => $priority,
				'data_status'     => $data_status,
				'source_timestamp'=> $timestamp,
				'scope'           => $scope['scope'],
				'owner_user_id'   => $scope['owner_user_id'],
			);
		}
		foreach ( $cards as $card ) {
			foreach ( array( 'label', 'note' ) as $field ) {
				if ( is_wp_error( $card[ $field ] ) ) {
					return $card[ $field ];
				}
			}
		}
		return $cards;
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	private static function normalize_actions( $raw, string $provider_key, array $context ) {
		if ( null === $raw || array() === $raw ) {
			return array();
		}
		if ( ! is_array( $raw ) || count( $raw ) > self::MAX_ACTIONS ) {
			return self::error( 'spdb_workspace_actions_invalid', 'The provider returned too many or malformed workspace actions.' );
		}

		$allowed_types = array(
			'official_create',
			'professional_create',
			'continue_draft',
			'open_changes_requested',
			'view_profile',
			'edit_profile',
			'open_knowledge',
			'view_public_profile',
		);
		$actions = array();
		$seen    = array();
		foreach ( $raw as $action ) {
			if ( ! is_array( $action ) ) {
				return self::error( 'spdb_workspace_action_invalid', 'A workspace action is malformed.' );
			}

			$key = self::canonical_key( $action['key'] ?? '' );
			if ( is_wp_error( $key ) || isset( $seen[ $key ] ) ) {
				return self::error( 'spdb_workspace_action_key_invalid', 'A workspace action key is invalid or duplicated.' );
			}
			$seen[ $key ] = true;

			$scope = self::normalize_scope( $action['scope'] ?? 'own', $action['owner_user_id'] ?? 0, $context );
			if ( is_wp_error( $scope ) ) {
				return $scope;
			}

			$action_type = self::exact_key( $action['action_type'] ?? '', $allowed_types );
			if ( is_wp_error( $action_type ) ) {
				return self::error( 'spdb_workspace_action_type_invalid', 'A workspace action type is invalid.' );
			}

			$capability = self::canonical_key( $action['required_capability'] ?? '' );
			if ( is_wp_error( $capability ) || ! in_array( $capability, SPDB_Capabilities::all(), true ) ) {
				return self::error( 'spdb_workspace_action_capability_invalid', 'A workspace action references an invalid capability.' );
			}

			$destination = SPDB_Safe_Destination::normalize( $action['destination'] ?? '' );
			if ( is_wp_error( $destination ) ) {
				return $destination;
			}

			$mutating = $action['mutating'] ?? false;
			if ( ! is_bool( $mutating ) ) {
				return self::error( 'spdb_workspace_action_mutation_flag_invalid', 'A workspace action mutation flag is invalid.' );
			}

			$founder_only = $action['founder_only'] ?? false;
			if ( ! is_bool( $founder_only ) ) {
				return self::error( 'spdb_workspace_action_founder_flag_invalid', 'A workspace action Founder flag is invalid.' );
			}
			if ( 'official_create' === $action_type ) {
				$founder_only = true;
			}

			$label = self::safe_text_or_error( $action['label'] ?? '', 100, 'spdb_workspace_action_label_invalid' );
			$description = self::safe_text_or_error( $action['description'] ?? '', 240, 'spdb_workspace_action_description_invalid', true );
			if ( is_wp_error( $label ) || is_wp_error( $description ) ) {
				return is_wp_error( $label ) ? $label : $description;
			}

			$actions[] = array(
				'provider_key'        => $provider_key,
				'key'                 => $key,
				'label'               => $label,
				'description'         => $description,
				'action_type'         => $action_type,
				'destination'         => $destination,
				'required_capability' => $capability,
				'mutating'            => $mutating,
				'founder_only'        => $founder_only,
				'scope'               => $scope['scope'],
				'owner_user_id'       => $scope['owner_user_id'],
			);
		}
		return $actions;
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	private static function normalize_activity( $raw, string $provider_key, array $context ) {
		if ( null === $raw || array() === $raw ) {
			return array();
		}
		if ( ! is_array( $raw ) || count( $raw ) > self::MAX_ACTIVITY ) {
			return self::error( 'spdb_workspace_activity_invalid', 'The provider returned too many or malformed activity records.' );
		}
		$result = array();
		$seen   = array();
		foreach ( $raw as $event ) {
			if ( ! is_array( $event ) ) {
				return self::error( 'spdb_workspace_activity_event_invalid', 'A workspace activity record is malformed.' );
			}
			$key = self::canonical_key( $event['key'] ?? '' );
			if ( is_wp_error( $key ) || isset( $seen[ $key ] ) ) {
				return self::error( 'spdb_workspace_activity_key_invalid', 'A workspace activity key is invalid or duplicated.' );
			}
			$seen[ $key ] = true;
			$scope = self::normalize_scope( $event['scope'] ?? 'own', $event['owner_user_id'] ?? 0, $context );
			if ( is_wp_error( $scope ) ) {
				return $scope;
			}
			$timestamp = self::timestamp( $event['occurred_at'] ?? '' );
			if ( is_wp_error( $timestamp ) || '' === $timestamp ) {
				return self::error( 'spdb_workspace_activity_timestamp_invalid', 'A workspace activity record requires an absolute timestamp.' );
			}
			$level = self::exact_key( $event['level'] ?? 'information', array( 'information', 'warning', 'critical' ) );
			if ( is_wp_error( $level ) ) {
				return self::error( 'spdb_workspace_activity_level_invalid', 'A workspace activity level is invalid.' );
			}
			$label = self::safe_text_or_error( $event['label'] ?? '', 200, 'spdb_workspace_activity_label_invalid' );
			if ( is_wp_error( $label ) ) {
				return $label;
			}
			$result[] = array(
				'provider_key' => $provider_key,
				'key'          => $key,
				'label'        => $label,
				'level'        => $level,
				'occurred_at'  => $timestamp,
				'scope'        => $scope['scope'],
				'owner_user_id'=> $scope['owner_user_id'],
			);
		}
		return $result;
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	private static function normalize_alerts( $raw, string $provider_key, array $context ) {
		if ( null === $raw || array() === $raw ) {
			return array();
		}
		if ( ! is_array( $raw ) || count( $raw ) > self::MAX_ALERTS ) {
			return self::error( 'spdb_workspace_alerts_invalid', 'The provider returned too many or malformed workspace alerts.' );
		}
		$result = array();
		$seen   = array();
		foreach ( $raw as $alert ) {
			if ( ! is_array( $alert ) ) {
				return self::error( 'spdb_workspace_alert_invalid', 'A workspace alert is malformed.' );
			}
			$key = self::canonical_key( $alert['key'] ?? '' );
			if ( is_wp_error( $key ) || isset( $seen[ $key ] ) ) {
				return self::error( 'spdb_workspace_alert_key_invalid', 'A workspace alert key is invalid or duplicated.' );
			}
			$seen[ $key ] = true;
			$scope = self::normalize_scope( $alert['scope'] ?? 'own', $alert['owner_user_id'] ?? 0, $context );
			if ( is_wp_error( $scope ) ) {
				return $scope;
			}
			$level = self::exact_key( $alert['level'] ?? 'information', array( 'information', 'warning', 'critical' ) );
			if ( is_wp_error( $level ) ) {
				return self::error( 'spdb_workspace_alert_level_invalid', 'A workspace alert level is invalid.' );
			}
			$message = self::safe_text_or_error( $alert['message'] ?? '', 260, 'spdb_workspace_alert_message_invalid' );
			if ( is_wp_error( $message ) ) {
				return $message;
			}
			$result[] = array(
				'provider_key' => $provider_key,
				'key'          => $key,
				'level'        => $level,
				'message'      => $message,
				'scope'        => $scope['scope'],
				'owner_user_id'=> $scope['owner_user_id'],
			);
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
		if ( is_wp_error( $eligibility ) ) {
			return self::error( 'spdb_workspace_profile_eligibility_invalid', 'A profile eligibility value is invalid.' );
		}
		$edit = self::optional_destination( $raw['edit_destination'] ?? '' );
		$public = self::optional_destination( $raw['public_destination'] ?? '' );
		if ( is_wp_error( $edit ) || is_wp_error( $public ) ) {
			return is_wp_error( $edit ) ? $edit : $public;
		}
		$timestamp = self::timestamp( $raw['source_timestamp'] ?? '' );
		if ( is_wp_error( $timestamp ) ) {
			return $timestamp;
		}
		return array(
			'provider_key'      => $provider_key,
			'owner_user_id'     => $scope['owner_user_id'],
			'completion_percent'=> $completion,
			'eligibility'       => $eligibility,
			'verification_state'=> self::optional_key( $raw['verification_state'] ?? '' ),
			'edit_destination'  => $edit,
			'public_destination'=> $public,
			'source_timestamp'  => $timestamp,
		);
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
			if ( null !== $value && ( ! is_int( $value ) || $value < 0 ) ) {
				return self::error( 'spdb_workspace_knowledge_count_invalid', 'Knowledge counts must be non-negative integers or unavailable.' );
			}
			$counts[ $field ] = $value;
		}
		$destination = self::optional_destination( $raw['destination'] ?? '' );
		if ( is_wp_error( $destination ) ) {
			return $destination;
		}
		$timestamp = self::timestamp( $raw['source_timestamp'] ?? '' );
		if ( is_wp_error( $timestamp ) ) {
			return $timestamp;
		}
		return array_merge(
			array(
				'provider_key'     => $provider_key,
				'owner_user_id'    => $scope['owner_user_id'],
				'destination'      => $destination,
				'source_timestamp' => $timestamp,
			),
			$counts
		);
	}

	/** @return array{scope:string,owner_user_id:int}|WP_Error */
	private static function normalize_scope( $raw_scope, $raw_owner, array $context ) {
		if ( ! is_scalar( $raw_scope ) || ! is_scalar( $raw_owner ) ) {
			return self::error( 'spdb_workspace_scope_shape_invalid', 'A workspace scope has an invalid shape.' );
		}
		$scope = (string) $raw_scope;
		if ( ! in_array( $scope, array( 'own', 'institution' ), true ) ) {
			return self::error( 'spdb_workspace_scope_invalid', 'A workspace scope is invalid.' );
		}
		$owner = (int) $raw_owner;
		$user  = (int) ( $context['user_id'] ?? 0 );
		$is_founder = ! empty( $context['is_founder'] );
		if ( 'own' === $scope && ( $user < 1 || $owner !== $user ) ) {
			return self::error( 'spdb_workspace_owner_mismatch', 'An own-scope workspace projection does not belong to the current user.' );
		}
		if ( 'institution' === $scope && ( ! $is_founder || 0 !== $owner ) ) {
			return self::error( 'spdb_workspace_institution_scope_forbidden', 'Institution scope is reserved for the server-verified Founder.' );
		}
		return array( 'scope' => $scope, 'owner_user_id' => $owner );
	}

	/** @return string|WP_Error */
	private static function canonical_key( $raw ) {
		if ( ! is_scalar( $raw ) ) {
			return self::error( 'spdb_workspace_key_shape_invalid', 'A workspace key has an invalid shape.' );
		}
		$key = (string) $raw;
		if ( ! SPDB_Adapter_Registry::is_canonical_key( $key ) || sanitize_key( $key ) !== $key ) {
			return self::error( 'spdb_workspace_key_invalid', 'A workspace key is not canonical.' );
		}
		return $key;
	}

	/** @return string|WP_Error */
	private static function exact_key( $raw, array $allowed ) {
		if ( ! is_scalar( $raw ) ) {
			return self::error( 'spdb_workspace_enum_shape_invalid', 'A workspace enum value has an invalid shape.' );
		}
		$value = (string) $raw;
		return in_array( $value, $allowed, true ) ? $value : self::error( 'spdb_workspace_enum_invalid', 'A workspace enum value is invalid.' );
	}

	private static function optional_key( $raw ): string {
		if ( ! is_scalar( $raw ) ) {
			return '';
		}
		$value = (string) $raw;
		return SPDB_Adapter_Registry::is_canonical_key( $value ) && sanitize_key( $value ) === $value ? $value : '';
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
		if ( null === $raw || '' === $raw ) {
			return '';
		}
		return SPDB_Safe_Destination::normalize( $raw );
	}

	/** @return string|WP_Error */
	private static function safe_text_or_error( $raw, int $limit, string $code, bool $allow_empty = false ) {
		if ( ! is_scalar( $raw ) ) {
			return self::error( $code, 'A workspace text field has an invalid shape.' );
		}
		$text = self::safe_text( $raw, $limit, $code );
		if ( ( ! $allow_empty && '' === $text ) || self::contains_sensitive_pattern( $text ) ) {
			return self::error( $code, 'A workspace text field is empty, invalid, or contains sensitive identifiers.' );
		}
		return $text;
	}

	private static function safe_text( $raw, int $limit, string $code ): string {
		if ( ! is_scalar( $raw ) ) {
			return '';
		}
		$text = trim( sanitize_text_field( (string) $raw ) );
		return strlen( $text ) > $limit ? substr( $text, 0, $limit ) : $text;
	}

	private static function contains_sensitive_pattern( string $text ): bool {
		if ( '' === $text ) {
			return false;
		}
		return 1 === preg_match(
			'~(?:https?://|www\.|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|\+?\d[\d\s().-]{6,}\d|(?:patient|mrn|cnic|passport)[\s:_-]*\d)~i',
			$text
		);
	}

	private static function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 422 ) );
	}
}
