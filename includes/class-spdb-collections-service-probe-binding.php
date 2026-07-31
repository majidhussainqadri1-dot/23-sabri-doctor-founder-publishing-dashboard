<?php
/**
 * Exact dependency composition for later Collections service probe consumption.
 *
 * This binding constructs the service and its readiness chain from one
 * repository/resolver pair. It does not modify the service, execute service
 * mutations, resolve native objects, or wire the plugin container.
 */
defined( 'ABSPATH' ) || exit;

final class SPDB_Collections_Service_Probe_Binding {
	private SPDB_Collections_Service $service;
	private SPDB_Collections_Repository_Readiness_Probe $probe;

	private function __construct(
		SPDB_Collections_Service $service,
		SPDB_Collections_Repository_Readiness_Probe $probe
	) {
		$this->service = $service;
		$this->probe   = $probe;
	}

	public static function create(
		?SPDB_Collections_Repository $repository,
		?SPDB_Native_Reference_Resolver $resolver
	): self {
		$service = new SPDB_Collections_Service( $repository, $resolver );
		$gate = new SPDB_Collections_Service_Readiness_Gate( $resolver );
		$integration = new SPDB_Collections_Service_Readiness_Integration( $gate );
		$probe = new SPDB_Collections_Repository_Readiness_Probe( $repository, $integration );

		return new self( $service, $probe );
	}

	public function service(): SPDB_Collections_Service {
		return $this->service;
	}

	/** @param mixed $writes_configured @return array<string,mixed> */
	public function readiness_snapshot( $writes_configured ): array {
		return $this->probe->snapshot( $writes_configured );
	}

	/** @return true|WP_Error */
	public function require_read_ready() {
		return $this->probe->require_read_ready();
	}

	/** @param mixed $writes_configured @return true|WP_Error */
	public function require_collection_write_ready( $writes_configured ) {
		return $this->probe->require_collection_write_ready( $writes_configured );
	}

	/** @param mixed $writes_configured @return true|WP_Error */
	public function require_knowledge_write_ready( $writes_configured ) {
		return $this->probe->require_knowledge_write_ready( $writes_configured );
	}
}
