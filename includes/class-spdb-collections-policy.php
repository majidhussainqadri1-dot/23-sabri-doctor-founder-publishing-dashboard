<?php
/**
 * Phase 23F policy and validation boundary for cross-module metadata.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_Policy {
	public const MAX_TITLE          = 200;
	public const MAX_OBJECTIVE      = 1000;
	public const MAX_ETHICS         = 1000;
	public const MAX_CONTRIBUTORS   = 25;
	public const MAX_SURFACES       = 20;

	/** @return string[] */
	public static function record_types(): array {
		return array( 'collection', 'campaign' );
	}

	/** @return string[] */
	public static function scopes(): array {
		return array( 'own', 'institution' );
	}

	/** @return string[] */
	public static function statuses(): array {
		return array( 'draft', 'active', 'paused', 'completed', 'archived' );
	}

	/** @return string[] */
	public static function target_surfaces(): array {
		return array(
			'home', 'news', 'profile', 'learning', 'encyclopedia', 'research',
			'video', 'reels', 'pdf', 'clinical_cases', 'remedy_archive',
			'disease_archive', 'search',
		);
	}

	/** @return string[] */
	public static function knowledge_relations(): array {
		return array(
			'encyclopedia', 'learning', 'research', 'founder_book',
			'doctor_knowledge', 'clinical_case', 'remedy_archive',
			'disease_archive', 'video_series', 'pdf_series', 'timeline',
			'topic_archive', 'search_index',
		);
	}

	/**
	 * Validate File 23-owned collection/campaign metadata only.
	 * Native publication bodies and native workflow state are never accepted.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public static function validate_collection( array $input ) {
		$allowed = array(
			'record_type', 'scope', 'title', 'objective', 'ethical_declaration',
			'contributors', 'target_surfaces', 'status', 'start_at_gmt',
			'end_at_gmt', 'idempotency_key', 'audit_reason',
		);
		if ( array_diff( array_keys( $input ), $allowed ) ) {
			return self::error( 'spdb_collection_field_forbidden', 'The collection payload contains a field that File 23 does not own.' );
		}

		$type   = self::enum( $input['record_type'] ?? '', self::record_types(), 'spdb_collection_type_invalid' );
		$scope  = self::enum( $input['scope'] ?? 'own', self::scopes(), 'spdb_collection_scope_invalid' );
		$status = self::enum( $input['status'] ?? 'draft', self::statuses(), 'spdb_collection_status_invalid' );
		$title  = self::plain_text( $input['title'] ?? '', self::MAX_TITLE, false, 'spdb_collection_title_invalid' );
		$objective = self::plain_text( $input['objective'] ?? '', self::MAX_OBJECTIVE, true, 'spdb_collection_objective_invalid' );
		$ethics = self::plain_text( $input['ethical_declaration'] ?? '', self::MAX_ETHICS, true, 'spdb_collection_ethics_invalid' );
		$idempotency = self::idempotency_key( $input['idempotency_key'] ?? '' );
		$audit_reason = self::plain_text( $input['audit_reason'] ?? '', 500, false, 'spdb_collection_audit_reason_invalid' );
		foreach ( array( $type, $scope, $status, $title, $objective, $ethics, $idempotency, $audit_reason ) as $value ) {
			if ( is_wp_error( $value ) ) {
				return $value;
			}
		}
		if ( strlen( $audit_reason ) < 10 ) {
			return self::error( 'spdb_collection_audit_reason_invalid', 'A meaningful audit reason is required.' );
		}

		$contributors = self::positive_integer_list( $input['contributors'] ?? array() );
		$surfaces     = self::canonical_list( $input['target_surfaces'] ?? array(), self::target_surfaces(), self::MAX_SURFACES, 'spdb_collection_surfaces_invalid' );
		if ( is_wp_error( $contributors ) || is_wp_error( $surfaces ) ) {
			return is_wp_error( $contributors ) ? $contributors : $surfaces;
		}

		$start = self::utc_timestamp( $input['start_at_gmt'] ?? '', true );
		$end   = self::utc_timestamp( $input['end_at_gmt'] ?? '', true );
		if ( is_wp_error( $start ) || is_wp_error( $end ) ) {
			return is_wp_error( $start ) ? $start : $end;
		}
		if ( '' !== $start && '' !== $end && $start > $end ) {
			return self::error( 'spdb_collection_date_range_invalid', 'The end date cannot precede the start date.' );
		}

		if ( 'institution' === $scope && ! self::current_user_is_founder() ) {
			return self::error( 'spdb_collection_institution_forbidden', 'Institution collections and campaigns are Founder-governed.' );
		}
		if ( 'campaign' === $type ) {
			if ( ! self::current_user_is_founder() ) {
				return self::error( 'spdb_campaign_founder_required', 'Only the Founder may create an institution campaign.' );
			}
			if ( '' === $objective || '' === $ethics || '' === $start || '' === $end || array() === $surfaces ) {
				return self::error( 'spdb_campaign_metadata_incomplete', 'Campaign objective, ethical declaration, dates, and target surfaces are required.' );
			}
			if ( self::contains_prohibited_campaign_pattern( $objective . ' ' . $ethics ) ) {
				return self::error( 'spdb_campaign_ethics_invalid', 'Fear, false urgency, fake scarcity, fabricated metrics, or cure guarantees are prohibited.' );
			}
		}

		return array(
			'record_type'         => $type,
			'scope'               => $scope,
			'title'               => $title,
			'objective'           => $objective,
			'ethical_declaration' => $ethics,
			'contributors'        => $contributors,
			'target_surfaces'     => $surfaces,
			'status'              => $status,
			'start_at_gmt'        => $start,
			'end_at_gmt'          => $end,
			'idempotency_key'     => $idempotency,
			'audit_reason'        => $audit_reason,
		);
	}

	/** @return array<string,string>|WP_Error */
	public static function validate_knowledge_link( array $input ) {
		$allowed = array(
			'source_provider_key', 'source_object_type', 'source_object_id',
			'target_provider_key', 'target_object_type', 'target_object_id',
			'relation_type', 'idempotency_key', 'audit_reason',
		);
		if ( array_diff( array_keys( $input ), $allowed ) ) {
			return self::error( 'spdb_knowledge_field_forbidden', 'The knowledge-link payload contains a field that File 23 does not own.' );
		}

		$result = array(
			'source_provider_key' => self::canonical_key( $input['source_provider_key'] ?? '', 'spdb_knowledge_source_provider_invalid' ),
			'source_object_type'  => self::canonical_key( $input['source_object_type'] ?? '', 'spdb_knowledge_source_type_invalid' ),
			'source_object_id'    => self::object_id( $input['source_object_id'] ?? '', 'spdb_knowledge_source_id_invalid' ),
			target_provider_key' => self::canonical_key( $input['target_provider_key'] ?? '', 'spdb_knowledge_target_provider_invalid' ),
			'target_object_type'  => self::canonical_key( $input['target_object_type'] ?? '', 'spdb_knowledge_target_type_invalid' ),
			'target_object_id'    => self::object_id( $input['target_object_id'] ?? '', 'spdb_knowledge_target_id_invalid' ),
			'relation_type'       => self::enum( $input['relation_type'] ?? '', self::knowledge_relations(), 'spdb_knowledge_relation_invalid' ),
			'idempotency_key'     => self::idempotency_key( $input['idempotency_key'] ?? '' ),
			'audit_reason'        => self::plain_text( $input['audit_reason'] ?? '', 500, false, 'spdb_knowledge_audit_reason_invalid' ),
		);
		foreach ( $result as $value ) {
			if ( is_wp_error( $value ) ) {
				return $value;
			}
		}
		if ( strlen( $result['audit_reason'] ) < 10 ) {
			return self::error( 'spdb_knowledge_audit_reason_invalid', 'A meaningful audit reason is required.' );
		}
		if (
			$result['source_provider_key'] === $result['target_provider_key']
			&& $result['source_object_type'] === $result['target_object_type']
			&& $result['source_object_id'] === $result['target_object_id']
		) {
			return self::error( 'spdb_knowledge_self_link_invalid', 'A knowledge relationship cannot point an object to itself.' );
		}
		return $result;
	}

	private static function current_user_is_founder(): bool {
		$user_id = get_current_user_id();
		return $user_id > 0
			&& SPDB_Membership_Guard::is_user_approved( $user_id )
			&& function_exists( 'smc_is_founder' )
			&& smc_is_founder( $user_id );
	}

	private static function contains_prohibited_campaign_pattern( string $value ): bool {
		return 1 === preg_match( '/\b(?:guaranteed cure|cure guaranteed|limited seats only|act now|last chance|100% success|fear of death|miracle cure)\b/i', $value );
	}

	/** @return string|WP_Error */
	private static function enum( $raw, array $allowed, string $code ) {
		$value = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		return in_array( $value, $allowed, true ) ? $value : self::error( $code, 'A metadata enumeration value is invalid.' );
	}

	/** @return string|WP_Error */
	private static function canonical_key( $raw, string $code ) {
		$value = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		return SPDB_Adapter_Registry::is_canonical_key( $value ) ? $value : self::error( $code, 'A canonical metadata key is invalid.' );
	}

	/** @return string|WP_Error */
	private static function object_id( $raw, string $code ) {
		$value = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		return SPDB_Projection_Validator::valid_object_id( $value ) ? $value : self::error( $code, 'A canonical object identifier is invalid.' );
	}

	/** @return string|WP_Error */
	private static function plain_text( $raw, int $maximum, bool $allow_empty, string $code ) {
		if ( ! is_scalar( $raw ) ) {
			return self::error( $code, 'A metadata text field has an invalid shape.' );
		}
		$value = trim( (string) $raw );
		if ( ( ! $allow_empty && '' === $value ) || strlen( $value ) > $maximum || wp_strip_all_tags( $value ) !== $value ) {
			return self::error( $code, 'A metadata text field is invalid.' );
		}
		if ( preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value ) || preg_match( '/(?:https?:\/\/|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|\b(?:\+?92|0)?3\d{9}\b|\b\d{5}-\d{7}-\d\b)/i', $value ) ) {
			return self::error( $code, 'A metadata text field contains prohibited sensitive data.' );
		}
		return $value;
	}

	/** @return string|WP_Error */
	private static function idempotency_key( $raw ) {
		$value = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		return 1 === preg_match( '/^[A-Za-z0-9._:-]{16,128}$/', $value ) ? $value : self::error( 'spdb_idempotency_key_invalid', 'A canonical idempotency key is required.' );
	}

	/** @return string|WP_Error */
	private static function utc_timestamp( $raw, bool $allow_empty ) {
		$value = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		if ( '' === $value && $allow_empty ) {
			return '';
		}
		if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value ) ) {
			return self::error( 'spdb_collection_timestamp_invalid', 'Dates must use canonical RFC 3339 UTC.' );
		}
		try {
			$date = new DateTimeImmutable( $value );
		} catch ( Throwable $throwable ) {
			return self::error( 'spdb_collection_timestamp_invalid', 'A collection date is invalid.' );
		}
		return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d\TH:i:s\Z' ) === $value
			? $value
			: self::error( 'spdb_collection_timestamp_invalid', 'A collection date is not canonical UTC.' );
	}

	/** @return int[]|WP_Error */
	private static function positive_integer_list( $raw ) {
		if ( ! is_array( $raw ) || count( $raw ) > self::MAX_CONTRIBUTORS || array_keys( $raw ) !== range( 0, count( $raw ) - 1 ) ) {
			return self::error( 'spdb_collection_contributors_invalid', 'The contributor list is malformed or oversized.' );
		}
		$result = array();
		foreach ( $raw as $item ) {
			$value = is_int( $item ) ? $item : ( is_string( $item ) && ctype_digit( $item ) ? (int) $item : 0 );
			if ( $value < 1 || in_array( $value, $result, true ) ) {
				return self::error( 'spdb_collection_contributors_invalid', 'The contributor list contains an invalid or duplicate user identifier.' );
			}
			$result[] = $value;
		}
		return $result;
	}

	/** @return string[]|WP_Error */
	private static function canonical_list( $raw, array $allowed, int $maximum, string $code ) {
		if ( ! is_array( $raw ) || count( $raw ) > $maximum || array_keys( $raw ) !== range( 0, count( $raw ) - 1 ) ) {
			return self::error( $code, 'A canonical metadata list is malformed or oversized.' );
		}
		$result = array();
		foreach ( $raw as $item ) {
			$value = is_scalar( $item ) ? trim( (string) $item ) : '';
			if ( ! in_array( $value, $allowed, true ) || in_array( $value, $result, true ) ) {
				return self::error( $code, 'A canonical metadata list contains an invalid or duplicate value.' );
			}
			$result[] = $value;
		}
		return $result;
	}

	private static function error( string $code, string $message ): WP_Error {
		return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => 422 ) );
	}
}
