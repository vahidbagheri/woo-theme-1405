<?php

defined( 'ABSPATH' ) || exit;

/**
 * ACF field that stores an SVG font glyph-name.
 */
final class Iconly_ACF_Field extends acf_field {
    /**
     * Configure the field type.
     */
    public function __construct() {
        $this->name     = 'iconly_icon';
        $this->label    = __( 'Iconly Font Icon', 'acf-iconly-picker' );
        $this->category = 'choice';
        $this->defaults = array(
            'allow_null' => 1,
            'show_search' => 1,
            'columns'     => 6,
        );

        parent::__construct();
    }

    /**
     * Add field-specific settings in the ACF field group editor.
     *
     * @param array $field Field settings.
     * @return void
     */
    public function render_field_settings( $field ) {
        acf_render_field_setting(
            $field,
            array(
                'label'        => __( 'Allow empty value', 'acf-iconly-picker' ),
                'instructions' => __( 'Show a button that clears the selected icon.', 'acf-iconly-picker' ),
                'name'         => 'allow_null',
                'type'         => 'true_false',
                'ui'           => 1,
            )
        );

        acf_render_field_setting(
            $field,
            array(
                'label'        => __( 'Show search', 'acf-iconly-picker' ),
                'instructions' => __( 'Allow searching by glyph name.', 'acf-iconly-picker' ),
                'name'         => 'show_search',
                'type'         => 'true_false',
                'ui'           => 1,
            )
        );

        acf_render_field_setting(
            $field,
            array(
                'label'        => __( 'Grid columns', 'acf-iconly-picker' ),
                'instructions' => __( 'Number of icon columns on wide screens.', 'acf-iconly-picker' ),
                'name'         => 'columns',
                'type'         => 'number',
                'min'          => 3,
                'max'          => 12,
                'step'         => 1,
            )
        );
    }

    /**
     * Enqueue ACF input assets.
     *
     * @return void
     */
    public function input_admin_enqueue_scripts() {
        wp_enqueue_style(
            'iconly-font',
            ICONLY_ACF_URL . 'assets/css/iconly-font.css',
            array(),
            ICONLY_ACF_VERSION
        );

        wp_enqueue_style(
            'iconly-acf-field',
            ICONLY_ACF_URL . 'assets/css/admin.css',
            array( 'iconly-font' ),
            ICONLY_ACF_VERSION
        );

        wp_enqueue_script(
            'iconly-acf-field',
            ICONLY_ACF_URL . 'assets/js/admin.js',
            array( 'jquery', 'acf-input' ),
            ICONLY_ACF_VERSION,
            true
        );

        static $data_added = false;

        if ( $data_added ) {
            return;
        }

        $icons = array_map(
            static function ( $icon ) {
                return array(
                    'name'      => (string) $icon['name'],
                    'unicode'   => (string) $icon['unicode'],
                    'codepoint' => (string) $icon['codepoint'],
                );
            },
            iconly_acf_parser()->get_icons()
        );

        $config = array(
            'icons' => $icons,
            'i18n'  => array(
                'choose'      => __( 'Choose an icon', 'acf-iconly-picker' ),
                'clear'       => __( 'Clear', 'acf-iconly-picker' ),
                'search'      => __( 'Search icons...', 'acf-iconly-picker' ),
                'icons'       => __( 'Icons', 'acf-iconly-picker' ),
                'noIcons'     => __( 'No icons were found in the SVG font file.', 'acf-iconly-picker' ),
                'noResults'   => __( 'No matching icons.', 'acf-iconly-picker' ),
                'missing'     => __( 'Missing icon:', 'acf-iconly-picker' ),
                'selected'    => __( 'Selected icon:', 'acf-iconly-picker' ),
            ),
        );

        wp_add_inline_script(
            'iconly-acf-field',
            'window.IconlyACF = ' . wp_json_encode( $config ) . ';',
            'before'
        );

        $data_added = true;
    }

