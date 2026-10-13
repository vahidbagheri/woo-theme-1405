<?php
namespace Citynet_WP\Modules\App;

defined( 'ABSPATH' ) || exit;

final class Module {
	public const KEY = 'citynet-app';

	private Hooks $hooks;

	public function __construct() {
		$this->hooks = new Hooks();
	}

	public function boot(): void {
		$this->hooks->register();
	}
}