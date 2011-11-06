# PHP Pagination Class

Based off my original "Making Pagination Easier" blog post (http://www.michaelpeacock.co.uk/blog/entry/making-pagination-easier-with-php-class), however it has been altered to adjust the query so that the total count can be calculated by querying for a single row with a count included.

## Usage

	<?php
	require_once('pagination.class.php');
	// establish a database connection in your code
	$db = mysqli_connect( 'localhost', 'root', '', 'database');
	// create the pagination object, and pass the database connection
	$p = new Pagination( $db );
	// set a limit
	$p->setLimit(3);
	// set an offset
	$p->setOffset( isset( $_GET['page_number'] ) ? intval( $_GET['page_number'] ) : 0 );
	// set the query
	$p->setQuery( "SELECT * FROM something " );
	// do the pagination
	$p->generatePagination();
	// what we do with these variables depends on the tempLating system used: the pagination object could send them direct to the template system for us
	// get an array of results
	$results = $p->getResults();
	// get the current page number
	$current_page = $p->getCurrentPage();
	// get the number of pages
	$number_of_pages = $p->getNumPages();
	
	$first_page_link = $p->isFirst() ? "" : "<a href='?page_number=0'>First page</a>";
	$previous_page_link = $p->isFirst() ? "" : "<a href='?page_number=".( $p->getCurrentPage() - 2 )."'>Previous page</a>";
	
	$next_page_link = $p->isLast() ? "" : "<a href='?page_number=" . $p->getCurrentPage() . "'>Next page</a>";
	$last_page_link = $p->isLast() ? "" : "<a href='?page_number=".( $p->getCurrentPage() - 1 )."'>Last page</a>";
	
	
	echo '<ul>';
	foreach( $results as $result )
	{
		echo '<li>' . $result['something'] . '</li>';
	}
	echo '</ul>';
	echo 'Page ' . $current_page . ' of ' . $number_of_pages . '<br />';
	echo $first_page_link .  ' ' . $previous_page_link . ' ' . $next_page_link . ' ' . $last_page_link;
	?>