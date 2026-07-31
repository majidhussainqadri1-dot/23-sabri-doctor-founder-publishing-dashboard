<?php
/**
 * Bounded repository-health acquisition boundary for Collections readiness.
 *
 * The probe acquires repository health at most once per public decision,
 * isolates exceptions and re-entrancy, and delegates all decision semantics to
 * the reviewed Phase 23K integration. It never executes metadata writes or
 * native-reference resolution.
 */
defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_Repository_Readiness_Probe {
	private ?SPDB_Collections_Repository $repository;
	private SPDB_Collections_Service_Readiness_Integration $integration;
	private SPDB_Collections_Service_Readiness_Integration $read_integration;
	private bool $evaluating = false;
	private bool $reentrant_detected = false;

	public function __construct(
		?SPDB_Collections_Repository $repository,
		SPDB_Collections_Service_Readiness_Integration $integration
	) {
		$this->repository       = $repository;
		$this->integration      = $integration;
		$this->read_integration = new SPDB_Collections_Service_Readiness_Integration(
			new SPDB_Collections_Service_Readiness_Gate()
		);
	}

	/** @param mixed $writes_configured @return array<string,mixed> */
	public function snapshot( $writes_configured ): array {
		if ( ! is_bool( $writes_configured ) ) {
			return $this->read_integration->snapshot( $writes_configured, false, array() );
		}

		$probe       = $this->probe_repository();
		$integration = $writes_configured ? $this->integration : $this->read_integration;
		return $integration->snapshot(
			$writes_configured,
			$probe['available'],
			$probe['health']
		);
	}

	/** @return true|WP_Error */
	public function require_read_ready() {
		$probe    = $this->probe_repository();
		$snapshot = $this->read_integration->snapshot( false, $probe['available'], $probe['health'] );

		if ( ! $snapshot['repository_available'] ) {
			return $this->error(
				'spdb_collections_repository_unavailable',
				'The collection metadata repository is unavailable.'
			);
		}
		if ( 'repository_health_invalid' === $snapshot['repository_code'] ) {
			return $this->error(
				'spdb_collections_repository_health_invalid',
				'The collection metadata repository returned an invalid health response.'
			);
		}
		if ( ! $snapshot['read_ready'] ) {
			return $this->error(
				'spdb_collections_repository_not_ready',
				'The collection metadata repository is not ready.'
			);
		}
		return true;
	}

	/** @param mixed $writes_configured @return true|WP_Error */
	public function require_collection_write_ready( $writes_configured ) {
		if ( ! is_bool( $writes_configured ) || false === $writes_configured ) {
			return $this->read_integration->require_collection_write_ready(
				$writes_configured,
				false,
				array()
			);
		}

		$probe = $this->probe_repository();
		return $this->read_integration->require_collection_write_ready(
			true,
			$probe['available'],
			$probe['health']
		);
	}

	/** @param mixed $writes_configured @return true|WP_Error */
	public function require_knowledge_write_ready( $writes_configured ) {
		if ( ! is_bool( $writes_configured ) || false === $writes_configured ) {
			return $this->read_integration->require_knowledge_write_ready(
				$writes_configured,
				false,
				array()
			);
		}

		$probe = $this->probe_repository();
		return $this->integration->require_knowledge_write_ready(
			true,
			$probe['available'],
			$probe['health']
		);
	}

	/** @return array{available:bool,health:mixed} */
	private function probe_repository(): array {
		if ( null === $this->repository ) {
			return array( 'available' => false, 'health' => array() );
		}

		if ( $this->evaluating ) {
			$this->reentrant_detected = true;
			return array(
				'available' => true,
				'health'    => $this->not_ready_health( 'repository_probe_reentrant' ),
			);
		}

		$this->evaluating = true;
		$this->reentrant_detected = false;
		$health = null;
		try {
			$health = $this->repository->health_check();
		} catch ( Throwable $throwable ) {
			$health = $this->not_ready_health( 'repository_probe_failed' );
		} finally {
			$reentrant = $this->reentrant_detected;
			$this->evaluating = false;
			$this->reentrant_detected = false;
		}

		if ( $reentrant ) {
			$health = $this->not_ready_health( 'repository_probe_reentrant' );
		}

		return array( 'available' => true, 'health' => $health );
	}

	/** @return array{healthy:false,database_ready:false,schema_ready:false,schema_version:string,code:string,cached_for_request:false} */
	private function not_ready_health( string $code ): array {
		return array(
			'healthy'            => false,
			'database_ready'     => false,
			'schema_ready'       => false,
			'schema_version'     => SPDB_Collections_Schema::VERSION,
			'code'               => $code,
			'cached_for_request' => false,
		);
	}

	private function error( string $code, string $message ): WP_Error {
		return new WP_Error(
			$code,
			__( $message, 'sabri-publishing-dashboard' ),
			array( 'status' => 503 )
		);
	}
}
