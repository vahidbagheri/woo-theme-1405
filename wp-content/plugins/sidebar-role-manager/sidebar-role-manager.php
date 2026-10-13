<?php
/**
 * Plugin Name: مدیریت نقش و دسترسی منوها
 * Description: ساخت نقش‌های اختصاصی و مدیریت منوها، زیرمنوها و مجوزهای وردپرس با رابط فارسی.
 * Version: 1.0.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Site Tools
 * Text Domain: sidebar-role-manager
 */

defined( 'ABSPATH' ) || exit;

final class SRM_Sidebar_Role_Manager {
    const OPTION = 'srm_roles';
    const CATALOG = 'srm_menu_catalog';
    const PAGE = 'sidebar-role-manager';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_page' ), 20 );
        add_action( 'admin_menu', array( $this, 'capture_and_filter' ), PHP_INT_MAX );
        add_action( 'admin_init', array( $this, 'guard_request' ), 0 );
        add_action( 'current_screen', array( $this, 'guard_request' ), 0 );
        add_action( 'admin_post_srm_save', array( $this, 'save' ) );
        add_action( 'admin_post_srm_delete', array( $this, 'delete' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
        add_action( 'admin_bar_menu', array( $this, 'filter_toolbar' ), 999 );
        add_filter( 'login_redirect', array( $this, 'login_redirect' ), 20, 3 );
        add_filter( 'user_has_cap', array( $this, 'protect_privileges' ), 20, 4 );
        add_filter( 'pre_update_option', array( $this, 'protect_role_options' ), 20, 3 );
        add_filter( 'citynet_manage_grant_roles', array( $this, 'citynet_grant_roles' ), 20, 2 );
    }

    public static function is_owner( $user = null ) {
        $user = $user ?: wp_get_current_user();
        return in_array( 'administrator', (array) $user->roles, true ) || ( is_multisite() && is_super_admin( $user->ID ) );
    }

    public static function blocked_caps() {
        return array( 'promote_users', 'edit_users', 'create_users', 'delete_users', 'remove_users', 'add_users', 'install_plugins', 'upload_plugins', 'update_plugins', 'delete_plugins', 'edit_plugins', 'activate_plugins', 'install_themes', 'upload_themes', 'update_themes', 'delete_themes', 'edit_themes', 'update_core', 'unfiltered_html', 'unfiltered_upload', 'edit_files', 'manage_network', 'manage_network_options', 'manage_network_users', 'manage_network_plugins', 'manage_network_themes', 'manage_sites', 'create_sites', 'delete_sites', 'setup_network' );
    }

    public function protect_privileges( $allcaps, $caps, $args, $user ) {
        if ( null !== $this->policy( $user ) ) {
            foreach ( self::blocked_caps() as $cap ) {
                $allcaps[ $cap ] = false;
            }
        }
        return $allcaps;
    }

    public function protect_role_options( $value, $option, $old_value ) {
        $protected = array( 'default_role', 'users_can_register', 'active_plugins', wp_roles()->role_key, self::OPTION, self::CATALOG );
        if ( in_array( $option, $protected, true ) && null !== $this->policy() ) {
            return $old_value;
        }
        return $value;
    }

    public function citynet_grant_roles( $grants, $capability ) {
        $grants = is_array( $grants ) ? $grants : array();
        // Citynet reconciles this capability on every init. Include the explicit
        // choices saved on our roles so its cleanup does not undo the admin's form.
        foreach ( get_option( self::OPTION, array() ) as $key => $rule ) {
            $role = get_role( $key );
            unset( $grants[ $key ] );
            if ( $role && $role->has_cap( $capability ) ) {
                $grants[ $key ] = true;
            }
        }
        return $grants;
    }

    public function policy( $user = null ) {
        $user = $user ?: wp_get_current_user();
        if ( self::is_owner( $user ) ) {
            return null;
        }
        $rules = get_option( self::OPTION, array() );
        $allowed = null;
        foreach ( (array) $user->roles as $role ) {
            if ( isset( $rules[ $role ] ) ) {
                // Multiple managed roles use the intersection, so a narrower role is not bypassed.
                $menus = (array) $rules[ $role ]['menus'];
                $allowed = null === $allowed ? $menus : array_values( array_intersect( $allowed, $menus ) );
            }
        }
        return $allowed;
    }

    public function register_page() {
        if ( self::is_owner() ) {
            add_menu_page( 'مدیریت سطح دسترسی', 'سطح دسترسی', 'manage_options', self::PAGE, array( $this, 'render' ), 'dashicons-shield', 81 );
        }
    }

    private function label( $html ) {
        $html = preg_replace( '#<span\b[^>]*>.*?</span>#si', '', $html );
        return trim( wp_strip_all_tags( $html ) );
    }

    public function menu_key( $slug ) {
        $slug = html_entity_decode( (string) $slug, ENT_QUOTES, 'UTF-8' );
        if ( false === strpos( $slug, '?' ) ) {
            return $slug;
        }
        $parts = explode( '?', $slug, 2 );
        parse_str( $parts[1], $query );
        if ( isset( $query['page'] ) && is_string( $query['page'] ) ) {
            return $query['page'];
        }
        $routing = array();
        foreach ( array( 'taxonomy', 'post_type' ) as $field ) {
            if ( isset( $query[ $field ] ) && is_string( $query[ $field ] ) && ! ( 'post_type' === $field && 'post' === $query[ $field ] ) ) {
                $routing[ $field ] = $query[ $field ];
            }
        }
        // Return URLs and nonces are navigation details, not permissions.
        return $parts[0] . ( $routing ? '?' . http_build_query( $routing, '', '&' ) : '' );
    }

    public function capture_and_filter() {
        global $menu, $submenu;
        if ( self::is_owner() && ! is_network_admin() ) {
            $catalog = array();
            foreach ( (array) $menu as $item ) {
                $raw_slug = (string) $item[2];
                $slug = $this->menu_key( $raw_slug );
                if ( self::PAGE === $slug || 0 === strpos( $slug, 'separator' ) ) {
                    continue;
                }
                $entry = array( 'label' => $this->label( $item[0] ), 'cap' => $item[1], 'children' => array() );
                foreach ( isset( $submenu[ $raw_slug ] ) ? $submenu[ $raw_slug ] : array() as $child ) {
                    $entry['children'][ $this->menu_key( $child[2] ) ] = array( 'label' => $this->label( $child[0] ), 'cap' => $child[1] );
                }
                $catalog[ $slug ] = $entry;
            }
            if ( $catalog !== get_option( self::CATALOG, array() ) ) {
                update_option( self::CATALOG, $catalog, false );
            }
            return;
        }
        $allowed = $this->policy();
        if ( null === $allowed ) {
            return;
        }
        foreach ( (array) $menu as $index => $item ) {
            $raw_slug = (string) $item[2];
            $slug = $this->menu_key( $raw_slug );
            $has_child = false;
            foreach ( isset( $submenu[ $raw_slug ] ) ? $submenu[ $raw_slug ] : array() as $key => $child ) {
                if ( ! in_array( $this->menu_key( $child[2] ), $allowed, true ) ) {
                    unset( $submenu[ $raw_slug ][ $key ] );
                } else {
                    $has_child = true;
                }
            }
            if ( ! in_array( $slug, $allowed, true ) && ! $has_child && 'profile.php' !== $slug ) {
                unset( $menu[ $index ] );
            }
        }
    }

    public function request_key() {
        global $pagenow;
        $script = $pagenow ?: basename( isset( $_SERVER['SCRIPT_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : '' );
        if ( isset( $_GET['page'] ) && is_string( $_GET['page'] ) ) {
            return sanitize_text_field( wp_unslash( $_GET['page'] ) );
        }
        if ( in_array( $script, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) {
            $type = isset( $_REQUEST['post_type'] ) && is_string( $_REQUEST['post_type'] ) ? sanitize_key( $_REQUEST['post_type'] ) : 'post';
            $id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : ( isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0 );
            if ( $id ) {
                $type = get_post_type( $id ) ?: $type;
            }
            if ( 'attachment' === $type ) {
                return 'upload.php';
            }
            return ( 'post-new.php' === $script ? 'post-new.php' : 'edit.php' ) . ( 'post' === $type ? '' : '?post_type=' . $type );
        }
        if ( in_array( $script, array( 'edit-tags.php', 'term.php' ), true ) ) {
            $taxonomy = isset( $_REQUEST['taxonomy'] ) && is_string( $_REQUEST['taxonomy'] ) ? sanitize_key( $_REQUEST['taxonomy'] ) : 'post_tag';
            $type = isset( $_REQUEST['post_type'] ) && is_string( $_REQUEST['post_type'] ) ? sanitize_key( $_REQUEST['post_type'] ) : 'post';
            return 'edit-tags.php?taxonomy=' . $taxonomy . ( 'post' === $type ? '' : '&post_type=' . $type );
        }
        $aliases = array( 'media-new.php' => 'media-new.php', 'media.php' => 'upload.php', 'async-upload.php' => 'upload.php', 'comment.php' => 'edit-comments.php', 'user-edit.php' => 'users.php' );
        if ( 'options.php' === $script && isset( $_POST['option_page'] ) && is_string( $_POST['option_page'] ) ) {
            $group = sanitize_key( $_POST['option_page'] );
            if ( in_array( $group, array( 'general', 'writing', 'reading', 'discussion', 'media', 'permalink', 'privacy' ), true ) ) {
                return 'options-' . $group . '.php';
            }
            // Custom settings handlers retain their native nonce and capability checks.
            return 'srm-settings-handler';
        }
        return isset( $aliases[ $script ] ) ? $aliases[ $script ] : $script;
    }

    public function guard_request() {
        if ( ! is_admin() || wp_doing_ajax() ) {
            return;
        }
        $allowed = $this->policy();
        if ( null === $allowed ) {
            return;
        }
        $key = $this->request_key();
        // Handler endpoints must enforce WordPress capabilities themselves; they are not menu pages.
        if ( in_array( $key, array( 'profile.php', 'admin-post.php', 'srm-settings-handler' ), true ) ) {
            return;
        }
        if ( ! in_array( $key, $allowed, true ) ) {
            if ( 'index.php' === $key && 'GET' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET' ) ) {
                wp_safe_redirect( admin_url( 'profile.php' ) );
                exit;
            }
            wp_die( 'شما به این بخش دسترسی ندارید. برای تغییر دسترسی با مدیر سایت تماس بگیرید.', 'دسترسی محدود', array( 'response' => 403, 'back_link' => true ) );
        }
    }

    public function filter_toolbar( $bar ) {
        $allowed = $this->policy();
        if ( null === $allowed ) {
            return;
        }
        $bar->remove_node( 'updates' );
        $map = array( 'comments' => 'edit-comments.php', 'new-media' => 'media-new.php', 'themes' => 'themes.php', 'widgets' => 'widgets.php', 'menus' => 'nav-menus.php', 'customize' => 'customize.php' );
        foreach ( get_post_types( array( 'show_ui' => true ) ) as $type ) {
            $map[ 'new-' . $type ] = 'post-new.php' . ( 'post' === $type ? '' : '?post_type=' . $type );
        }
        foreach ( $map as $node => $key ) {
            if ( ! in_array( $key, $allowed, true ) ) {
                $bar->remove_node( $node );
            }
        }
        if ( is_singular() ) {
            $type = get_post_type();
            $key = 'edit.php' . ( 'post' === $type ? '' : '?post_type=' . $type );
            if ( ! in_array( $key, $allowed, true ) ) {
                $bar->remove_node( 'edit' );
            }
        }
        $bar->remove_node( 'new-user' );
    }

    public function login_redirect( $redirect, $requested, $user ) {
        if ( $user instanceof WP_User && null !== $this->policy( $user ) && ! in_array( 'index.php', $this->policy( $user ), true ) ) {
            return admin_url( 'profile.php' );
        }
        return $redirect;
    }

    private function authorize( $nonce ) {
        if ( ! self::is_owner() || ! current_user_can( 'manage_options' ) ) {
            wp_die( 'فقط مدیر اصلی می‌تواند سطح دسترسی را تغییر دهد.', '', array( 'response' => 403 ) );
        }
        check_admin_referer( $nonce );
    }

    public function menu_capabilities( $entry ) {
        $cap = $entry['cap'];
        // These core menu capabilities map to a different stored permission.
        $aliases = array( 'customize' => 'edit_theme_options', 'manage_privacy_options' => is_multisite() ? 'manage_network' : 'manage_options' );
        $cap = isset( $aliases[ $cap ] ) ? $aliases[ $cap ] : $cap;
        if ( in_array( $cap, self::blocked_caps(), true ) || 'do_not_allow' === $cap || get_role( $cap ) ) {
            return array();
        }
        return array( $cap );
    }

    public function required_menu_caps( $menus ) {
        $caps = array();
        foreach ( get_option( self::CATALOG, array() ) as $slug => $entry ) {
            $items = $entry['children'];
            $items[ $slug ] = $entry;
            foreach ( $items as $item_slug => $item ) {
                if ( in_array( $item_slug, $menus, true ) ) {
                    foreach ( $this->menu_capabilities( $item ) as $cap ) {
                        $caps[ $cap ] = true;
                    }
                }
            }
        }
        return $caps;
    }

    public function available_caps() {
        $caps = array();
        foreach ( wp_roles()->roles as $role ) {
            foreach ( $role['capabilities'] as $cap => $value ) {
                if ( ! in_array( $cap, self::blocked_caps(), true ) && ! preg_match( '/^level_\d+$/', $cap ) ) {
                    $caps[ $cap ] = true;
                }
            }
        }
        foreach ( get_option( self::CATALOG, array() ) as $entry ) {
            foreach ( array_merge( array( $entry ), array_values( $entry['children'] ) ) as $item ) {
                if ( ! in_array( $item['cap'], self::blocked_caps(), true ) ) {
                    $caps[ $item['cap'] ] = true;
                }
                foreach ( $this->menu_capabilities( $item ) as $cap ) {
                    $caps[ $cap ] = true;
                }
            }
        }
        ksort( $caps );
        return $caps;
    }

    public function save() {
        $this->authorize( 'srm_save' );
        $rules = get_option( self::OPTION, array() );
        $key = isset( $_POST['role_key'] ) ? sanitize_key( wp_unslash( $_POST['role_key'] ) ) : '';
        $name = isset( $_POST['role_name'] ) ? sanitize_text_field( wp_unslash( $_POST['role_name'] ) ) : '';
        if ( '' === trim( $name ) || strlen( $name ) > 180 || ( $key && ! isset( $rules[ $key ] ) ) ) {
            wp_die( 'نام نقش معتبر نیست یا این نقش قابل ویرایش نیست.', '', array( 'response' => 400 ) );
        }
        if ( ! $key ) {
            do {
                $key = 'srm_' . strtolower( wp_generate_password( 10, false, false ) );
            } while ( get_role( $key ) );
        }
        $selected = isset( $_POST['caps'] ) && is_array( $_POST['caps'] ) ? array_filter( wp_unslash( $_POST['caps'] ), 'is_string' ) : array();
        $caps = array_intersect_key( $this->available_caps(), array_flip( $selected ) );
        $caps['read'] = true;
        $known = array( 'profile.php' );
        foreach ( get_option( self::CATALOG, array() ) as $slug => $entry ) {
            $known[] = $slug;
            $known = array_merge( $known, array_keys( $entry['children'] ) );
        }
        // Retain temporarily missing plugin entries when editing an existing role.
        if ( isset( $rules[ $key ] ) ) {
            $known = array_merge( $known, $rules[ $key ]['menus'] );
        }
        $posted = isset( $_POST['menus'] ) && is_array( $_POST['menus'] ) ? array_filter( wp_unslash( $_POST['menus'] ), 'is_string' ) : array();
        $menus = array_values( array_unique( array_merge( array( 'profile.php' ), array_intersect( $posted, $known ) ) ) );
        // Use the trusted catalog, not submitted data-cap values; also works without JS.
        $caps = array_merge( $caps, $this->required_menu_caps( $menus ) );
        $role = get_role( $key );
        if ( ! $role ) {
            $role = add_role( $key, $name, $caps );
        }
        if ( ! $role ) {
            wp_die( 'ساخت نقش انجام نشد.', '', array( 'response' => 500 ) );
        }
        foreach ( array_keys( $role->capabilities ) as $cap ) {
            if ( ! isset( $caps[ $cap ] ) ) {
                $role->remove_cap( $cap );
            }
        }
        foreach ( $caps as $cap => $value ) {
            $role->add_cap( $cap, true );
        }
        $wp_roles = wp_roles();
        $wp_roles->roles[ $key ]['name'] = $name;
        $wp_roles->role_names[ $key ] = $name;
        update_option( $wp_roles->role_key, $wp_roles->roles );
        $rules[ $key ] = array( 'name' => $name, 'menus' => $menus );
        update_option( self::OPTION, $rules, false );
        wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE, 'role' => $key, 'srm_notice' => 'saved' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public function delete() {
        $this->authorize( 'srm_delete' );
        $key = isset( $_POST['role_key'] ) ? sanitize_key( wp_unslash( $_POST['role_key'] ) ) : '';
        $rules = get_option( self::OPTION, array() );
        if ( ! isset( $rules[ $key ] ) ) {
            wp_die( 'نقش معتبر نیست.', '', array( 'response' => 400 ) );
        }
        if ( get_users( array( 'role' => $key, 'number' => 1, 'fields' => 'ID' ) ) || get_option( 'default_role' ) === $key ) {
            wp_die( 'این نقش به کاربر اختصاص دارد یا نقش پیش‌فرض سایت است. ابتدا نقش کاربران و نقش پیش‌فرض را تغییر دهید.', '', array( 'response' => 409, 'back_link' => true ) );
        }
        remove_role( $key );
        unset( $rules[ $key ] );
        update_option( self::OPTION, $rules, false );
        wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE, 'srm_notice' => 'deleted' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public function assets( $hook ) {
        if ( 'toplevel_page_' . self::PAGE === $hook ) {
            wp_enqueue_style( 'srm-admin', plugins_url( 'assets/admin.css', __FILE__ ), array(), '1.0.2' );
            wp_enqueue_script( 'srm-admin', plugins_url( 'assets/admin.js', __FILE__ ), array(), '1.0.2', true );
        }
    }

    private function checkbox( $slug, $entry, $selected, $class = '' ) {
        $required = $this->menu_capabilities( $entry );
        $blocked = ! $required;
        ?>
        <label class="srm-check <?php echo esc_attr( $class ); ?>">
            <input type="checkbox" name="menus[]" value="<?php echo esc_attr( $slug ); ?>" data-cap="<?php echo esc_attr( $entry['cap'] ); ?>" data-required-caps="<?php echo esc_attr( wp_json_encode( $required ) ); ?>" <?php checked( in_array( $slug, $selected, true ) ); ?> <?php disabled( $blocked ); ?>>
            <span><?php echo esc_html( $entry['label'] ); ?><?php if ( $blocked ) : ?><small>ویژه مدیر اصلی</small><?php else : ?><small class="srm-cap-note" hidden>مجوز لازم فعال نیست</small><?php endif; ?></span>
        </label>
        <?php
    }

    public function render() {
        if ( ! self::is_owner() ) {
            return;
        }
        $rules = get_option( self::OPTION, array() );
        $key = isset( $_GET['role'] ) ? sanitize_key( wp_unslash( $_GET['role'] ) ) : '';
        $key = isset( $rules[ $key ] ) ? $key : '';
        $role = $key ? get_role( $key ) : get_role( 'editor' );
        $caps = $role ? $role->capabilities : array( 'read' => true );
        $menus = $key ? $rules[ $key ]['menus'] : array( 'index.php', 'edit.php', 'post-new.php', 'edit-tags.php?taxonomy=category', 'edit-tags.php?taxonomy=post_tag', 'upload.php', 'media-new.php', 'edit.php?post_type=page', 'post-new.php?post_type=page', 'edit-comments.php', 'profile.php' );
        $catalog = get_option( self::CATALOG, array() );
        $notice = isset( $_GET['srm_notice'] ) ? sanitize_key( $_GET['srm_notice'] ) : '';
        $cap_labels = array( 'read' => 'ورود به پیشخوان', 'manage_options' => 'مدیریت تنظیمات سایت', 'edit_posts' => 'ویرایش نوشته‌ها', 'edit_others_posts' => 'ویرایش نوشته‌های دیگران', 'publish_posts' => 'انتشار نوشته‌ها', 'delete_posts' => 'حذف نوشته‌ها', 'edit_pages' => 'ویرایش برگه‌ها', 'edit_others_pages' => 'ویرایش برگه‌های دیگران', 'publish_pages' => 'انتشار برگه‌ها', 'delete_pages' => 'حذف برگه‌ها', 'upload_files' => 'بارگذاری رسانه', 'moderate_comments' => 'مدیریت دیدگاه‌ها', 'manage_categories' => 'مدیریت دسته‌ها', 'list_users' => 'مشاهده کاربران', 'edit_theme_options' => 'ویرایش فهرست‌ها و ابزارک‌ها', 'switch_themes' => 'تغییر پوسته', 'export' => 'برون‌بری محتوا', 'import' => 'درون‌ریزی محتوا' );
        ?>
        <div class="wrap srm-wrap" dir="rtl">
            <div class="srm-heading"><div><span class="srm-eyebrow">مدیریت تیم سایت</span><h1>نقش‌ها و سطح دسترسی</h1><p>برای هر مسئولیت، دسترسی متناسب تعریف کنید.</p></div><a class="button" href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">اختصاص نقش به کاربران</a></div>
            <?php if ( in_array( $notice, array( 'saved', 'deleted' ), true ) ) : ?><div class="notice notice-success is-dismissible"><p><?php echo 'saved' === $notice ? 'نقش و دسترسی‌ها ذخیره شد. اکنون می‌توانید این نقش را از بخش کاربران اختصاص دهید.' : 'نقش حذف شد.'; ?></p></div><?php endif; ?>
            <div class="srm-layout"><aside class="srm-panel srm-roles"><h2>نقش‌های اختصاصی <span class="srm-count"><?php echo esc_html( count( $rules ) ); ?></span></h2>
                <a class="srm-role <?php echo ! $key ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE ) ); ?>">＋ ساخت نقش جدید</a>
                <?php foreach ( $rules as $slug => $rule ) : ?><a class="srm-role <?php echo $key === $slug ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => self::PAGE, 'role' => $slug ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $rule['name'] ); ?></a><?php endforeach; ?>
                <p class="description">نقش‌های اصلی وردپرس و دسترسی مدیر اصلی تغییر نمی‌کنند.</p>
            </aside><main class="srm-panel">
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" id="srm-form">
                <input type="hidden" name="action" value="srm_save"><input type="hidden" name="role_key" value="<?php echo esc_attr( $key ); ?>"><?php wp_nonce_field( 'srm_save' ); ?>
                <h2><?php echo $key ? 'ویرایش نقش' : 'ساخت نقش جدید'; ?></h2>
                <label class="srm-field" for="srm-name">نام نقش<input id="srm-name" name="role_name" type="text" required maxlength="80" placeholder="مثلاً مدیر اجرایی" value="<?php echo esc_attr( $key ? $rules[ $key ]['name'] : '' ); ?>"></label>
                <div class="srm-section-heading"><div><h2>منوهای قابل دسترسی</h2><p>منوها و زیرمنوهای مجاز را انتخاب کنید. شناسنامه همیشه در دسترس است.</p></div><div class="srm-bulk"><button type="button" class="button" data-select="all">انتخاب همه</button><button type="button" class="button" data-select="none">حذف انتخاب‌ها</button></div></div>
                <input class="srm-search" type="search" id="srm-search" placeholder="جست‌وجوی منو…" aria-label="جست‌وجوی منو">
                <div class="srm-menu-grid">
                <?php foreach ( $catalog as $slug => $entry ) : if ( 'profile.php' === $slug ) { continue; } ?>
                    <section class="srm-menu-card"><div class="srm-menu-parent"><?php $this->checkbox( $slug, $entry, $menus, 'srm-parent' ); ?><button type="button" class="srm-group-toggle" aria-label="تغییر انتخاب همه موارد <?php echo esc_attr( $entry['label'] ); ?>">همه</button></div><div class="srm-children">
                        <?php foreach ( $entry['children'] as $child_slug => $child ) : if ( $slug !== $child_slug ) { $this->checkbox( $child_slug, $child, $menus ); } endforeach; ?>
                    </div></section>
                <?php endforeach; ?>
                </div><p id="srm-no-results" hidden>منویی با این نام پیدا نشد.</p>
                <?php $known = array_keys( $catalog ); foreach ( $catalog as $entry ) { $known = array_merge( $known, array_keys( $entry['children'] ) ); } foreach ( array_diff( $menus, $known, array( 'profile.php' ) ) as $missing ) : ?><input type="hidden" name="menus[]" value="<?php echo esc_attr( $missing ); ?>"><?php endforeach; ?>
                <div class="srm-info">با انتخاب هر منو یا زیرمنو، مجوز لازم آن خودکار فعال و همراه نقش ذخیره می‌شود. برداشتن تیک منو، مجوزهای عملیاتی را حذف نمی‌کند؛ برای حذف آن‌ها ابتدا منوهای مرتبط را از انتخاب خارج کنید و سپس مجوز را در بخش زیر بردارید.</div>
                <details class="srm-permissions"><summary>مجوزهای عملیاتی وردپرس <span>پیش‌فرض نقش جدید: ویرایشگر</span></summary>
                    <p>مجوز لازم منوهای انتخاب‌شده خودکار فعال می‌ماند. مجوزهای بیشتر، مثل انتشار یا حذف محتوا، را می‌توانید جداگانه انتخاب کنید. مجوز «مدیریت تنظیمات سایت» بین بسیاری از افزونه‌ها مشترک است. مدیریت حساب‌ها، نصب و تغییر کد افزونه‌ها فقط برای مدیر اصلی محفوظ است.</p>
                    <label for="srm-preset">الگوی مجوزها </label><select id="srm-preset"><option value="">انتخاب الگو…</option value="editor">ویرایشگر</option><option value="author">نویسنده</option><option value="contributor">مشارکت‌کننده</option><option value="subscriber">مشترک (فقط ورود)</option></select><span class="description"> انتخاب الگو، مجوزهای فعلی فرم را جایگزین می‌کند.</span>
                    <div class="srm-cap-grid">
                    <?php foreach ( $this->available_caps() as $cap => $value ) : ?><label class="srm-cap"><input type="checkbox" name="caps[]" value="<?php echo esc_attr( $cap ); ?>" <?php checked( ! empty( $caps[ $cap ] ) || 'read' === $cap ); ?> <?php disabled( 'read' === $cap ); ?>><span><?php echo esc_html( isset( $cap_labels[ $cap ] ) ? $cap_labels[ $cap ] : $cap ); ?><code><?php echo esc_html( $cap ); ?></code></span></label><?php endforeach; ?>
                    </div>
                </details>
                <div class="srm-footer"><button type="submit" class="button button-primary button-hero">ذخیره نقش و دسترسی‌ها</button><span>تغییرات برای کاربران این نقش اعمال می‌شود.</span></div>
            </form>
            <?php if ( $key ) : ?><form class="srm-delete" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="srm_delete"><input type="hidden" name="role_key" value="<?php echo esc_attr( $key ); ?>"><?php wp_nonce_field( 'srm_delete' ); ?><button class="button-link-delete" type="submit">حذف این نقش</button><span>فقط نقش بدون کاربر قابل حذف است.</span></form><?php endif; ?>
            </main></div>
        </div>
        <?php
        $presets = array();
        foreach ( array( 'editor', 'author', 'contributor', 'subscriber' ) as $preset ) {
            $preset_role = get_role( $preset );
            $presets[ $preset ] = $preset_role ? array_keys( array_filter( $preset_role->capabilities ) ) : array( 'read' );
        }
        wp_add_inline_script( 'srm-admin', 'window.srmPresets = ' . wp_json_encode( $presets ) . ';', 'before' );
    }
}

new SRM_Sidebar_Role_Manager();
