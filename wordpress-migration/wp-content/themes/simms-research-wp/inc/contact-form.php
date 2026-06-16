<?php
/**
 * Contact form handler (admin-post). Sensible default: sanitize + wp_mail to
 * the site admin. Production deliverability needs SMTP or a form plugin
 * (provider is an open decision).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_post_nopriv_simms_contact', 'simms_handle_contact_form' );
add_action( 'admin_post_simms_contact', 'simms_handle_contact_form' );

function simms_handle_contact_form(): void {
	$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/contact/' );

	if ( ! isset( $_POST['simms_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['simms_contact_nonce'] ) ), 'simms_contact' ) ) {
		wp_safe_redirect( add_query_arg( 'contact', 'error', $redirect ) );
		exit;
	}

	$contact = array();
	if ( isset( $_POST['contact'] ) && is_array( $_POST['contact'] ) ) {
		$contact = wp_unslash( $_POST['contact'] );
	}

	$name    = sanitize_text_field( $contact['name'] ?? wp_unslash( $_POST['name'] ?? '' ) );
	$email   = sanitize_email( $contact['email'] ?? wp_unslash( $_POST['email'] ?? '' ) );
	$phone   = sanitize_text_field( $contact['phone'] ?? wp_unslash( $_POST['phone'] ?? '' ) );
	$subject = sanitize_text_field( $contact['subject'] ?? wp_unslash( $_POST['subject'] ?? '' ) );
	$message = sanitize_textarea_field( $contact['body'] ?? wp_unslash( $_POST['message'] ?? '' ) );

	if ( ! is_email( $email ) || '' === $message ) {
		wp_safe_redirect( add_query_arg( 'contact', 'error', $redirect ) );
		exit;
	}

	$to      = get_option( 'admin_email' );
	$body    = "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\nSubject: {$subject}\n\n{$message}";
	$headers = array( 'Reply-To: ' . ( $name ? "{$name} <{$email}>" : $email ) );

	if ( ! wp_mail( $to, '[Simms Contact] ' . ( $subject ? $subject : 'New message' ), $body, $headers ) ) {
		wp_safe_redirect( add_query_arg( 'contact', 'error', $redirect ) );
		exit;
	}

	wp_safe_redirect( add_query_arg( 'contact', 'sent', $redirect ) );
	exit;
}
