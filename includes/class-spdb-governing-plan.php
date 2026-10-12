<?php
/**
 * Executable File 23 mapping for the 6–10 August 2026 governing plans.
 *
 * This class is a compliance/traceability contract, not a second source of
 * domain truth. Canonical domain ownership remains with the numbered owner
 * modules and every native mutation still passes through their adapters.
 *
 * @package Sabri_Publishing_Dashboard
 */

defined( 'ABSPATH' ) || exit;

final class SPDB_Governing_Plan {
	public const REVISION               = '2026-08-10';
	public const PRIMARY_GREEN_FALLBACK = '#087A4E';
	public const FILE_COUNT_MAX          = 26;

	/**
	 * The exact 70 Continuous Value requirements imported by the amended File 23
	 * plan: CV-050–CV-062 and CV-229–CV-285.
	 *
	 * Presence here means File 23 must own, consume, guard, or evidence the
	 * requirement according to canonical ownership; it never implies duplicate
	 * ownership of another module's data or workflow.
	 *
	 * @return string[]
	 */
	public static function inherited_requirements(): array {
		$ids = array();
		foreach ( array( array( 50, 62 ), array( 229, 285 ) ) as $range ) {
			for ( $number = $range[0]; $number <= $range[1]; ++$number ) {
				$ids[] = sprintf( 'CV-%03d', $number );
			}
		}
		return $ids;
	}

	/** @return string[] */
	public static function acceptance_journeys(): array {
		return array(
			'AJ-06', 'AJ-09', 'AJ-10', 'AJ-24', 'AJ-25', 'AJ-26',
			'AJ-31', 'AJ-32', 'AJ-33', 'AJ-34', 'AJ-35', 'AJ-36',
			'AJ-37', 'AJ-38', 'AJ-39', 'AJ-40',
		);
	}

	/** @return array<string,string> */
	public static function canonical_owners(): array {
		return array(
			'identity_authorization' => 'file00',
			'profile_truth'          => 'file03',
			'learning_truth'         => 'file05',
			'knowledge_truth'        => 'file06',
			'verification_truth'     => 'file09',
			'video_truth'            => 'file10',
			'reel_truth'             => 'file11',
			'pdf_truth'              => 'file12',
			'communication_truth'    => 'file17',
			'marketplace_truth'      => 'file18',
			'notifications'          => 'file19',
			'application_shell'      => 'file20',
			'publication_truth'      => 'file21',
			'composer'               => 'file22',
			'publishing_dashboard'   => 'file23',
			'assurance'              => 'file24',
			'visual_tokens'          => 'file25',
			'search_discovery'       => 'file26',
		);
	}

	/** @return array<string,string|bool> */
	public static function guardrails(): array {
		return array(
			'one_canonical_owner'        => true,
			'direct_domain_table_write'  => false,
			'duplicate_domain_truth'     => false,
			'single_free_tier'           => true,
			'donor_advantage'            => false,
			'paid_or_donor_ranking_bias' => false,
			'ai_clinical_authority'      => false,
			'primary_green'              => self::PRIMARY_GREEN_FALLBACK,
			'file26_enabled'             => true,
			'staging_first'              => true,
			'two_fresh_reviews'          => true,
		);
	}

	/** @return array<string,string> */
	public static function studios(): array {
		return array(
			'founder' => 'File 00 Founder identity + native owner adapters',
			'doctor'  => 'File 00 approved/verified account + native owner adapters',
			'teacher' => 'explicit spdb_view_teacher_studio capability + native owner adapters',
			'admin'   => 'explicit spdb_view_admin_studio capability + native owner adapters',
		);
	}
}
