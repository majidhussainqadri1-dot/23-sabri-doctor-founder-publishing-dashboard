<?php
/**
 * Exact dependency composition for later Collections service probe consumption.
 *
 * This binding constructs one private service and its readiness chain from the
 * same repository/resolver pair. It exposes only reviewed readiness decisions;
 * the raw legacy service remains private until the service itself consumes the
 * probe in a separately reviewed phase.
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

	private function __clone() {}

	/** @return never */
	public function __serialize(): array {
		throw new LogicException( 'Collections service probe bindings cannot be serialized.' );
	}

	/** @param array<string,mixed> $data @return never */
	public function __unserialize( array $data ): void {
		throw new LogicException( 'Collections service probe bindings cannot be unserialized.' );
	}

	public static function create(
		?SPDB_Collections_Repository $repository,
		?SPDB_Native_Reference_Resolver $resolver
	): self {
		$service     = new SPDB_Collections_Service( $repository, $resolver );
		$gate        = new SPDB_Collections_Service_Readiness_Gate( $resolver );
		$integration = new SPDB_Collections_Service_Readiness_Integration( $gate );
		$probe       = new SPDB_Collections_Repository_Readiness_Probe( $repository, $integration );

		return new self( $service, $probe );
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
