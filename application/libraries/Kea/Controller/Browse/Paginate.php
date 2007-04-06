<?php
require_once 'Kea/Controller/Action.php';
require_once 'Kea/Controller/Browse/Interface.php';
/**
 * In charge of paginated browsing for the controllers
 *
 * @package Omeka
 **/
class Kea_Controller_Browse_Paginate extends Kea_Controller_Browse_Abstract
{	
	protected $_options = array('num_links' => 5, 'limit' => 10);
		
	public function browse()
	{
		$pluralVar = $this->getOption('pluralized');
		if(empty($pluralVar)) $pluralVar = $this->formatPluralized();
		
		//per_page is either a $_POST var, db option, passed via constructor or default to 10 (in that order)
		
		$per_page = $this->getOption('limit');
				
		//page 
		$page = $this->getOption('page');
		if(!$page) $page = 1;
		
		$offset = ($page - 1) * $per_page;
		
		$query = $this->getQuery();

		
		Kea_Controller_Plugin_Broker::getInstance()->filterBrowse($this);

		$query = $this->buildQuery();

		$countQuery = clone $query;
		$total = $countQuery->count();
		
		settype($per_page, 'int');
		$query->limit($per_page);
		
		settype($offset, 'int');
		$query->offset($offset);

//		echo $query;
		$$pluralVar = $query->execute();
		
		//Figure out the pagination 
		
		//num_links defaults to 5, we can make this dynamic as well
		$num_links = $this->getOption('num_links');
		
		//Url is most likely going to be the current one, we can make this dynamic too if needed
		$req = $this->getRequest();
		$url = $req->getBaseUrl().DIRECTORY_SEPARATOR.$pluralVar.DIRECTORY_SEPARATOR.'browse'.DIRECTORY_SEPARATOR;
		
		$pagination = $this->pagination($page, $per_page, $total, $num_links, $url);
				
		return $this->_controller->render($pluralVar."/browse.php", compact("total", "offset", $pluralVar, "per_page", "page", "pagination"));
	}
	
	/**
	 *	The pagination function from the old version of the software
	 *  It looks more complicated than it might need to be, but its also more flexible.  We may decide to simplify it later
	 */
	public function pagination( $page = 1, $per_page, $total, $num_links, $link, $page_query = null )
	{
		$num_pages = ceil( $total / $per_page );
		$num_links = ($num_links > $num_pages) ? $num_pages : $num_links;

		$query = !empty( $_SERVER['QUERY_STRING'] ) ? '?' . $_SERVER['QUERY_STRING'] : null;
		
		if ( $page_query )
		{
			//Using the power of regexp we replace only part of the query string related to the pagination
			if( preg_match( '/[\?&]'.$page_query.'/', $query ) ) 
			{
				$p = '/([\?&])('.preg_quote($page_query) . ')=([0-9]*)/';
				$pattern = preg_replace( $p, '$1$2='.preg_quote('%PAGE%'), $query );
			}
			else $pattern = ( !empty($query) )  ? $query . '&' . $page_query . '=' . '%PAGE%' : '?' . $page_query . '=' . '%PAGE%' ; 
	
		}
		else
		{
			$pattern = '%PAGE%' . $query;
		}

		$html = ' <a href="' . $link . str_replace('%PAGE%', 1, $pattern) . '">First</a> |';

		if( $page > 1 ) {
			$html .= ' <a href="' . $link . str_replace('%PAGE%', ($page - 1), $pattern) . '">&lt; Prev</a> |';
		} else {
			$html .= ' &lt; Prev |';
		}

		$buffer = floor( ( $num_links - 1 ) / 2 );
		$start_link = ( ($page - $buffer) > 0 ) ? ($page - $buffer) : 1;
		$end_link = ( ($page + $buffer) < $num_pages ) ? ($page + $buffer) : $num_pages;

		if( $start_link == 1 ) {
			$end_link += ( $num_links - $end_link );
		}elseif( $end_link == $num_pages ) {
			$start_link -= ( $num_links - ($end_link - $start_link ) - 1 );
		}

		for( $i = $start_link; $i < $end_link+1; $i++) {
			if( $i <= $num_pages ) {
				if( $page == $i ) {
					$html .= ' ' . $i . ' |';
				} else {
					$html .= ' <a href="' . $link . str_replace('%PAGE%', $i, $pattern) . '">' . ($i) . '</a> |';
				}
			}
		}

		if( $page < $num_pages ) {
			$html .= ' <a href="' . $link . str_replace('%PAGE%', ($page + 1), $pattern). '">Next &gt;</a> |';
		} else {
			$html .= ' Next &gt; |';
		}

		$html .= ' <a href="' . $link . str_replace('%PAGE%', ($num_pages), $pattern) . '">Last</a> ';

		$html .= '<select class="pagination-link" onchange="location.href = \''.$link. str_replace('%PAGE%', '\' + this.value + \'', $pattern) .'\'">'; 
		$html .= '<option>Page:&nbsp;&nbsp;</option>';
		for( $i = 0; $i < $num_pages; $i++ ) {
			$html .= '<option value="' . ($i + 1) . '"';
			//if( $page == ($i+1) ) $html .= ' selected ';
			$html .= '>' . ($i + 1) . '</option>';
		}
		$html .= '</select>';

		return $html;
	}
	
} // END class Kea_Controller_Browse_Paginate

?>