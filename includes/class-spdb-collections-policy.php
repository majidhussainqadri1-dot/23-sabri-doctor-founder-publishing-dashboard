<?php
/** Request-shape and current-user policy for File 23-owned metadata. */
defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_Policy {
	public static function record_types(): array { return array( 'collection', 'campaign' ); }
	public static function scopes(): array { return array( 'own', 'institution' ); }
	public static function collection_statuses(): array { return array( 'draft', 'active', 'archived' ); }
	public static function campaign_statuses(): array { return array( 'draft', 'active', 'paused', 'completed', 'archived' ); }
	public static function target_surfaces(): array { return array( 'home', 'news', 'profile', 'learning', 'encyclopedia', 'research', 'video', 'reels', 'pdf', 'clinical_cases', 'remedy_archive', 'disease_archive', 'search' ); }
	public static function knowledge_relations(): array { return array( 'encyclopedia', 'learning', 'research', 'founder_book', 'doctor_knowledge', 'clinical_case', 'remedy_archive', 'disease_archive', 'video_series', 'pdf_series', 'timeline', 'topic_archive', 'search_index' ); }

	/** @return array<string,mixed>|WP_Error */
	public static function validate_collection( array $input ) {
		$allowed = array( 'record_type', 'scope', 'title', 'objective', 'ethical_declaration', 'contributors', 'target_surfaces', 'status', 'start_at_gmt', 'end_at_gmt', 'idempotency_key', 'audit_reason' );
		if ( array_diff( array_keys( $input ), $allowed ) ) { return self::error( 'spdb_collection_field_forbidden', 'The collection payload contains a field that File 23 does not own.' ); }
		$record_type = self::enum_value( $input['record_type'] ?? '', self::record_types(), 'spdb_collection_type_invalid' );
		$scope       = self::enum_value( $input['scope'] ?? '', self::scopes(), 'spdb_collection_scope_invalid' );
		if ( is_wp_error( $record_type ) ) { return $record_type; }
		if ( is_wp_error( $scope ) ) { return $scope; }
		$authority = self::mutation_authority( $record_type, $scope ); if ( is_wp_error( $authority ) ) { return $authority; }
		$statuses = 'campaign' === $record_type ? self::campaign_statuses() : self::collection_statuses();
		$values = array(
			'record_type' => $record_type,
			'scope' => $scope,
			'title' => self::plain_text( $input['title'] ?? '', 200, false, 'spdb_collection_title_invalid' ),
			'objective' => self::plain_text( $input['objective'] ?? '', 1000, true, 'spdb_collection_objective_invalid' ),
			'ethical_declaration' => self::plain_text( $input['ethical_declaration'] ?? '', 1000, true, 'spdb_collection_ethics_invalid' ),
			'contributors' => self::positive_integer_list( $input['contributors'] ?? array(), 25, 'spdb_collection_contributors_invalid' ),
			'target_surfaces' => self::canonical_list( $input['target_surfaces'] ?? array(), self::target_surfaces(), 20, 'spdb_collection_surfaces_invalid' ),
			'status' => self::enum_value( $input['status'] ?? 'draft', $statuses, 'spdb_collection_status_invalid' ),
			'start_at_gmt' => self::utc_timestamp( $input['start_at_gmt'] ?? '' ),
			'end_at_gmt' => self::utc_timestamp( $input['end_at_gmt'] ?? '' ),
			'idempotency_key' => self::idempotency_key( $input['idempotency_key'] ?? '' ),
			'audit_reason' => self::plain_text( $input['audit_reason'] ?? '', 500, false, 'spdb_collection_audit_reason_invalid' ),
		);
		foreach ( $values as $value ) { if ( is_wp_error( $value ) ) { return $value; } }
		if ( self::text_length( $values['audit_reason'] ) < 10 ) { return self::error( 'spdb_collection_audit_reason_invalid', 'A meaningful audit reason is required.' ); }
		if ( '' !== $values['start_at_gmt'] && '' !== $values['end_at_gmt'] && $values['start_at_gmt'] > $values['end_at_gmt'] ) { return self::error( 'spdb_collection_date_range_invalid', 'The end date cannot precede the start date.' ); }
		if ( 'collection' === $record_type ) {
			if ( '' !== $values['ethical_declaration'] || array() !== $values['target_surfaces'] || '' !== $values['start_at_gmt'] || '' !== $values['end_at_gmt'] ) { return self::error( 'spdb_collection_campaign_fields_forbidden', 'Campaign-only ethics, target, and schedule fields are not permitted on a collection.' ); }
			if ( 'own' === $scope && array() !== $values['contributors'] ) { return self::error( 'spdb_collection_contributors_forbidden', 'An own-scope collection cannot delegate contributor authority.' ); }
		}
		if ( 'campaign' === $record_type ) {
			if ( 'institution' !== $scope || '' === $values['objective'] || '' === $values['ethical_declaration'] || '' === $values['start_at_gmt'] || '' === $values['end_at_gmt'] || array() === $values['target_surfaces'] ) { return self::error( 'spdb_campaign_metadata_incomplete', 'A campaign requires institution scope, objective, ethical declaration, dates, and target surfaces.' ); }
			if ( self::contains_prohibited_campaign_pattern( $values['title'] . ' ' . $values['objective'] . ' ' . $values['ethical_declaration'] ) ) { return self::error( 'spdb_campaign_ethics_invalid', 'Fear, false urgency, fake scarcity, fabricated metrics, miracle claims, and cure guarantees are prohibited.' ); }
		}
		return $values;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function validate_knowledge_link( array $input ) {
		$allowed = array( 'scope', 'source_provider_key', 'source_object_type', 'source_object_id', 'target_provider_key', 'target_object_type', 'target_object_id', 'relation_type', 'idempotency_key', 'audit_reason' );
		if ( array_diff( array_keys( $input ), $allowed ) ) { return self::error( 'spdb_knowledge_field_forbidden', 'The knowledge-link payload contains a field that File 23 does not own.' ); }
		$scope = self::enum_value( $input['scope'] ?? '', self::scopes(), 'spdb_knowledge_scope_invalid' ); if ( is_wp_error( $scope ) ) { return $scope; }
		$authority = self::mutation_authority( 'knowledge_link', $scope ); if ( is_wp_error( $authority ) ) { return $authority; }
		$values = array(
			'scope' => $scope,
			'source_provider_key' => self::canonical_key( $input['source_provider_key'] ?? '', 'spdb_knowledge_source_provider_invalid' ),
			'source_object_type' => self::canonical_key( $input['source_object_type'] ?? '', 'spdb_knowledge_source_type_invalid' ),
			'source_object_id' => self::object_id( $input['source_object_id'] ?? '', 'spdb_knowledge_source_id_invalid' ),
			'target_provider_key' => self::canonical_key( $input['target_provider_key'] ?? '', 'spdb_knowledge_target_provider_invalid' ),
			'target_object_type' => self::canonical_key( $input['target_object_type'] ?? '', 'spdb_knowledge_target_type_invalid' ),
			'target_object_id' => self::object_id( $input['target_object_id'] ?? '', 'spdb_knowledge_target_id_invalid' ),
			'relation_type' => self::enum_value( $input['relation_type'] ?? '', self::knowledge_relations(), 'spdb_knowledge_relation_invalid' ),
			'idempotency_key' => self::idempotency_key( $input['idempotency_key'] ?? '' ),
			'audit_reason' => self::plain_text( $input['audit_reason'] ?? '', 500, false, 'spdb_knowledge_audit_reason_invalid' ),
		);
		foreach ( $values as $value ) { if ( is_wp_error( $value ) ) { return $value; } }
		if ( self::text_length( $values['audit_reason'] ) < 10 ) { return self::error( 'spdb_knowledge_audit_reason_invalid', 'A meaningful audit reason is required.' ); }
		if ( $values['source_provider_key'] === $values['target_provider_key'] && $values['source_object_type'] === $values['target_object_type'] && $values['source_object_id'] === $values['target_object_id'] ) { return self::error( 'spdb_knowledge_self_link_invalid', 'A knowledge relationship cannot point an object to itself.' ); }
		return $values;
	}

	/** @return true|WP_Error */
	private static function mutation_authority( string $record_type, string $scope ) {
		$user_id = get_current_user_id();
		if ( $user_id < 1 || ! SPDB_Membership_Guard::is_user_approved( $user_id ) ) { return self::error( 'spdb_collections_account_forbidden', 'An approved current account is required for collection metadata changes.', 403 ); }
		if ( 'institution' === $scope || 'campaign' === $record_type ) {
			return self::current_user_is_founder() && SPDB_Capabilities::current_user_can( 'spdb_manage_campaigns' ) ? true : self::error( 'spdb_collections_institution_forbidden', 'Institution metadata is Founder-governed and requires the campaign-management capability.', 403 );
		}
		return SPDB_Capabilities::current_user_can( 'spdb_manage_own_content' ) ? true : self::error( 'spdb_collections_own_forbidden', 'The current account is not authorized to manage own-scope collection metadata.', 403 );
	}
	private static function current_user_is_founder(): bool { $user_id = get_current_user_id(); return $user_id > 0 && SPDB_Membership_Guard::is_user_approved( $user_id ) && function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id ); }
	private static function contains_prohibited_campaign_pattern( string $value ): bool { return 1 === preg_match( '/\b(?:guarantee(?:d|s)?\s+(?:a\s+)?cure|cure\s+guarantee(?:d|s)?|miracle\s+cure|instant\s+cure|100%\s+(?:success|effective)|no\s+risk|limited\s+(?:seats|time|stock)\s+only|act\s+now|last\s+chance|do\s+not\s+miss|fear\s+of\s+death|everyone\s+is\s+buying|doctor(?:s)?\s+do\s+not\s+want\s+you\s+to\s+know)\b/i', $value ); }
	private static function enum_value( $raw, array $allowed, string $code ) { $value = is_scalar( $raw ) ? trim( (string) $raw ) : ''; return in_array( $value, $allowed, true ) ? $value : self::error( $code, 'A metadata enumeration value is invalid.' ); }
	private static function canonical_key( $raw, string $code ) { $value = is_scalar( $raw ) ? trim( (string) $raw ) : ''; return SPDB_Adapter_Registry::is_canonical_key( $value ) ? $value : self::error( $code, 'A canonical metadata key is invalid.' ); }
	private static function object_id( $raw, string $code ) { $value = is_scalar( $raw ) ? trim( (string) $raw ) : ''; return SPDB_Projection_Validator::valid_object_id( $value ) ? $value : self::error( $code, 'A canonical object identifier is invalid.' ); }
	private static function plain_text( $raw, int $maximum, bool $allow_empty, string $code ) {
		if ( ! is_scalar( $raw ) ) { return self::error( $code, 'A metadata text field has an invalid shape.' ); }
		$value = trim( (string) $raw );
		if ( ( ! $allow_empty && '' === $value ) || self::text_length( $value ) > $maximum || wp_strip_all_tags( $value ) !== $value ) { return self::error( $code, 'A metadata text field is invalid.' ); }
		if ( preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value ) || preg_match( '/(?:https?:\/\/|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}|\b(?:\+?92|0)?3\d{9}\b|\b\d{5}-\d{7}-\d\b)/iu', $value ) ) { return self::error( $code, 'A metadata text field contains prohibited sensitive data.' ); }
		return $value;
	}
	private static function idempotency_key( $raw ) { $value = is_scalar( $raw ) ? trim( (string) $raw ) : ''; return 1 === preg_match( '/^[A-Za-z0-9._:-]{16,128}$/', $value ) ? $value : self::error( 'spdb_idempotency_key_invalid', 'A canonical idempotency key is required.' ); }
	private static function utc_timestamp( $raw ) {
		$value = is_scalar( $raw ) ? trim( (string) $raw ) : ''; if ( '' === $value ) { return ''; }
		if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value ) ) { return self::error( 'spdb_collection_timestamp_invalid', 'Dates must use canonical RFC 3339 UTC.' ); }
		try { $date = new DateTimeImmutable( $value ); } catch ( Throwable $throwable ) { return self::error( 'spdb_collection_timestamp_invalid', 'A collection date is invalid.' ); }
		return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d\TH:i:s\Z' ) === $value ? $value : self::error( 'spdb_collection_timestamp_invalid', 'A collection date is not canonical UTC.' );
	}
	private static function positive_integer_list( $raw, int $maximum, string $code ) {
		if ( ! is_array( $raw ) || ! self::is_list( $raw ) || count( $raw ) > $maximum ) { return self::error( $code, 'The contributor list is malformed or oversized.' ); }
		$result = array(); foreach ( $raw as $item ) { $value = is_int( $item ) ? $item : ( is_string( $item ) && ctype_digit( $item ) ? (int) $item : 0 ); if ( $value < 1 || in_array( $value, $result, true ) ) { return self::error( $code, 'The contributor list contains an invalid or duplicate user identifier.' ); } $result[] = $value; } return $result;
	}
	private static function canonical_list( $raw, array $allowed, int $maximum, string $code ) {
		if ( ! is_array( $raw ) || ! self::is_list( $raw ) || count( $raw ) > $maximum ) { return self::error( $code, 'A canonical metadata list is malformed or oversized.' ); }
		$result = array(); foreach ( $raw as $item ) { $value = is_scalar( $item ) ? trim( (string) $item ) : ''; if ( ! in_array( $value, $allowed, true ) || in_array( $value, $result, true ) ) { return self::error( $code, 'A canonical metadata list contains an invalid or duplicate value.' ); } $result[] = $value; } return $result;
	}
	private static function is_list( array $value ): bool { $expected = 0; foreach ( $value as $key => $unused ) { if ( $key !== $expected ) { return false; } ++$expected; } return true; }
	private static function text_length( string $value ): int { if ( function_exists( 'mb_strlen' ) ) { return mb_strlen( $value, 'UTF-8' ); } $count = preg_match_all( '/./us', $value, $matches ); return false === $count ? strlen( $value ) : $count; }
	private static function error( string $code, string $message, int $status = 422 ): WP_Error { return new WP_Error( $code, __( $message, 'sabri-publishing-dashboard' ), array( 'status' => $status ) ); }
}
