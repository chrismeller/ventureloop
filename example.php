<?php

	// remember to use utf-8 -- you'll get some special characters back in the results
	header('Content-Type: text/html; charset=utf-8' );

	date_default_timezone_set('America/Chicago');

	require('ventureloop.php');

	$v = VentureLoop::factory()->search( 'php', 'Austin, TX' )->get_all();

	?>

		<table>
			<thead>
				<tr>
					<th>Date Posted</th>
					<th>Title</th>
					<th>Company</th>
					<th>Investors</th>
					<th>Location</th>
				</tr>
			</thead>
			<tbody>
				<?php

					foreach ( $v->jobs() as $job ) {
						?>
							<tr>
								<td><?php echo $job->posted_on->format('m/d/Y'); ?></td>
								<td><a href="<?php echo $job->url; ?>"><?php echo $job->title; ?></a></td>
								<td><a href="<?php echo $job->company->url; ?>"><?php echo $job->company->name; ?></a></td>
								<td>
									<ul>
										<?php

											foreach ( $job->investors as $investor ) {
												?>
													<li><a href="<?php echo $investor->url; ?>"><?php echo $investor->name; ?></a></li>
												<?php
											}

										?>
									</ul>
								</td>
								<td>
									<?php

										if ( count( $job->location ) == 1 ) {
											echo $job->location[0];
										}
										else {
											echo '<ul><li>' . implode( '</li><li>', $job->location ) . '</li></ul>';
										}

									?>
								</td>
							</tr>
						<?php
					}

				?>
			</tbody>
		</table>