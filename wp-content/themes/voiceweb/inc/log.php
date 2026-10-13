<?php
/**
 * VoiceWeb activity logging, archived by local Jalali date.
 *
 * @package voiceweb
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'voiceweb_log' ) ) {
	/**
	 * Write a message using the existing VoiceWeb log format.
	 *
	 * CN_LOG_DIR can select a private, writable directory. By default logs live
	 * beside the WordPress document root, so the web server cannot serve them.
	 * WP-Parsidate provides Jalali dates; Gregorian dates are used without it.
	 *
	 * @param string $message   Log message. Never pass credentials or form bodies.
	 * @param string $file_name Archive file name, without its extension.
	 * @param array  $data      Associative, scalar headline metadata.
	 * @return bool Whether the log was written.
	 */
	function voiceweb_log( $message, $file_name = 'general', $data = array() ) {
		$now = ( new DateTimeImmutable( 'now', new DateTimeZone( 'Asia/Tehran' ) ) )->format( 'Y-m-d H:i:s' );
		if ( function_exists( 'parsidate' ) ) {
			$now = parsidate( 'Y-m-d H:i:s', $now, 'eng' );
		}
		list( $date, $time ) = explode( ' ', $now, 2 );
		$file_name = sanitize_key( $file_name );
		if ( $file_name === '' ) {
			$file_name = 'general';
		}

		$root = ( defined( 'CN_LOG_DIR' ) ) ? CN_LOG_DIR : dirname( rtrim( ABSPATH, '/\\' ) ) . '/voiceweb-log';
		$directory = rtrim( $root, '/\\' ) . '/' . $date;
		if ( ! is_dir( $directory ) && ! wp_mkdir_p( $directory ) ) {
			error_log( 'VoiceWeb could not create its private log directory.' );
			return false;
		}

		$head_line = "Local time = {$time}";
		foreach ( $data as $key => $value ) {
			if ( ! is_scalar( $value ) && $value !== null ) {
				continue;
			}
			$head_line .= ' || ' . sanitize_key( $key ) . ' = ' . str_replace( array( "\r", "\n" ), ' ', (string) $value );
		}

		// One locked append keeps concurrent entries intact.
		return ( @file_put_contents(
			$directory . '/' . $file_name . '.log',
			"{$head_line}\n\n{$message}\n\n==================\n\n",
			FILE_APPEND | LOCK_EX
		) !== false );
	}
}

/**
 * Send an activity to voiceweb_log using only approved, non-sensitive metadata.
 *
 * @param string $event   Stable event name, e.g. post.created.
 * @param array  $context IDs and status information; other keys are discarded.
 * @return bool Whether the logger accepted the event.
 */
function cn_log_event( $event, $context = array() ) {
	static $writing = false;
	if ( $writing ) {
		return false;
	}

	$context = array_intersect_key( (array) $context, array_flip( array(
		'user_id', 'post_id', 'post_type', 'status', 'previous_status', 'comment_id',
		'form_id', 'provider', 'interaction', 'object_id', 'role', 'error_code',
		'language', 'result_count',
	) ) );
	$context['actor_id'] = get_current_user_id();
	$context['blog_id'] = get_current_blog_id();
	$context['event'] = preg_replace( '/[^a-z0-9_.-]/', '', strtolower( (string) $event ) );
	foreach ( $context as $key => $value ) {
		$context[ $key ] = ( is_scalar( $value ) ) ? substr( sanitize_text_field( (string) $value ), 0, 120 ) : '';
	}

	$writing = true;
	try {
		return (bool) voiceweb_log( $context['event'], 'activity', $context );
	} catch ( Throwable $error ) {
		// A logging failure must not interrupt authentication or content writes.
		error_log( 'VoiceWeb activity logger failed.' );
		return false;
	} finally {
		$writing = false;
	}
}

function cn_log_login( $user_login, $user ) {
	cn_log_event( 'user.login', array( 'user_id' => $user->ID ) );
}
add_action( 'wp_login', 'cn_log_login', 10, 2 );

function cn_log_login_failed( $username, $error ) {
	cn_log_event( 'user.login_failed', array( 'error_code' => $error->get_error_code() ) );
}
add_action( 'wp_login_failed', 'cn_log_login_failed', 10, 2 );

function cn_log_logout( $user_id ) {
	cn_log_event( 'user.logout', array( 'user_id' => $user_id ) );
}
add_action( 'wp_logout', 'cn_log_logout' );

function cn_log_registration( $user_id ) {
	cn_log_event( 'user.registered', array( 'user_id' => $user_id ) );
}
add_action( 'user_register', 'cn_log_registration' );

function cn_log_profile_update( $user_id ) {
	cn_log_event( 'user.updated', array( 'user_id' => $user_id ) );
}
add_action( 'profile_update', 'cn_log_profile_update' );

function cn_log_user_deleted( $user_id ) {
	cn_log_event( 'user.deleted', array( 'user_id' => $user_id ) );
}
add_action( 'deleted_user', 'cn_log_user_deleted' );
add_action( 'wpmu_delete_user', 'cn_log_user_deleted' );

