<?php

/**
 * @package Simple_Move_Comments
 * @author Peter Hilbring
 * @version 1.1
 *
 * @param string $_REQUEST['select'] ['src', 'dst']
 * @param int $_REQUEST['author']
 * @param char $_REQUEST['filter_1'] ['a', 'b', 'c']
 * @param string $_REQUEST['filter_2']
 * @param string $_REQUEST['order'] ['na', 'nd', 'da', 'dd']
 */

/** Include the bootstrap for setting up WordPress environment */
include( '../../../wp-load.php' );

if ( !is_user_logged_in() )
	wp_die( _e('You must be logged in to run this script.', 'simple-move-comments') );

if ( !( isset($_REQUEST['select']) and in_array($_REQUEST['select'], array( 'src', 'dst' )) ) )
	wp_die( _e('Parameter failure.', 'simple-move-comments') );

class ph_move_comments_ajax {

	/**
	 * Constructor
	 *
	 * @returns Nothing
	 */
	function __construct()
	{
		global $wpdb;

		$where = 'src' == $_REQUEST['select'] ? 'AND '.$wpdb->posts.'.comment_count != 0' : '';
		if ( isset($_REQUEST['author']) and is_numeric($_REQUEST['author']) and ($_REQUEST['author'] >= 0) )
			$where .= $wpdb->prepare(' AND '.$wpdb->users.'.ID = %d', $_REQUEST['author']);

		if ( isset($_REQUEST['filter_1']) and in_array($_REQUEST['filter_1'], array( 'a', 'b', 'c' )) and isset($_REQUEST['filter_2']) ) {
			switch($_REQUEST['filter_1']) {
				case 'a':
					break;
				case 'b':
						$val = $_REQUEST['filter_2'].'%';
						$where .= $wpdb->prepare(' AND '.$wpdb->posts.'.post_title LIKE %s', $val);
					break;
				case 'c':
						$val = '%'.$_REQUEST['filter_2'].'%';
						$where .= $wpdb->prepare(' AND '.$wpdb->posts.'.post_title LIKE %s', $val);
					break;
			}
		}

		if ( isset($_REQUEST['order']) and in_array($_REQUEST['order'], array( 'na', 'nd', 'da', 'dd' )) ) {
			switch($_REQUEST['order']) {
				case 'na':
					$where .= ' ORDER BY '.$wpdb->posts.'.post_title ASC';
					break;
				case 'nd':
					$where .= ' ORDER BY '.$wpdb->posts.'.post_title DESC';
					break;
				case 'da':
					$where .= ' ORDER BY '.$wpdb->posts.'.post_date ASC';
					break;
				case 'dd':
					$where .= ' ORDER BY '.$wpdb->posts.'.post_date DESC';
					break;
			}
		}

		$query = 'SELECT '.$wpdb->posts.'.ID, '.$wpdb->posts.'.post_title, '.$wpdb->posts.'.post_author, ';
		$query .= $wpdb->posts.'.comment_count, '.$wpdb->users.'.display_name ';
		$query .= 'FROM '.$wpdb->posts.' ';
		$query .= 'INNER JOIN '.$wpdb->users.' ON ('.$wpdb->posts.'.post_author = '.$wpdb->users.'.ID) ';
		$query .= 'WHERE '.$wpdb->posts.'.comment_status = "open" ';
		$query .= 'AND ('.$wpdb->posts.'.post_type = "page" OR '.$wpdb->posts.'.post_type = "post") ';
		$query .= $where;

		$myrows = $wpdb->get_results($query);

		$charset = $wpdb->get_var('SELECT '.$wpdb->options.'.option_value FROM '.$wpdb->options.' WHERE '.$wpdb->options.'.option_name = "blog_charset"');
		if ( stristr($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml') ){
			header('Content-type: application/xhtml+xml;charset='.$charset);
		} else {
			header('Content-type: text/xml;charset='.$charset);
		}
		$xml = "<?xml version='1.0' encoding='".$charset."'?>";
		$xml .= '<select object="'.$_REQUEST['select'].'">';
		foreach ( $myrows as $val ) {
			$xml .= '<option>';
			$xml .= '<id>'.$val->ID.'</id>';
			$xml .= '<title><![CDATA['.$val->post_title.']]></title>';
			$xml .= '<author><![CDATA['.$val->display_name.']]></author>';
			$xml .= '<count>'.sprintf(_n('%d comment', '%d comments', $val->comment_count, 'simple-move-comments'), $val->comment_count).'</count>';
			$xml .= '</option>';
		}
		$xml .= '</select>';
		echo $xml;
	}

}

$ph_move_comments_ajax = new ph_move_comments_ajax();

?>
