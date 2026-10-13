<?php
namespace VoiceWeb_WP\Modules\Tour;

defined( 'ABSPATH' ) || exit;

final class Module {

	private PostType $post_type;
	private Taxonomies $taxonomies;
	private Roles $roles;
	private Admin $admin;

	public function __construct() {
		$this->post_type  = new PostType();
		$this->taxonomies = new Taxonomies();
		$this->roles	  = new Roles();
		$this->admin      = new Admin();
	}

	public function boot() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function register() {
		$this->post_type->register();
		$this->taxonomies->register();
		$this->roles->register();
	}

}