function cn_log_password_reset( $user ) {
	cn_log_event( 'user.password_reset', array( 'user_id' => $user->ID ) );
}
add_action( 'after_password_reset', 'cn_log_password_reset' );

function cn_log_role_change( $user_id, $role ) {
	cn_log_event( 'user.role_changed', array( 'user_id' => $user_id, 'role' => $role ) );
}
add_action( 'set_user_role', 'cn_log_role_change', 10, 2 );
add_action( 'add_user_role', 'cn_log_role_change', 10, 2 );

function cn_log_role_removed( $user_id, $role ) {
	cn_log_event( 'user.role_removed', array( 'user_id' => $user_id, 'role' => $role ) );
}
add_action( 'remove_user_role', 'cn_log_role_removed', 10, 2 );

/** One record for each completed content save, including custom post types/media. */
function cn_log_post_saved( $post_id, $post, $update, $post_before ) {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || $post->post_status === 'auto-draft' ) {
		return;
	}

	$previous_status = ( $post_before instanceof WP_Post ) ? $post_before->post_status : '';
	$event = ( $update && $previous_status !== 'auto-draft' ) ? 'post.updated' : 'post.created';
	if ( $post->post_status === 'trash' && $previous_status !== 'trash' ) {
		$event = 'post.trashed';
	} elseif ( $previous_status === 'trash' && $post->post_status !== 'trash' ) {
		$event = 'post.restored';
	}
	cn_log_event( $event, array(
		'post_id' => $post_id,
		'post_type' => $post->post_type,
		'status' => $post->post_status,
		'previous_status' => $previous_status,
	) );
}
add_action( 'wp_after_insert_post', 'cn_log_post_saved', 10, 4 );

/** Core attachment writes return before wp_after_insert_post. */
function cn_log_attachment_created( $post_id ) {
	$post = get_post( $post_id );
	if ( $post instanceof WP_Post ) {
		cn_log_post_saved( $post_id, $post, false, null );
	}
}
add_action( 'add_attachment', 'cn_log_attachment_created' );

function cn_log_attachment_updated( $post_id, $post_after, $post_before ) {
	cn_log_post_saved( $post_id, $post_after, true, $post_before );
}
add_action( 'attachment_updated', 'cn_log_attachment_updated', 10, 3 );

function cn_log_post_deleted( $post_id, $post ) {
	if ( $post->post_type === 'revision' || $post->post_status === 'auto-draft' ) {
		return;
	}
	cn_log_event( 'post.deleted', array( 'post_id' => $post_id, 'post_type' => $post->post_type, 'status' => $post->post_status ) );
}
add_action( 'deleted_post', 'cn_log_post_deleted', 10, 2 );

function cn_log_comment_submitted( $comment_id, $approved, $comment_data ) {
	cn_log_event( 'form.submitted', array(
		'provider' => 'wordpress-comment',
		'comment_id' => $comment_id,
		'post_id' => $comment_data['comment_post_ID'],
		'status' => $approved,
	) );
}
add_action( 'comment_post', 'cn_log_comment_submitted', 10, 3 );

function cn_log_comment_status( $new_status, $old_status, $comment ) {
	if ( $new_status === $old_status ) {
		return;
	}
	cn_log_event( 'comment.status_changed', array(
		'comment_id' => $comment->comment_ID,
		'post_id' => $comment->comment_post_ID,
		'status' => $new_status,
		'previous_status' => $old_status,
	) );
}
add_action( 'transition_comment_status', 'cn_log_comment_status', 10, 3 );

function cn_log_comment_deleted( $comment_id ) {
	cn_log_event( 'comment.deleted', array( 'comment_id' => $comment_id ) );
}
add_action( 'deleted_comment', 'cn_log_comment_deleted' );

function cn_log_comment_updated( $comment_id ) {
	cn_log_event( 'comment.updated', array( 'comment_id' => $comment_id ) );
}
add_action( 'edit_comment', 'cn_log_comment_updated' );

function cn_log_acf_form( $form, $post_id ) {
	cn_log_form_submitted( $form['id'] ?? 'acf-form', array( 'provider' => 'acf', 'object_id' => $post_id ) );
}
add_action( 'acf/submit_form', 'cn_log_acf_form', 10, 2 );

function cn_log_settings_saved( $post_id ) {
	if ( is_string( $post_id ) && ( $post_id === 'options' || str_ends_with( $post_id, '_options' ) ) ) {
		cn_log_event( 'settings.updated', array( 'provider' => 'acf', 'object_id' => $post_id ) );
	}
}
add_action( 'acf/save_post', 'cn_log_settings_saved', 20 );

/** Custom handlers call this action only after validating and saving a form. */
function cn_log_form_submitted( $form_id, $context = array() ) {
	$context['form_id'] = $form_id;
	cn_log_event( 'form.submitted', $context );
}
add_action( 'cn/form/submitted', 'cn_log_form_submitted', 10, 2 );

/** Custom, server-validated interactions can opt in without exposing a public logger. */
function cn_log_interaction( $interaction, $context = array() ) {
	$context['interaction'] = $interaction;
	cn_log_event( 'user.interaction', $context );
}
add_action( 'cn/interaction', 'cn_log_interaction', 10, 2 );
