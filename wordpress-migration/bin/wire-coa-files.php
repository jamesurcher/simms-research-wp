<?php
/**
 * One-off: sideload local COA PDFs into the media library and link each to its
 * simms_coa_batch via _simms_coa_file_id. Matches files by batch id (lot-{id}).
 *
 * Run:  wp eval-file wire-coa-files.php <source-dir> [apply]
 * Without "apply" it is a dry run. <source-dir> holds the COA PDFs.
 */

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$argv_in = (array) ( $args ?? array() );
$apply   = in_array( 'apply', $argv_in, true );
$src_dir = '';
foreach ( $argv_in as $a ) {
	if ( 'apply' !== $a ) {
		$src_dir = rtrim( $a, '/' );
		break;
	}
}

if ( '' === $src_dir || ! is_dir( $src_dir ) ) {
	fwrite( STDERR, "Usage: wp eval-file wire-coa-files.php <source-dir> [apply]\nsource-dir not found: '$src_dir'\n" );
	exit( 1 );
}

$files = glob( $src_dir . '/*.pdf' );
$ver   = static function ( $f ) {
	return preg_match( '/--v(\d+)\.pdf$/i', $f, $m ) ? (int) $m[1] : 1;
};

$batch_ids = get_posts(
	array(
		'post_type'      => 'simms_coa_batch',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

printf( "mode=%s  batches=%d  files=%d\n", $apply ? 'APPLY' : 'DRY-RUN', count( $batch_ids ), count( $files ) );

$linked = 0;
$skipped = 0;
$missing = array();

foreach ( $batch_ids as $pid ) {
	$batch_id = get_post_meta( $pid, '_simms_batch_id', true );
	if ( '' === $batch_id ) {
		continue;
	}

	// Already wired to a real attachment? Skip.
	$existing = (int) get_post_meta( $pid, '_simms_coa_file_id', true );
	if ( $existing && get_post_status( $existing ) ) {
		$skipped++;
		printf( "  skip   %-8s batch#%d already -> attachment#%d\n", $batch_id, $pid, $existing );
		continue;
	}

	$needle = 'lot-' . strtolower( $batch_id ) . '-';
	$cands  = array_values( array_filter( $files, static function ( $f ) use ( $needle ) {
		return false !== strpos( strtolower( basename( $f ) ), $needle );
	} ) );

	if ( empty( $cands ) ) {
		$missing[] = $batch_id;
		printf( "  MISS   %-8s batch#%d no local PDF\n", $batch_id, $pid );
		continue;
	}

	usort( $cands, static function ( $a, $b ) use ( $ver ) {
		return $ver( $b ) <=> $ver( $a );
	} );
	$file = $cands[0];

	printf( "  link   %-8s batch#%d -> %s\n", $batch_id, $pid, basename( $file ) );

	if ( ! $apply ) {
		continue;
	}

	// media_handle_sideload moves the source file, so work from a temp copy.
	$tmp = trailingslashit( sys_get_temp_dir() ) . basename( $file );
	if ( ! copy( $file, $tmp ) ) {
		printf( "  ERROR  %-8s could not stage temp copy\n", $batch_id );
		continue;
	}

	$file_array = array(
		'name'     => basename( $file ),
		'tmp_name' => $tmp,
	);

	$attach_id = media_handle_sideload( $file_array, $pid, sprintf( 'COA %s', $batch_id ) );

	if ( is_wp_error( $attach_id ) ) {
		@unlink( $tmp );
		printf( "  ERROR  %-8s %s\n", $batch_id, $attach_id->get_error_message() );
		continue;
	}

	update_post_meta( $pid, '_simms_coa_file_id', (int) $attach_id );
	$linked++;
}

printf( "\nlinked=%d skipped=%d missing=%d\n", $linked, $skipped, count( $missing ) );
if ( $missing ) {
	printf( "missing batches: %s\n", implode( ', ', $missing ) );
}
