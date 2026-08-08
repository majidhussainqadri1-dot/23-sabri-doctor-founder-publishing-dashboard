<?php
/**
 * Complete File 00–26 dependency and assurance manifest for File 23.
 *
 * The manifest is discovery evidence only. It never grants authorization,
 * accepts an adapter, or treats availability as permission.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Module_Manifest {
	/** @return array<int,array<string,mixed>> */
	public static function snapshot( SPDB_Adapter_Registry $registry ): array {
		$definitions = self::definitions();
		$providers   = array();
		foreach ( $registry->all() as $provider_key => $adapter ) {
			$metadata = $registry->metadata( $provider_key );
			if ( ! is_array( $metadata ) ) {
				continue;
			}
			$providers[ $provider_key ] = array(
				'version'             => sanitize_text_field( (string) ( $metadata['provider_version'] ?? '' ) ),
				'acceptance'          => sanitize_key( (string) ( $metadata['acceptance_state'] ?? 'unknown' ) ),
				'effective_state'     => sanitize_key( (string) $registry->get_effective_state( $provider_key ) ),
				'declared_capability' => sanitize_key( (string) ( $metadata['declared_capability'] ?? 'unknown' ) ),
			);
		}

		$out = array();
		foreach ( $definitions as $file_number => $definition ) {
			$detected = false;
			$version  = '';
			$state    = 'unknown';
			foreach ( $providers as $provider_key => $provider ) {
				foreach ( $definition['provider_patterns'] as $pattern ) {
					if ( '' !== $pattern && false !== strpos( $provider_key, $pattern ) ) {
						$detected = true;
						$version  = $provider['version'];
						$state    = $provider['effective_state'];
						break 2;
					}
				}
			}
			if ( '00' === $file_number ) {
				$detected = SPDB_Membership_Guard::is_available();
				$state    = $detected ? 'compatible' : 'unavailable';
			}
			if ( '23' === $file_number ) {
				$detected = true;
				$version  = SPDB_VERSION;
				$state    = 'native_owner';
			}
			$display_number = str_starts_with( $file_number, '01-' ) ? strtoupper( $file_number ) : $file_number;
			$out[] = array(
				'file'                 => $display_number,
				'name'                 => $definition['name'],
				'relationship'         => $definition['relationship'],
				'required_level'       => $definition['required_level'],
				'detected'             => $detected,
				'version'              => $version,
				'effective_state'      => $state,
				'authorization_source' => $definition['authorization_source'],
			);
		}
		return $out;
	}

	/** @return array<string,mixed> */
	public static function assurance( SPDB_Adapter_Registry $registry, SPDB_Operations_Repository $repository ): array {
		$operations = $repository->health_check();
		$audit      = $repository->audit_health();
		$errors     = 0;
		foreach ( $registry->registration_errors() as $provider_errors ) {
			$errors += is_array( $provider_errors ) ? count( $provider_errors ) : 0;
		}
		return array(
			'module_key'               => 'file23_publishing_dashboard',
			'module_version'           => SPDB_VERSION,
			'contract_version'         => SPDB_CONTRACT_VERSION,
			'private_route'            => '/publishing-dashboard/',
			'cache_policy'             => 'private_no_store',
			'index_policy'             => 'noindex_noarchive',
			'authorization_owner'      => 'file00',
			'global_safe_mode_owner'   => 'file20',
			'public_visual_owner'      => 'file25',
			'native_publication_owner' => 'file21',
			'composer_owner'           => 'file22',
			'native_data_copied'       => false,
			'patient_content_stored'   => false,
			'raw_analytics_stored'     => false,
			'operations_schema_ready'  => ! empty( $operations['healthy'] ),
			'audit_chain_healthy'      => ! empty( $audit['healthy'] ),
			'provider_errors'          => $errors,
			'generated_at_gmt'         => gmdate( 'c' ),
		);
	}

	/** @return array<string,array<string,mixed>> */
	private static function definitions(): array {
		return array(
			'00'   => self::definition( 'Sabri Membership Core', 'identity and authorization assertions', 'hard', 'file00', array( 'file00', 'membership' ) ),
			'01-a' => self::definition( 'Definitive Master Plan and Product Constitution', 'numbering, ownership, release law and change control', 'governing', 'file01-a', array( 'file01-a', 'master_plan', 'governance' ) ),
			'01-b' => self::definition( 'Platform Foundation and Contract Registry', 'module registry, contract discovery, routes and activation conventions', 'core', 'file01-b', array( 'file01-b', 'foundation', 'contract_registry' ) ),
			'02'   => self::definition( 'Authentication and Accounts', 'authenticated session entry', 'core', 'file00', array( 'file02', 'authentication' ) ),
			'03'   => self::definition( 'Profiles and Doctors', 'identity projection and native edit route', 'domain', 'file03', array( 'file03', 'profile' ) ),
			'04'   => self::definition( 'Legacy Publishing Adapter', 'migration diagnostics only', 'legacy', 'file21', array( 'file04', 'legacy' ) ),
			'05'   => self::definition( 'Learn Sabri Classical Homeopathy', 'learning publication projections', 'domain', 'file05', array( 'file05', 'learn' ) ),
			'06'   => self::definition( 'Homeopathy Encyclopedia', 'knowledge entries and corrections', 'domain', 'file06', array( 'file06', 'encyclopedia' ) ),
			'07'   => self::definition( 'Doctors Directory and Discovery', 'public doctor destination', 'domain', 'file07', array( 'file07', 'doctor_directory' ) ),
			'08'   => self::definition( 'Worldwide Clinic and Appointments', 'clinic and appointment context', 'domain', 'file08', array( 'file08', 'clinic', 'appointment' ) ),
			'09'   => self::definition( 'Doctor Onboarding and Verification', 'publishing eligibility projection', 'domain', 'file09', array( 'file09', 'verification' ) ),
			'10'   => self::definition( 'Video Wall and Live Broadcasting', 'video projections and native routes', 'domain', 'file10', array( 'file10', 'video' ) ),
			'11'   => self::definition( 'Reels', 'reel projections and native routes', 'domain', 'file11', array( 'file11', 'reel' ) ),
			'12'   => self::definition( 'PDF Library', 'document projections and native routes', 'domain', 'file12', array( 'file12', 'pdf' ) ),
			'13'   => self::definition( 'Welcome Intro Animation', 'route-suppression diagnostic only', 'optional', 'file13', array( 'file13', 'intro' ) ),
			'14'   => self::definition( 'Global Clinic USP', 'campaign destination health', 'optional', 'file14', array( 'file14', 'clinic_usp' ) ),
			'15'   => self::definition( 'Radar and Trend Intelligence', 'research and trend projections', 'domain', 'file15', array( 'file15', 'radar' ) ),
			'16'   => self::definition( 'Sabri Classical Homeopathy AI', 'source-linked assistance only', 'optional', 'file16', array( 'file16', 'ai' ) ),
			'17'   => self::definition( 'Communication Network', 'community and message context links', 'domain', 'file17', array( 'file17', 'communication', 'network' ) ),
			'18'   => self::definition( 'Marketplace', 'listing and deal context links', 'domain', 'file18', array( 'file18', 'marketplace' ) ),
			'19'   => self::definition( 'Unified Notifications and Alerts', 'single notification delivery owner', 'full', 'file19', array( 'file19', 'notification' ) ),
			'20'   => self::definition( 'Unified Application Shell', 'route, layout, global Safe Mode and rollback', 'core', 'file20', array( 'file20', 'shell' ) ),
			'21'   => self::definition( 'Home and News', 'canonical publication, review and interactions', 'core', 'file21', array( 'file21', 'home_news', 'social_publication' ) ),
			'22'   => self::definition( 'Universal Post Composer', 'canonical create and edit orchestration', 'core', 'file22', array( 'file22', 'composer' ) ),
			'23'   => self::definition( 'Publishing Dashboard', 'federated operational metadata and UI', 'native', 'file23', array( 'file23', 'spdb' ) ),
			'24'   => self::definition( 'Security, Privacy, Compliance and Resilience', 'sanitized assurance evidence', 'full', 'file24', array( 'file24', 'security' ) ),
			'25'   => self::definition( 'Global Visual Experience', 'public visual and timeline destinations', 'full', 'file25', array( 'file25', 'visual' ) ),
			'26'   => self::definition( 'Search, Discovery and Ranking', 'federated search/ranking destinations and index-contract discovery', 'domain', 'file26', array( 'file26', 'search', 'discovery', 'ranking' ) ),
		);
	}

	/** @param string[] $patterns @return array<string,mixed> */
	private static function definition( string $name, string $relationship, string $level, string $owner, array $patterns ): array {
		return array(
			'name'                 => $name,
			'relationship'         => $relationship,
			'required_level'       => $level,
			'authorization_source' => $owner,
			'provider_patterns'    => $patterns,
		);
	}
}
