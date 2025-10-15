<?php
/**
 * Dependency Injection Container
 *
 * @package SEOGenerator
 */

namespace SEOGenerator;

defined( 'ABSPATH' ) || exit;

/**
 * Simple dependency injection container.
 */
class Container {
	/**
	 * Container bindings.
	 *
	 * @var array
	 */
	private $bindings = array();

	/**
	 * Container instances.
	 *
	 * @var array
	 */
	private $instances = array();

	/**
	 * Register a binding in the container.
	 *
	 * @param string $key The binding key.
	 * @param string $className The class name to bind.
	 * @return void
	 */
	public function register( string $key, string $className ): void {
		$this->bindings[ $key ] = $className;
	}

	/**
	 * Get an instance from the container.
	 *
	 * @param string $key The binding key.
	 * @return object
	 * @throws \Exception If binding not found.
	 */
	public function get( string $key ): object {
		// Return cached instance if exists.
		if ( isset( $this->instances[ $key ] ) ) {
			return $this->instances[ $key ];
		}

		if ( ! isset( $this->bindings[ $key ] ) ) {
			throw new \Exception( "Binding not found: {$key}" );
		}

		// Create new instance.
		$className = $this->bindings[ $key ];
		$instance  = new $className();

		// Cache the instance.
		$this->instances[ $key ] = $instance;

		return $instance;
	}

	/**
	 * Set an instance directly in the container.
	 *
	 * @param string $key The binding key.
	 * @param object $instance The instance to store.
	 * @return void
	 */
	public function set( string $key, object $instance ): void {
		$this->instances[ $key ] = $instance;
	}

	/**
	 * Check if a binding exists.
	 *
	 * @param string $key The binding key.
	 * @return bool
	 */
	public function has( string $key ): bool {
		return isset( $this->bindings[ $key ] );
	}
}
