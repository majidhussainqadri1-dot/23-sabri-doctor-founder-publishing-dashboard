<?php
/**
 * File23 V12.1 WP-06: RFC 5545 sections 3.1 & 3.3.11, iCalendar
 * TEXT escaping and 75-octet UTF-8-preserving content-line folding.
 * Isolated source regression; no calendar client or Hostinger claim.
 */
require_once __DIR__ . '/bootstrap.php';
$tests=0; $failed=0;
$assert=static function ( bool $ok, string $label ) use ( &$tests, &$failed ): void {
	++$tests; if ( ! $ok ) { ++$failed; fwrite( STDERR, "FAIL: {$label}\n" ); }
};
$service = ( new ReflectionClass( SPDB_Export_Service::class ) )->newInstanceWithoutConstructor();
$esc = new ReflectionMethod( SPDB_Export_Service::class, 'ics_escape' );
$esc->setAccessible( true );
$render = new ReflectionMethod( SPDB_Export_Service::class, 'render_ics' );
$render->setAccessible( true );

$assert( 'Line1\\nLine2' === $esc->invoke( null, "Line1\nLine2" ), 'Actual newline becomes exactly one backslash-n, not doubled.' );
$assert( 'Line1\\nLine2\\nLine3' === $esc->invoke( null, "Line1\r\nLine2\rLine3" ), 'CRLF and solitary CR encode as one logical text newline.' );
$assert( 'A\\\\B\\;C\\,D' === $esc->invoke( null, 'A\\B;C,D' ), 'Original backslashes, semicolons and commas escape once each.' );
$assert( 'A:B' === $esc->invoke( null, 'A:B' ), 'Colon is not escaped in RFC 5545 TEXT.' );
$assert( 'Literal\\\\n' === $esc->invoke( null, 'Literal\\n' ), 'Literal source backslash-n is preserved as literal text, not a logical newline.' );

$long_urdu = str_repeat( 'کتاب', 45 );
$summary = 'Part;Segment,Another\\Path' . "\n" . 'Next';
$model = array( 'events' => array(
	array( 'uid' => 'file23_test_1', 'dtstart' => '2026-11-20 10:00:00 UTC', 'summary' => $summary, 'description' => "Line1\r\nLine2\rLine3\nLine4" ),
	array( 'uid' => 'file23_test_2', 'dtstart' => '2026-11-21 10:00:00 UTC', 'summary' => $long_urdu, 'description' => str_repeat( 'A', 230 ) ),
) );
$ics = $render->invoke( $service, $model );
$unfolded = str_replace( "\r\n ", '', $ics );
$assert( str_contains( $unfolded, 'SUMMARY:Part\\;Segment\\,Another\\\\Path\\nNext' ), 'Rendered summary has exactly escaped special characters and newline.' );
$assert( str_contains( $unfolded, 'DESCRIPTION:Line1\\nLine2\\nLine3\\nLine4' ), 'Rendered description normalizes all source line break forms.' );
$assert( str_contains( $unfolded, 'SUMMARY:' . $long_urdu ), 'Unfolding restores every multilingual UTF-8 byte without truncation.' );
$assert( true === (bool) preg_match( '//u', $ics ), 'Folding does not split or corrupt any UTF-8 character.' );

$all_bounded = true;
foreach ( explode( "\r\n", rtrim( $ics, "\r\n" ) ) as $physical ) {
	if ( strlen( $physical ) > 75 ) { $all_bounded = false; break; }
}
$assert( $all_bounded, 'Every physical ICS content line has at most 75 octets, including continuation space.' );
$assert( str_contains( $ics, "\r\n " ), 'At least one actual RFC 5545 folded continuation is emitted.' );
$assert( ! str_contains( str_replace( "\r\n", '', $ics ), "\n" ), 'ICS uses CRLF line separators and contains no stray raw LF.' );
$assert( str_ends_with( $ics, "END:VCALENDAR\r\n" ), 'Calendar ends with a proper CRLF-terminated END:VCALENDAR.' );

if ( $failed ) { fwrite( STDERR, "{$failed} of {$tests} V12.1 RFC5545 ICS tests failed.\n" ); exit( 1 ); }
echo "All {$tests} V12.1 RFC5545 ICS tests passed.\n";
