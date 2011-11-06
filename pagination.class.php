<?php
/**
 * Pagination class
 * Making pagination of records easy(ier)
 * @author Michael Peacock
 * @url www.michaelpeacock.co.uk
 */
class Pagination {
	
	/**
	 * MySQLi Connection
	 */
	private $db;
	
	/**
	 * The query we will be paginating
	 */
	private $query = "";
	
	/**
	 * The processed query which will be executed
	 */
	private $executedQuery = "";
	
	/**
	 * The results from the query
	 */
	private $results;
	
	/**
	 * The maximum number of results to display per page
	 */
	private $limit = 25;
	
	/**
	 * The results offset - i.e. page we are on (-1)
	 */
	private $offset = 0;
	
	/**
	 * The number of rows there were in the query passed
	 */
	private $numRows;
	
	/**
	 * The number of rows on the current page (main use if on last page, may not have as many as limit on the page)
	 */
	private $numRowsPage;
	
	/**
	 * Number of pages of results there are
	 */
	private $numPages;
	
	/**
	 * Is this the first page of results?
	 */
	private $isFirst;
	
	/**
	 * Is this the last page of results?
	 */
	private $isLast;
	
	/**
	 * The current page we are on
	 */
	private $currentPage;
	
	/**
	 * Our constructor
	 * @param mysqli $mysqli_link
	 * @return void
	 */
    function __construct( $mysqli_link ) 
    {
		$this->db = $mysqli_link;
	}
    
    /**
     * Process the query, and set the paginated properties
     * @return bool (true if there are some rows on the page, false if there are not)
     */
    public function generatePagination()
    {
    	$temp_query = $this->query;
    	
    	// its more efficient to query one row (if possible) and include a count, as opposed to querying all of them
    	if( preg_match( '#SELECT DISTINCT((.+?)),#si', $temp_query ) > 0 )
    	{
    		// this is a distinct query, we really have to query them all :-(
	    	$q = mysqli_query( $this->db, $temp_query );
	    	$nums = mysqli_num_rows( $q );
	    	$this->numRows = $nums;
    	}
    	else
    	{
    		// normal query, let's strip out everything before the "primary" FROM 
    		$q = mysqli_query( $this->db, "SELECT COUNT(*) AS nums " . $this->excludePrimarySelects( $temp_query ) . " LIMIT 1" );
    		if( mysqli_num_rows( $q ) == 1 )
    		{
    			// how many rows?
    			$row = mysqli_fetch_array( $q, MYSQLI_ASSOC );
    			$this->numRows = $row['nums'];
    		}
    		else
    		{
    			// query didn't work...0 rows
    			$this->numRows = 0;
    		}
    	}
    	    	
    	// limit!
    	$this->executedQuery = $temp_query . " LIMIT " . ( $this->offset * $this->limit ) . ", " . $this->limit;
    	
    	$q = mysqli_query( $this->db, $this->executedQuery );
    	while( $row = mysqli_fetch_array( $q, MYSQLI_ASSOC ) )
    	{
    		$this->results[] = $row;
    	}
    	
    	// be nice...do some calculations
		
		// num pages
		$this->numPages = ceil($this->numRows / $this->limit);
		// is first
		$this->isFirst = ( $this->offset == 0 ) ? true : false;
		// is last
		$this->isLast = ( ( $this->offset + 1 ) == $this->numPages ) ? true : false;
		// current page
		$this->currentPage = ( $this->numPages == 0 ) ? 0 : $this->offset +1;
		$this->numRowsPage = mysqli_num_rows( $q );
		
		return ( $this->numRowsPage == 0 ) ? false : true;
    	
    }
    
    //===========================================================================//
    //								HELPER METHODS								 //
    //===========================================================================//
    
    /**
     * Exclude the primary selects from the SQL query
     * @param String $sql the SQL query
     * @return String the query starting with FROM
     */
    private function excludePrimarySelects( $sql )
    {
    	$word = "from";
    	$wordLength = strlen( $word );
    	$left = 0;
    	$right = 0;
    	$within = 0;
    	$precedingSQLStatement = "";
    	for( $i = 0; $i < ( strlen( $sql ) ); $i++ )
    	{
    		$left = ( $sql[ $i ] == "(" ) ? $left+1 : $left;
    		$right = ( $sql[ $i ] == ")" ) ? $right+1 : $right;
    		if( $left === $right )
    		{
    			if( $within < $wordLength && strcasecmp( $sql[ $i ], $word[ $within ] ) == 0 )
    			{
    				$within++;
    			}
    			elseif( $within <> $wordLength )
    			{
    				$within = 0;
    			}
    		}
    		elseif( $within <> $wordLength )
    		{
    			$within = 0;
    		}
    		
    		if( $within < $wordLength )
	    	{
	    		$precedingSQLStatement .= $sql[ $i ];
	    	}
	    	elseif( $within == $wordLength )
	    	{
	    		$precedingSQLStatement .= $sql[ $i ];
	    		break;
	    	}
    	}

    	return str_replace( $precedingSQLStatement, " FROM ", $sql );
    }
    
    
    //===========================================================================//
    //						GETTER AND SETTER METHODS							 //
    //===========================================================================//
    
    /**
     * Set the query to be paginated
     * @param String $sql the query
     * @return void
     */
    public function setQuery( $sql )
    {
    	$this->query = $sql;
    }
    
    /**
     * Set the limit of how many results should be displayed per page
     * @param int $limit the limit
     * @return void
     */
    public function setLimit( $limit )
    {
    	$this->limit = $limit;	
    }
    
    /**
     * Set the offset - i.e. if offset is 1, then we show the next page of results
     * @param int $offset the offset
     * @return void
     */
    public function setOffset( $offset )
    {
    	$this->offset = $offset;
    }
    
    /**
     * Get the result set
     * @return array
     */
    public function getResults()
    {
    	return $this->results;
    }
    
    /**
     * Get the number of pages of results there are
     * @return int
     */
    public function getNumPages()
    {
    	return $this->numPages;
    }
    
    /**
     * Is this page the first page of results?
     * @return bool
     */
    public function isFirst()
    {
    	return $this->isFirst;
    }
    
    /**
     * Is this page the last page of results?
     * @return bool
     */
    public function isLast()
    {
    	return $this->isLast;
    }
    
    /**
     * Get the current page within the paginated results we are viewing
     * @return int
     */
    public function getCurrentPage()
    {
    	return $this->currentPage;
    }
    
    /**
     * Get the number of rows there are on the current page
     * @return int
     */
    public function getNumRowsPage()
    {
    	return $this->numRowsPage;    	
    }
    
}
?>