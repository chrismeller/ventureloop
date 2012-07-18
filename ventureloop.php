<?php

	class VentureLoop {

		private $email;
		private $password;

		public $distance = 20;
		public $categories = array( self::CATEGORY_ALL_FUNCTIONS );
		public $search_in = array( self::SEARCH_IN_JOB_DESCRIPTION, self::SEARCH_IN_JOB_TITLE, self::SEARCH_IN_COMPANY_NAME );
		public $posted = self::POSTED_ALL;

		public $session_name;
		public $session_id;
		public $session_expires;
		public $session_path;

		public $results;

		const SEARCH_IN_JOB_DESCRIPTION = 'jd';
		const SEARCH_IN_JOB_TITLE = 'jt';
		const SEARCH_IN_COMPANY_NAME = 'jc';

		const CATEGORY_ALL_FUNCTIONS = 0;
		const CATEGORY_ACCOUNTING_FINANCE = 1;
		const CATEGORY_ADMINISTRATION = 2;
		const CATEGORY_ASSOCIATE = 3;
		const CATEGORY_BUSINESS_DEV = 11;
		const CATEGORY_CLINICAL_RESEARCH = 41;
		const CATEGORY_ENGINEERING_HARDWARE = 13;
		const CATEGORY_ENGINEERING_PROD_MGMT_DEV = 39;
		const CATEGORY_ENGINEERING_QA = 15;
		const CATEGORY_ENGINEERING_SOFTWARE = 12;
		const CATEGORY_EXECUTIVE = 42;
		const CATEGORY_HR = 18;
		const CATEGORY_IT = 19;
		const CATEGORY_LEGAL = 20;
		const CATEGORY_LIFE_SCIENCES_QA_QC = 9;
		const CATEGORY_LIFE_SCIENCES_RD_ENGINEERING = 4;
		const CATEGORY_MANUFACTURING = 21;
		const CATEGORY_MARKETING_PR_PRODUCT_MKTG = 31;
		const CATEGORY_OPERATIONS = 27;
		const CATEGORY_PROFESSIONAL_SVCS = 28;
		const CATEGORY_REGULATORY_AFFAIRS = 40;
		const CATEGORY_RESEARCH_SCI_ASSOC_MGR = 10;
		const CATEGORY_SALES = 8;
		const CATEGORY_TECHNICAL_CUSTOMER_SUPPORT = 30;
		const CATEGORY_WEB_GRAPHIC_DESIGN_INTERNET = 38;

		const POSTED_ALL = 'all';
		const POSTED_TODAY = 'today';
		const POSTED_YESTERDAY = 'yesterday';
		const POSTED_WEEK = 'week';
		const POSTED_MONTH = 'month';

		public function __construct ( $email = null, $password = null ) {
			$this->email = $email;
			$this->password = $password;
		}

		public static function factory ( $email = null, $password = null ) {
			$c = __CLASS__;
			$object = new $c( $email, $password );

			return $object;
		}

		public function email ( $email ) {
			$this->email = $email;

			return $this;
		}

		public function password ( $password ) {
			$this->password = $password;

			return $this;
		}

		public function login ( ) {

			$fields = array(
				'email_1' => $this->email,
				'pass' => $this->password,
				'cmd' => 'Submit',
				'ref' => null,
			);

			$options = array(
				'http' => array(
					'method' => 'POST',
					'content' => $fields,
					'header' => array(
						'Content-Type: application/x-www-form-urlencoded',
					),
					'timeout' => 5,
				),
			);

			$context = stream_context_create( $options );

			$url = 'https://www.ventureloop.com/ventureloop/login.php';

			$contents = file_get_contents( $url, false, $context );

			$headers = $this->parse_headers( $http_response_header );

			if ( !isset( $headers['Set-Cookie'] ) ) {
				throw new Exception( 'Login failed - no cookie was set.' );
			}
			else if ( is_array( $headers['Set-Cookie'] ) ) {
				$session = null;

				// loop through them, trying to find the session value. right now it's PHPSESSID, but we try to be a little generic in case it changes - though that would break more than just this
				foreach ( $headers['Set-Cookie'] as $v ) {
					$lower_v = strtolower( $v );
					if ( strpos( $lower_v, 'session' ) !== false || strpos( $lower_v, 'sess' ) !== false ) {
						$session = $v;
					}
				}
			}
			else {
				$session = null;

				$lower_v = strtolower( $headers['Set-Cookie'] );

				// there is only one set-cookie header, so let's see if it looks like the session
				if ( strpos( $lower_v, 'session' ) !== false || strpos( $lower_v, 'sess' ) !== false ) {
					$session = $headers['Set-Cookie'];
				}
			}

			// if we couldn't find something that looks like a session...
			if ( $session == null ) {
				throw new Exception( 'Login Failed - cookies were set, but none contained a session' );
			}

			// now we parse out the cookie header
			// first, hammer it into a format we can parse easily
			$session = str_replace( ';', '&', $session );
			echo $session;

			parse_str( $session, $pieces );
			print_r($pieces);

			// keys should now be COOKIE_NAME, expires, and path
			$keys = array_keys( $pieces );

			$this->session_name = array_shift( $keys );
			$this->session_id = $pieces[ $this->session_name ];
			$this->session_expires = isset( $pieces['expires'] ) ? $pieces['expires'] : '';		// expires is apparently not always sent... odd
			$this->session_path = $pieces['path'];

			return $this;

		}

		private function parse_headers( $header ) {

			$headers = array();

			foreach ( $header as $h ) {
				if ( strpos( $h, ':' ) !== false ) {
					// it appears to be a standard header (ie: Content-Type: text/html) so split it apart
					list( $h_k, $h_v ) = explode( ':', $h, 2 );

					// make sure we don't overwrite any existing headers, things like Set-Cookie can come multiple times
					if ( isset( $headers[ $h_k ] ) ) {
						if ( is_array( $headers[ $h_k ] ) ) {
							// it's already an array, just append our new value
							$headers[ $h_k ][] = $h_v;
						}
						else {
							// it's not already an array, create a new one with our current and previous values
							$headers[ $h_k ] = array( $headers[ $h_k ], $h_v );
						}
					}
					else {
						// it doesn't already exist, just set it
						$headers[ $h_k ] = $h_v;
					}
				}
				else if ( strpos( $h, 'HTTP/' ) === 0 ) {
					// it's a status line. set it, overwriting any previous ones, so we always get the most recent at the end of the processing
					$headers['Status'] = $h;
				}
				else {
					// we don't know what this is, just key it up as itself
					$headers[ $h ] = $h;
				}
			}

			return $headers;

		}

		public function distance ( $miles ) {
			$this->distance = $miles;

			return $this;
		}

		public function search_in ( $search_in ) {
			$this->search_in = $search_in;

			return $this;
		}

		public function categories ( $categories ) {
			$this->categories = $categories;

			return $this;
		}

		public function posted ( $posted ) {
			$this->posted = $posted;

			return $this;
		}

		public function search ( $keywords, $location = null, $page = 1 ) {

			$query = array(
				'pageno' => $page,
				'kword' => $keywords,
				'ldata' => $location,
				'jcat' => implode( ',', $this->categories ),
				'distance' => $this->distance,
				'dc' => $this->posted,	// today, yesterday, week, month
				'btn' => 1,
				'g' => 1,		// this seems to indicate that the location includes geo (lat and lon) coordinates - which none of ours do
			);

			// now add the fields we should search in
			foreach ( $this->search_in as $v ) {
				$query[ $v ] = 1;
			}

			$query = http_build_query( $query );

			$url = 'https://www.ventureloop.com/ventureloop/job_search_results.php?' . $query;

			$options = array(
				'http' => array(
					'timeout' => 5,
					'header' => array(),
				),
			);

			// if we have logged in and gotten a session id, hand that back as a header with the request
			if ( $this->session_name != null && $this->session_id != null ) {
				$options['http']['header'][] = 'Cookie: ' . $this->session_name . '=' . $this->session_id;
			}

			$context = stream_context_create( $options );

			$contents = file_get_contents( $url, false, $context );

			if ( $contents === false ) {
				return false;
			}


			// now we want to extract the information we got back
			$dom = new DOMDocument( '1.0', 'utf-8' );
			$dom->validateOnParse = false;

			// this is only a partial document, and VERY VERY invalid
			@$dom->loadHTML( $contents );

			$xpath = new DOMXPath( $dom );

			// first, figure out the number of jobs found
			$number_jobs = $this->extract_number_jobs( $xpath );

			// next, the total number of pages
			$number_pages = $this->extract_number_pages( $xpath );

			// and which one we're on now
			$current_page = $this->extract_current_page( $xpath );

			// now for the fun stuff, the actual jobs!

			// headers first
			$headers = $this->extract_headers( $xpath );

			// now the data rows
			$jobs = $this->extract_jobs( $xpath );

			// and finally, clean up the jobs we found
			$jobs = $this->normalize_jobs( $headers, $jobs );

			$results = new VentureLoop_Results();

			// pagination and result totals
			$results->total_pages = $number_pages;
			$results->current_page = $current_page;
			$results->total_results = $number_jobs;

			// the search criteria these are for
			$results->distance = $this->distance;
			$results->categories = $this->categories;
			$results->search_in = $this->search_in;
			$results->posted = $this->posted;
			$results->keywords = $keywords;
			$results->location = $location;

			// session info
			$results->session_name = $this->session_name;
			$results->session_id = $this->session_id;
			$results->session_expires = $this->session_expires;
			$results->session_path = $this->session_path;

			// and finally, the jobs!
			$results->jobs = $jobs;

			// save the results we just got
			$this->results = $results;

			// i've done what you wanted, please, can i go home now?
			return $this;

		}

		private function can_get_more ( ) {

			// make sure we've actually searched for something, first
			if ( $this->results == null ) {
				throw new LogicException('You requested more results, but it looks like we haven\'t searched for anything yet!');
			}

			// also, make sure there are more to get
			if ( $this->results->current_page == $this->results->total_pages ) {
				throw new OutOfBoundsException('You requested more results, but there aren\'t any to get!');
			}

		}

		/**
		 * Get the next page of search results.
		 *
		 * @param  boolean $overwrite If true, the result object will only contain jobs from the new page (ie: the old ones were overwritten). If false, it will contain a merged array of all the jobs we've gotten so far for this search.
		 * @return VentureLoop             The current object, for factory-pattern chaining.
		 */
		public function get_next_page ( $overwrite = false ) {

			// run our sanity checks to make sure we can actually get more
			$this->can_get_more();

			// save the last search we performed before it's overwritten
			$results = $this->results;

			$next_page = $this->results->current_page + 1;

			// and do the search again, for the next page
			$this->search( $this->results->keywords, $this->results->location, $next_page );

			// now $this->results has been overwritten with the next page of results. did we want to overwrite the old ones, or merge the new ones on top of them?
			if ( $overwrite != true ) {
				$full_results = array_merge( $results->jobs, $this->results->jobs );

				$this->results->jobs = $full_results;
			}

			return $this;

		}

		public function get_all ( ) {

			// make sure we've already gotten some results
			$this->can_get_more();

			// now this sucks - basically, you could search, then get_next_page(true), so $results->jobs only contains the last page's results
			// but we're called get_all, and i'd expect that to return ALL the results, wouldn't i? if not, i'd be called get_rest or something equally stupid.
			// so we start over at 1 :\

			// first, run the main search again so we know we're at page 1 and there aren't other results mucking up the works
			$this->search( $this->results->keywords, $this->results->location, 1 );

			// now, just wrap around get_next_page until we're done
			for ( $i = 2; $i <= $this->results->total_pages; $i++ ) {
				$this->get_next_page();
			}

			return $this;

		}

		public function jobs ( ) {

			if ( $this->results == null ) {
				throw new LogicException('You want the jobs? Well, you have to search for something first!');
			}

			return $this->results->jobs;

		}

		private function extract_number_jobs ( $xpath ) {

			$number = null;

			$number_jobs = $xpath->query( './/div[@class="formLs"]' );

			if ( $number_jobs->length > 0 ) {
				$number_jobs = $number_jobs->item(0)->nodeValue;

				// and parse out the actual number
				preg_match( '/(\d+)/', $number_jobs, $matches );

				if ( count( $matches ) >= 2 ) {
					$number = $matches[1];
				}
			}

			return $number;

		}

		private function extract_current_page ( $xpath ) {

			$current_page = $xpath->query( './/span[@class="pag_txt_current"]' );

			if ( $current_page->length < 1 ) {
				$current_page = null;
			}
			else {
				$current_page = $current_page->item(0)->nodeValue;
			}

			return $current_page;

		}

		private function extract_number_pages ( $xpath ) {

			$number_pages = $xpath->query( './/span[@class="pag_txt_tot"]' );

			if ( $number_pages->length < 1 ) {
				$number_pages = null;
			}
			else {
				$number_pages = $number_pages->item(0)->nodeValue;
			}

			return $number_pages;

		}

		private function extract_headers ( $xpath ) {

			$headers = array();

			$header_ths = $xpath->query( './/table/tr[@class="head"]/th' );

			if ( $header_ths->length > 0 ) {

				foreach ( $header_ths as $header_th ) {
					$header = $header_th->nodeValue;
					$header = trim( $header );

					$key = strtolower( $header );
					$key = str_replace( ' ', '_', $key );

					$headers[ $key ] = $header;
				}

			}

			return $headers;

		}

		private function extract_jobs ( $xpath ) {

			$jobs = array();

			$trs = $xpath->query( './/table/tr[not(@class="head")]' );

			if ( $trs->length > 0 ) {

				foreach ( $trs as $tr ) {
					// find all the td for this row
					$tds = $xpath->query( './td', $tr );

					$cells = array();
					foreach ( $tds as $td ) {

						// see if its contains links - we just want those
						$links = $xpath->query( './a', $td );

						if ( $links->length > 0 ) {

							$ls = array();
							foreach ( $links as $link ) {

								$url = $link->getAttribute('href');
								$text = $link->nodeValue;

								// pull the ID out of the url in a very quick and dirty way
								$id = substr( $url, strpos( $url, 'id=' ) + strlen( 'id=' ) );

								$ls[] = array(
									'id' => $id,
									'text' => $text,
								);

							}

							// and that's our content for this cell
							$cells[] = $ls;

						}
						else {
							// otherwise, just the content is fine
							$cells[] = $td->nodeValue;
						}

					}

					// and save the job
					$jobs[] = $cells;

				}

			}

			return $jobs;

		}

		/**
		 * Convert an array of arrays containing job keys and values pulled from the DOM into nice, clean objects.
		 *
		 * This is where you'd need to change the logic if the headers or number of rows change, everything else should be generic.
		 *
		 * @param  array $jobs Multidimensional array of jobs "rows" => "cells" to convert.
		 * @return array       Array of VentureLoop_Job objects.
		 */
		private function normalize_jobs ( $headers, $jobs ) {

			// the keys we want are actually the keys of the headers array - values are textual display labels
			$headers = array_keys( $headers );

			$cleaned_jobs = array();

			foreach ( $jobs as $job ) {

				$job = array_combine( $headers, $job );

				// parse out the date it was posted
				$posted_on = $job['date'];

				// php has trouble parsing the format by default, so we specify it
				$posted_on = DateTime::createFromFormat( 'm-d-Y', $posted_on );

				// there is no time parameter in the posted_on date, so we'll set it to midnight, otherwise by default it's the current (parsing) time
				$posted_on->setTime( 0, 0 );

				// parse out the first company - there should only be one, but it's in an array of links
				$company = array_shift( $job['company'] );

				$c = new VentureLoop_Company();
				$c->id = $company['id'];
				$c->name = $company['text'];

				// now any investors
				$investors = array();
				foreach ( $job['vc'] as $vc ) {

					$i = new VentureLoop_Investor();
					$i->id = $vc['id'];
					$i->name = $vc['text'];

					$investors[] = $i;

				}

				// and finally the title and location
				$job_title = array_shift( $job['job_title'] );
				$location = $job['location'];

				// and build our custom structure
				$j = new VentureLoop_Job();
				$j->id = $job_title['id'];
				$j->title = $job_title['text'];
				$j->posted_on = $posted_on;
				$j->company = $c;
				$j->investors = $investors;
				$j->location = $location;

				$cleaned_jobs[] = $j;

			}

			return $cleaned_jobs;

		}

	}

	abstract class VentureLoop_URL_Addressable {
		protected $url;
		protected $type;

		protected $url_maps = array(
			'job' => 'jobdetail.php?jobid=%u',
			'company' => 'companyprofile.php?cid=%u',
			'investor' => 'investorprofile.php?cid=%u',
		);

		public function __get ( $name ) {

			if ( $name == 'url' ) {

				if ( isset( $this->type ) && isset( $this->url_maps[ $this->type ]) ) {
					$url = $this->url_maps[ $this->type ];
					$url = sprintf( $url, $this->id );

					return $url;
				}
				else {
					return null;
				}

			}

			return parent::__get( $url );

		}
	}

	abstract class VentureLoop_Entity extends VentureLoop_URL_Addressable {
		public $id;
		public $url;
		public $name;
	}

	class VentureLoop_Job extends VentureLoop_Entity {
		public $id;
		public $url;
		public $title;
		public $posted_on;
		public $company;
		public $investors = array();
	}

	class VentureLoop_Investor extends VentureLoop_Entity {}

	class VentureLoop_Company extends VentureLoop_Entity {}

	class VentureLoop_Results {
		// where are we and where can we go?
		public $current_page;
		public $total_pages;
		public $total_results;

		// the criteria searched for
		public $distance;
		public $categories;
		public $search_in;
		public $posted;
		public $keywords;
		public $location;

		// and what we got back
		public $jobs;

		// in case we want to make more requests later
		public $session_name;
		public $session_id;
		public $session_expires;
		public $session_path;
	}

?>