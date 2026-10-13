<?php

defined( 'ABSPATH' ) || exit;

/**
 * Reads glyph names and one-character Unicode values from an SVG font file.
 */
final class Iconly_SVG_Parser {
    /** @var string */
    private $path;

    /** @var array|null */
    private $icons = null;

    /**
     * @param string $path Absolute SVG font path.
     */
    public function __construct( $path ) {
        $this->path = (string) $path;
    }

    /**
     * @return string
     */
    public function get_path() {
        return $this->path;
    }

    /**
     * Return parsed icons.
     *
     * Each item contains: name, unicode, and codepoint.
     * Multi-character ligatures are intentionally ignored.
     *
     * @return array
     */
    public function get_icons() {
        if ( is_array( $this->icons ) ) {
            return $this->icons;
        }

        if ( ! is_readable( $this->path ) || ! class_exists( 'DOMDocument' ) ) {
            $this->icons = array();
            return $this->icons;
        }

        $mtime     = (int) filemtime( $this->path );
        $filesize  = (int) filesize( $this->path );
        $cache_key = 'iconly_acf_' . md5( $this->path . '|' . $mtime . '|' . $filesize );
        $cached    = get_transient( $cache_key );

        if ( is_array( $cached ) ) {
            $this->icons = $cached;
            return $this->icons;
        }

        $previous_errors = libxml_use_internal_errors( true );
        $dom             = new DOMDocument();
        $dom->resolveExternals  = false;
        $dom->substituteEntities = false;

        $loaded = $dom->load(
            $this->path,
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        );

        libxml_clear_errors();
        libxml_use_internal_errors( $previous_errors );

        if ( ! $loaded ) {
            $this->icons = array();
            return $this->icons;
        }

        $xpath  = new DOMXPath( $dom );
        $nodes  = $xpath->query( '//*[local-name()="glyph"]' );
        $icons  = array();
        $unique = array();

        if ( false !== $nodes ) {
            foreach ( $nodes as $glyph ) {
                if ( ! $glyph instanceof DOMElement ) {
                    continue;
                }

                $glyph_names = trim( $glyph->getAttribute( 'glyph-name' ) );
                $unicode     = $glyph->getAttribute( 'unicode' );

                if ( '' === $glyph_names || ! $this->is_single_unicode_character( $unicode ) ) {
                    continue;
                }

                foreach ( preg_split( '/\s*,\s*/', $glyph_names ) as $glyph_name ) {
                    $glyph_name = trim( sanitize_text_field( $glyph_name ) );

                    if ( '' === $glyph_name || isset( $unique[ $glyph_name ] ) ) {
                        continue;
                    }

                    $unique[ $glyph_name ] = true;
                    $icons[] = array(
                        'name'      => $glyph_name,
                        'unicode'   => $unicode,
                        'codepoint' => $this->unicode_codepoint( $unicode ),
                    );
                }
            }
        }

        usort(
            $icons,
            static function ( $left, $right ) {
                return strnatcasecmp( $left['name'], $right['name'] );
            }
        );

        $icons = (array) apply_filters( 'iconly_acf_parsed_icons', $icons, $this->path );

        set_transient( $cache_key, $icons, WEEK_IN_SECONDS );

        $this->icons = $icons;
        return $this->icons;
    }

    /**
     * Find an icon by its exact glyph name.
     *
     * @param string $name Glyph name.
     * @return array|null
     */
    public function get_icon( $name ) {
        $name = trim( sanitize_text_field( (string) $name ) );

        if ( '' === $name ) {
            return null;
        }

        foreach ( $this->get_icons() as $icon ) {
            if ( isset( $icon['name'] ) && $icon['name'] === $name ) {
                return $icon;
            }
        }

        return null;
    }

    /**
     * @param string $name Glyph name.
     * @return bool
     */
    public function has_icon( $name ) {
        return null !== $this->get_icon( $name );
    }

    /**
     * True only for exactly one UTF-8 code point.
     *
     * @param string $value Unicode attribute value.
     * @return bool
     */
    private function is_single_unicode_character( $value ) {
        return is_string( $value ) && 1 === preg_match( '/^.$/us', $value );
    }

    /**
     * Convert one UTF-8 character to an uppercase hexadecimal code point.
     *
     * @param string $character One UTF-8 character.
     * @return string
     */
    private function unicode_codepoint( $character ) {
        $bytes = array_values( unpack( 'C*', $character ) );
        $count = count( $bytes );

        if ( 1 === $count ) {
            $codepoint = $bytes[0];
        } elseif ( 2 === $count ) {
            $codepoint = ( ( $bytes[0] & 0x1F ) << 6 ) | ( $bytes[1] & 0x3F );
        } elseif ( 3 === $count ) {
            $codepoint = ( ( $bytes[0] & 0x0F ) << 12 )
                | ( ( $bytes[1] & 0x3F ) << 6 )
                | ( $bytes[2] & 0x3F );
        } elseif ( 4 === $count ) {
            $codepoint = ( ( $bytes[0] & 0x07 ) << 18 )
                | ( ( $bytes[1] & 0x3F ) << 12 )
                | ( ( $bytes[2] & 0x3F ) << 6 )
                | ( $bytes[3] & 0x3F );
        } else {
            return '';
        }

        return strtoupper( dechex( $codepoint ) );
    }
}