    /**
     * Render the field input.
     *
     * @param array $field Field data.
     * @return void
     */
    public function render_field( $field ) {
        $value       = isset( $field['value'] ) ? trim( (string) $field['value'] ) : '';
        $selected    = iconly_acf_parser()->get_icon( $value );
        $allow_null  = ! empty( $field['allow_null'] );
        $show_search = ! empty( $field['show_search'] );
        $columns     = isset( $field['columns'] ) ? absint( $field['columns'] ) : 6;
        $columns     = max( 3, min( 12, $columns ) );
        $picker_id   = wp_unique_id( 'iconly-picker-' );
        ?>
        <div
            class="iconly-picker"
            data-allow-null="<?php echo $allow_null ? '1' : '0'; ?>"
            data-show-search="<?php echo $show_search ? '1' : '0'; ?>"
            data-columns="<?php echo esc_attr( (string) $columns ); ?>"
        >
            <input
                type="hidden"
                class="iconly-picker__input"
                name="<?php echo esc_attr( $field['name'] ); ?>"
                value="<?php echo esc_attr( $value ); ?>"
            >

            <div class="iconly-picker__toolbar">
                <button
                    type="button"
                    class="button iconly-picker__toggle"
                    aria-expanded="false"
                    aria-controls="<?php echo esc_attr( $picker_id ); ?>"
                >
                    <span class="iconly iconly-picker__preview-glyph" aria-hidden="true"><?php
                        if ( $selected ) {
                            echo esc_html( $selected['unicode'] );
                        }
                    ?></span>
                    <span class="iconly-picker__selected-name"><?php
                        echo esc_html( $value ? $value : __( 'Choose an icon', 'acf-iconly-picker' ) );
                    ?></span>
                </button>

                <?php if ( $allow_null ) : ?>
                    <button
                        type="button"
                        class="button-link-delete iconly-picker__clear"
                        <?php echo '' === $value ? 'hidden' : ''; ?>
                    ><?php esc_html_e( 'Clear', 'acf-iconly-picker' ); ?></button>
                <?php endif; ?>
            </div>

            <div id="<?php echo esc_attr( $picker_id ); ?>" class="iconly-picker__panel" hidden>
                <?php if ( $show_search ) : ?>
                    <label class="screen-reader-text" for="<?php echo esc_attr( $picker_id . '-search' ); ?>">
                        <?php esc_html_e( 'Search icons', 'acf-iconly-picker' ); ?>
                    </label>
                    <input
                        id="<?php echo esc_attr( $picker_id . '-search' ); ?>"
                        type="search"
                        class="iconly-picker__search"
                        placeholder="<?php esc_attr_e( 'Search icons...', 'acf-iconly-picker' ); ?>"
                        autocomplete="off"
                    >
                <?php endif; ?>

                <div class="iconly-picker__status" aria-live="polite"></div>
                <div class="iconly-picker__grid" role="listbox" aria-label="<?php esc_attr_e( 'Available icons', 'acf-iconly-picker' ); ?>"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Reject values that are not present in the current SVG font.
     *
     * @param bool|string $valid Current validation status.
     * @param mixed       $value Submitted value.
     * @param array       $field Field data.
     * @param string      $input Input name.
     * @return bool|string
     */
    public function validate_value( $valid, $value, $field, $input ) {
        unset( $input );

        if ( true !== $valid ) {
            return $valid;
        }

        $value = trim( sanitize_text_field( (string) $value ) );

        if ( '' === $value ) {
            if ( ! empty( $field['required'] ) ) {
                return __( 'Please select an icon.', 'acf-iconly-picker' );
            }

            return $valid;
        }

        if ( ! iconly_acf_parser()->has_icon( $value ) ) {
            return __( 'The selected icon does not exist in the current SVG font.', 'acf-iconly-picker' );
        }

        return $valid;
    }

    /**
     * Store only a valid glyph name.
     *
     * @param mixed $value   Submitted value.
     * @param mixed $post_id Post ID.
     * @param array $field   Field data.
     * @return string
     */
    public function update_value( $value, $post_id, $field ) {
        unset( $post_id, $field );

        $value = trim( sanitize_text_field( (string) $value ) );

        if ( '' === $value ) {
            return '';
        }

        return iconly_acf_parser()->has_icon( $value ) ? $value : '';
    }
}
