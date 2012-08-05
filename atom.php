<?php

	header( 'Content-Type: application/atom+xml; charset=utf-8' );

	date_default_timezone_set('America/New_York');

	$expiration = 43200;	// 12 hours

	if ( file_exists( 'atom.cache' ) ) {
		$mtime = filemtime( 'atom.cache' );
		if ( time() < $mtime + $expiration ) {
			echo file_get_contents( 'atom.cache' );
			die();
		}
	}

	require('ventureloop.php');

	// search for some jobs
	$v = VentureLoop::factory()->search( 'php', 'Austin, TX' );
	$jobs = $v->jobs();

	$dom = new DOMDocument('1.0', 'utf-8');
	$dom->formatOutput = true;

	// create the root feed node with its namespace
	$feed = $dom->createElementNS( 'http://www.w3.org/2005/Atom', 'feed' );

	// create the title node
	$title_text = implode( ' in ', array( $v->results->keywords, $v->results->location ) );
	$title = $dom->createElement( 'title', 'VentureLoop - ' . $title_text );

	// add the title to the feed node
	$feed->appendChild( $title );

	// and the link node
	$link = $dom->createElement( 'link' );
	$link->setAttribute( 'href', 'http://www.ventureloop.com' );

	// add it to the feed node
	$feed->appendChild( $link );

	// figure out the last updated date - should be the posted date of the first job, if we have one
	if ( count( $jobs ) > 0 ) {
		$last_updated = $jobs[0]->posted_on;
	}
	else {
		// otherwise, it's now - we just checked
		$last_updated = new DateTime();
	}

	$updated = $dom->createElement( 'updated', $last_updated->format( DateTime::ATOM ) );

	$feed->appendChild( $updated );

	$author = $dom->createElement( 'author' );
	$author_name = $dom->createElement( 'name', 'VentureLoop' );

	$author->appendChild( $author_name );

	$feed->appendChild( $author );

	// all of this tries to come up with a unique ID to represent this exact search... and formats it as a UUID
	$search_key = $v->results->keywords . $v->results->location . $v->results->distance . implode( ',', $v->results->categories ) . implode( ',', $v->results->search_in ) . $v->results->posted . $v->email . ( $v->results->session_id != null );
	$uuid = hash( 'md5', $search_key );		// md5 so we get 32 chars back

	$uuid_hex = uuid_hex( $uuid );

	$id = $dom->createElement( 'id', 'urn:uuid:' . $uuid_hex );

	$feed->appendChild( $id );

	foreach ( $jobs as $job ) {

		$entry = $dom->createElement( 'entry' );

		$title = $dom->createElement( 'title', $job->title . ' at ' . $job->company->name );

		$link = $dom->createElement( 'link' );
		$link->setAttribute( 'href', $job->url );

		$uuid = hash( 'md5', $job->id );
		$uuid_hex = uuid_hex( $uuid );
		$id = $dom->createElement( 'id', 'urn:uuid:' . $uuid_hex );

		$updated = $dom->createElement( 'updated', $job->posted_on->format( DateTime::ATOM ) );

		$summary = $dom->createElement( 'summary', htmlspecialchars( $job->description ) );
		$summary->setAttribute( 'type', 'html' );

		$entry->appendChild( $title );
		$entry->appendChild( $link );
		$entry->appendChild( $id );
		$entry->appendChild( $updated );
		$entry->appendChild( $summary );

		$feed->appendChild( $entry );

	}

	// add the root feed node to the document
	$dom->appendChild( $feed );

	$xml = $dom->saveXML();

	file_put_contents( 'atom.cache', $xml );

	echo $xml;

	function uuid_hex ( $uuid ) {
		$uuid = str_split( $uuid );
		$uuid_hex = '';
		for ( $i = 0; $i < 32; $i++ ) {
			if ( $i == 8 || $i == 12 || $i == 16 || $i == 20 ) {
				$uuid_hex .= '-';
			}
			$uuid_hex .= $uuid[ $i ];
		}

		return $uuid_hex;
	}

?>
