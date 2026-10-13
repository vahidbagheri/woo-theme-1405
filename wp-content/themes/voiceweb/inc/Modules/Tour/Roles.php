<?php
namespace VoiceWeb_WP\Modules\Tour;

use WP_Role;

defined( 'ABSPATH' ) || exit;

final class Roles {

    private const VERSION        = '1.0.0';
    private const VERSION_OPTION = '_cn_tour_module_roles_version';

    public static function register() {
        if ( get_option( self::VERSION_OPTION ) === self::VERSION ) return;

        $roles = self::get_roles();
        if ( empty( $roles ) ) return;

        foreach ( $roles as $role_key => $display_name ) {
            $role         = get_role( $role_key );
            $capabilities = self::get_role_capabilities( $role_key );

            if ( ! $role instanceof \WP_Role && $role_key !== 'administrator' ) {
                $role = add_role(
                    sanitize_key( (string) $role_key ),
                    sanitize_text_field( (string) $display_name ),
                    $capabilities
                );
                continue;
            }

            if ( empty( $capabilities ) ) continue;

            foreach ( $capabilities as $cap => $grant ) {
                $role->add_cap(
                    sanitize_key( (string) $cap ),
                    (bool) $grant
                );
            }
        }

        update_option( self::VERSION_OPTION, self::VERSION );
    }

    /**
     * Returns user roles of module
     * 
     * @return array List of module user roles. Keyed by role-key and value is role's display name
     * 
     * @since 1.0.0
     */
    public static function get_roles() {
        $roles = array(
            'administrator'   => '',
            'cn_tour_manager' => 'Tour Manager'
        );
        $roles = (array) apply_filters( 'cn/modules/tour/get-roles', $roles );
        return $roles;
    }

    /**
     * Returns capabilities for specified `$role` which sets for module
     * 
     * @param string $role Wordpress role key.
     * 
     * @return array List of user role capablities
     * 
     * @since 1.0.0
     */
    private static function get_role_capabilities( $role ) {
        $capabilities = array();

        switch ( $role ) {
            case 'administrator':
            case 'cn_tour_manager':
                $capabilities = array(
                    'read'                   => true,
                    'upload_files'           => true,

                    'edit_tours'             => true,
                    'edit_others_tours'      => true,
                    'edit_private_tours'     => true,
                    'edit_published_tours'   => true,

                    'publish_tours'          => true,

                    'read_private_tours'     => true,

                    'delete_tours'           => true,
                    'delete_private_tours'   => true,
                    'delete_published_tours' => true,
                    'delete_others_tours'    => true,
                );
                break;

            default:
                $capabilities = array(
                    'read' => true
                );
                break;
        }

        $capabilities = (array) apply_filters( 'cn/modules/tour/get-role-capabilities', $capabilities, $role );
        return $capabilities;
    }

}