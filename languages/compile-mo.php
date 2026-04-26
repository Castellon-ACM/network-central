<?php
/**
 * Compile a .po file into a binary .mo file (GNU gettext format).
 * Usage: php compile-mo.php t-backup-es_ES.po
 */

if ( $argc < 2 ) {
	echo "Usage: php compile-mo.php <file.po>\n";
	exit( 1 );
}

$po_file = __DIR__ . '/' . $argv[1];
if ( ! file_exists( $po_file ) ) {
	echo "File not found: $po_file\n";
	exit( 1 );
}

$entries = parse_po( $po_file );
write_mo( $entries, str_replace( '.po', '.mo', $po_file ) );

echo "Compiled: " . basename( str_replace( '.po', '.mo', $po_file ) ) . " (" . count( $entries ) . " strings)\n";

// ── PO parser ──────────────────────────────────────────────────────────────

function parse_po( string $file ): array {
	$lines   = file( $file, FILE_IGNORE_NEW_LINES );
	$entries = [];
	$msgid   = null;
	$msgstr  = null;
	$in      = null;

	foreach ( $lines as $line ) {
		$line = rtrim( $line );

		if ( str_starts_with( $line, 'msgid "' ) ) {
			if ( $msgid !== null && $msgstr !== null && $msgid !== '' ) {
				$entries[] = [ $msgid, $msgstr ];
			}
			$msgid = unescape( substr( $line, 7, -1 ) );
			$in    = 'id';
		} elseif ( str_starts_with( $line, 'msgstr "' ) ) {
			$msgstr = unescape( substr( $line, 8, -1 ) );
			$in     = 'str';
		} elseif ( str_starts_with( $line, '"' ) && str_ends_with( $line, '"' ) ) {
			$chunk = unescape( substr( $line, 1, -1 ) );
			if ( $in === 'id' ) {
				$msgid .= $chunk;
			} elseif ( $in === 'str' ) {
				$msgstr .= $chunk;
			}
		} else {
			$in = null;
		}
	}

	if ( $msgid !== null && $msgstr !== null && $msgid !== '' ) {
		$entries[] = [ $msgid, $msgstr ];
	}

	usort( $entries, fn( $a, $b ) => strcmp( $a[0], $b[0] ) );

	return $entries;
}

function unescape( string $s ): string {
	return stripcslashes( $s );
}

// ── MO writer ─────────────────────────────────────────────────────────────
// Reference: https://www.gnu.org/software/gettext/manual/html_node/MO-Files.html

function write_mo( array $entries, string $out_file ): void {
	$magic    = 0x950412de;
	$revision = 0;
	$count    = count( $entries );
	$header   = 7 * 4; // 7 × 4-byte fields

	$orig_offset  = $header;
	$trans_offset = $header + $count * 8;
	$strings_base = $header + $count * 16;

	$orig_table  = '';
	$trans_table = '';
	$orig_data   = '';
	$trans_data  = '';
	$cursor      = $strings_base;

	foreach ( $entries as [ $id, $str ] ) {
		$id_len  = strlen( $id );
		$str_len = strlen( $str );

		$orig_table  .= pack( 'VV', $id_len, $cursor );
		$orig_data   .= $id . "\0";
		$cursor      += $id_len + 1;

		$trans_table .= pack( 'VV', $str_len, $cursor );
		$trans_data  .= $str . "\0";
		$cursor      += $str_len + 1;
	}

	$mo  = pack( 'V', $magic );
	$mo .= pack( 'V', $revision );
	$mo .= pack( 'V', $count );
	$mo .= pack( 'V', $orig_offset );
	$mo .= pack( 'V', $trans_offset );
	$mo .= pack( 'VV', 0, $strings_base ); // hash table size=0, hash offset (unused)
	$mo .= $orig_table;
	$mo .= $trans_table;
	$mo .= $orig_data;
	$mo .= $trans_data;

	file_put_contents( $out_file, $mo );
}
