<?php
/**
 * @package Simple_Move_Comments
 * @author Peter Hilbring
 * @version 1.1
 */
/*
Plugin Name: Simple Move Comments
Plugin URI: http://www.hilbring.de/2009/09/13/wordpress-plugin-simple-move-comments/
Description: Moves all comments from one page/post to another.
Version: 1.1
Author: Peter Hilbring
Author URI: http://www.hilbring.de/


        Copyright 2009  Peter Hilbring  (email : peter@hilbring.de)

        This program is free software; you can redistribute it and/or modify
        it under the terms of the GNU General Public License as published by
        the Free Software Foundation; either version 2 of the License, or
        (at your option) any later version.

        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.

        You should have received a copy of the GNU General Public License
        along with this program; if not, write to the Free Software
        Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class ph_move_comments {

	protected $plugin_dir = '';
	protected $logfile = '';

	/**
	 * Constructor
	 *
	 * @returns Nothing
	 */
	function __construct()
	{
		global $wp_version;
		if ( version_compare($wp_version, '2.8', '>=') ) {
			$this->plugin_dir = basename(dirname(__FILE__));
			$this->logfile = WP_PLUGIN_DIR.'/'.plugin_basename(dirname(__FILE__)).'/'.basename(__FILE__, '.php').'.log';
			load_plugin_textdomain('simple-move-comments', 'wp-content/plugins/'.$this->plugin_dir.'/i18n', $this->plugin_dir.'/i18n');
			add_action('admin_menu', array(&$this, 'init_hooks'));
		} else {
			$this->version_warning();
		}
	}

	/**
	 * Creates and registers admin page
	 */
	function init_hooks()
	{
		add_options_page('ph_move_comments', __('Move Comments', 'simple-move-comments'), 8, __FILE__, array(&$this, 'phmc_options_page'));
	}

	/**
	 * Displays a warning when installed in an old Wordpress Version
	 *
	 * @returns	Nothing
	 */
	function version_warning() {
		echo '<div class="updated fade"><p><strong>Simple Move Comments '.__('requires WordPress version 2.8 or later!', 'simple-move-comments').'</strong></p></div>';
	}

	/**
	 * The Admin Page and all it's functions
	 */
	function phmc_options_page()
	{
		global $wpdb;
?>
		<script type="text/javascript">
			<!--
			var $phmc_j = jQuery.noConflict();
			-->
		</script>
<?php
		echo '<div class="wrap"><h2>'.__('Move Comments', 'simple-move-comments').'</h2>';

		if ( isset($_POST['phmc']['move']) ) {
			if ( isset($_POST['phmc']['old_id']) and isset($_POST['phmc']['new_id']) and is_numeric($_POST['phmc']['old_id']) and is_numeric($_POST['phmc']['new_id']) ) {
				$old_id = $_POST['phmc']['old_id'];
				$new_id = $_POST['phmc']['new_id'];
?>
				<script type="text/javascript">
					<!--
					// set form-values
					$phmc_j(function(){
						$phmc_j('#phmc_from_author').val(<?php echo $_POST['phmc']['from_author'];?>);
						$phmc_j('#phmc_to_author').val(<?php echo $_POST['phmc']['to_author'];?>);
						$phmc_j('#phmc_from_filter_1').val('<?php echo $_POST['phmc']['from_filter_1'];?>');
						$phmc_j('#phmc_to_filter_1').val('<?php echo $_POST['phmc']['to_filter_1'];?>');
						$phmc_j('#phmc_from_filter_2').val('<?php echo $_POST['phmc']['from_filter_2'];?>');
						$phmc_j('#phmc_to_filter_2').val('<?php echo $_POST['phmc']['to_filter_2'];?>');
						$phmc_j('#phmc_from_order').val('<?php echo $_POST['phmc']['from_order'];?>');
						$phmc_j('#phmc_to_order').val('<?php echo $_POST['phmc']['to_order'];?>');
					});
					-->
				</script>
<?php
				$query = $wpdb->prepare('UPDATE '.$wpdb->comments.' SET comment_post_ID = %d WHERE comment_post_ID = %d', $new_id, $old_id);
				$wpdb->query($query);
				$query = $wpdb->prepare('SELECT '.$wpdb->posts.'.comment_count FROM '.$wpdb->posts.' WHERE ID = %d', $old_id);
				$count_posts = $wpdb->get_var($query);
				$query = $wpdb->prepare('SELECT '.$wpdb->posts.'.post_title FROM '.$wpdb->posts.' WHERE ID = %d', $old_id);
				$old_post_title = '&bdquo;'.$wpdb->get_var($query).'&rdquo;';
				$query = $wpdb->prepare('SELECT '.$wpdb->posts.'.post_title FROM '.$wpdb->posts.' WHERE ID = %d', $new_id);
				$new_post_title = '&bdquo;'.$wpdb->get_var($query).'&rdquo;';
				$query = $wpdb->prepare('UPDATE '.$wpdb->posts.' SET '.$wpdb->posts.'.comment_count = 0 WHERE ID = %d', $old_id);
				$wpdb->query($query);
				$query = $wpdb->prepare('UPDATE '.$wpdb->posts.' SET '.$wpdb->posts.'.comment_count = '.$wpdb->posts.'.comment_count + '.$count_posts.' WHERE ID = %d', $new_id);
				$wpdb->query($query);
				$fh = fopen($this->logfile, 'ab');
				$comments = sprintf(_n('%d comment', '%d comments', $count_posts, 'simple-move-comments'), $count_posts);
				fprintf($fh, '[%4$s] '.__('Moved %1$s from %2$s to %3$s', 'simple-move-comments')."\n", $comments, $old_post_title, $new_post_title, date('r'));
				fclose($fh);
			}
		}
?>
		<script type="text/javascript">
			<!--
			$phmc_j(function(){
				fillSelect('from');
				fillSelect('to');

				// from-filter elements changed -> ajax request
				$phmc_j('#phmc_from_author').change(function(e){
					fillSelect('from');
				});
				if('a' != $phmc_j('#phmc_from_filter_1').val()) {
					$phmc_j('#phmc_from_filter_2').css('display', 'inline');
				} else {
					$phmc_j('#phmc_from_filter_2').css('display', 'none');
				}
				$phmc_j('#phmc_from_filter_1').change(function(e){
					if('a' != $phmc_j('#phmc_from_filter_1').val()) {
						$phmc_j('#phmc_from_filter_2').css('display', 'inline');
					} else {
						$phmc_j('#phmc_from_filter_2').css('display', 'none');
					}
					fillSelect('from');
				});
				$phmc_j('#phmc_from_filter_2').blur(function(e){
					fillSelect('from');
				});
				$phmc_j('#phmc_from_filter_2').keypress(function(e){
					if( 13 == e.which ) fillSelect('from');
				});
				$phmc_j('#phmc_from_order').change(function(e){
					fillSelect('from');
				});

				// to-filter elements changed -> ajax request
				$phmc_j('#phmc_to_author').change(function(e){
					fillSelect('to');
				});
				if('a' != $phmc_j('#phmc_to_filter_1').val()) {
					$phmc_j('#phmc_to_filter_2').css('display', 'inline');
				} else {
					$phmc_j('#phmc_to_filter_2').css('display', 'none');
				}
				$phmc_j('#phmc_to_filter_1').change(function(e){
					if('a' != $phmc_j('#phmc_to_filter_1').val()) {
						$phmc_j('#phmc_to_filter_2').css('display', 'inline');
					} else {
						$phmc_j('#phmc_to_filter_2').css('display', 'none');
					}
					fillSelect('to');
				});
				$phmc_j('#phmc_to_filter_2').blur(function(e){
					fillSelect('to');
				});
				$phmc_j('#phmc_to_filter_2').keypress(function(e){
					if( 13 == e.which ) fillSelect('to');
				});
				$phmc_j('#phmc_to_order').change(function(e){
					fillSelect('to');
				});

				// request comment title list via ajax call
				function fillSelect($phmc_sel){
					var $phmc_s = ('from' == $phmc_sel ? 'src' : 'dst');
					$phmc_j.ajax({
						type: 'POST',
						url: '<?php echo WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),'',plugin_basename(__FILE__));?>_ajax.php',
						data: {
							select: $phmc_s,
							author: ('src' == $phmc_s ? $phmc_j('#phmc_from_author').val() : $phmc_j('#phmc_to_author').val()),
							filter_1: ('src' == $phmc_s ? $phmc_j('#phmc_from_filter_1').val() : $phmc_j('#phmc_to_filter_1').val()),
							filter_2: ('src' == $phmc_s ? $phmc_j('#phmc_from_filter_2').val() : $phmc_j('#phmc_to_filter_2').val()),
							order: ('src' == $phmc_s ? $phmc_j('#phmc_from_order').val() : $phmc_j('#phmc_to_order').val())
						},
						dataType: 'xml',
						cache: false,
						beforeSend: function(){
							if ('src' == $phmc_s){
								$phmc_j('#phmc_loading_from').css('left', $phmc_j('#phmc_from_box').position().left +
									parseInt($phmc_j('#phmc_from_box').css('width').replace(/px/, '')) / 2 - 24);
								$phmc_j('#phmc_loading_from').css('top', $phmc_j('#phmc_from_box').position().top +
									parseInt($phmc_j('#phmc_from_box').css('height').replace(/px/, '')) / 2 - 24);
								$phmc_j('#phmc_loading_from').show();
							} else{
								$phmc_j('#phmc_loading_to').css('left', $phmc_j('#phmc_to_box').position().left +
									parseInt($phmc_j('#phmc_to_box').css('width').replace(/px/, '')) / 2 - 24);
								$phmc_j('#phmc_loading_to').css('top', $phmc_j('#phmc_to_box').position().top +
									parseInt($phmc_j('#phmc_to_box').css('height').replace(/px/, '')) / 2 - 24);
								$phmc_j('#phmc_loading_to').show();
							}
						},
						success: function($phmc_xml){
							var $phmc_elem = '';
							$phmc_j($phmc_xml).find('option').each(function(){
								$phmc_elem = $phmc_elem + '<option value="' + $phmc_j(this).find('id').text() + '">';
								$phmc_elem = $phmc_elem + $phmc_j(this).find('title').text();
								$phmc_elem = $phmc_elem + ' (' + $phmc_j(this).find('author').text() + ') ';
								$phmc_elem = $phmc_elem + $phmc_j(this).find('count').text() + '</option>';
							});
							if ('src' == $phmc_s){
								$phmc_j('#phmc_old_id').empty();
								$phmc_j('#phmc_loading_from').hide();
								$phmc_j('#phmc_old_id').append($phmc_elem);
							} else{
								$phmc_j('#phmc_new_id').empty();
								$phmc_j('#phmc_loading_to').hide();
								$phmc_j('#phmc_new_id').append($phmc_elem);
							}
						}
					});
				};
			});

			$phmc_j(document).ready(function(){
				// form click event (submit)
				$phmc_j('#phmc_submit').click(function(){
					$phmc_j('#phmc_form').submit();
				});
				$phmc_j('#phmc_submit').mousemove(function(e){
					$phmc_j(this).css('cursor', 'pointer');
					$phmc_j('#phmc_submit_info').css({'top':e.pageY+8, 'left':e.pageX+8}).show();
					$phmc_j('#phmc_submit_info').html('<?php _e('Move comments', 'simple-move-comments');?>');
				});
				$phmc_j('#phmc_submit').mouseout(function(){
					$phmc_j('#phmc_submit_info').hide();
				});
			});
			-->
		</script>
		<form method="post" action="" id="phmc_form" accept-charset="<?php echo $wpdb->get_var('SELECT '.$wpdb->options.'.option_value FROM '.$wpdb->options.' WHERE '.$wpdb->options.'.option_name = "blog_charset"');?>">
		<input type="hidden" name="phmc[move]" />
		<table>
		<tr><td>
		<div id="phmc_from_box" style="border: thin solid #000; display: inline-block; padding: 1em; -moz-border-radius: 4px; -webkit-border-radius: 4px;">
		<!-- START Author -->
		<label for="phmc[from_author]"><?php _e('Author ', 'simple-move-comments');?></label>
		<select name="phmc[from_author]" id="phmc_from_author">
		<option value="-1"><?php _e('all', 'simple-move-comments');?></option>
<?php
		$query = 'SELECT '.$wpdb->users.'.ID, '.$wpdb->users.'.display_name FROM '.$wpdb->users.' ORDER BY '.$wpdb->users.'.display_name';
		$myrows = $wpdb->get_results($query);
		foreach ( $myrows as $val ) {
			echo '<option value="'.$val->ID.'">'.$val->display_name.'</option>';
		}
?>
		</select>
		<!-- END Author -->
		<span style="margin-left: 1em; margin-right: 1em;">|</span>
		<!-- START Filter -->
		<label for="phmc[from_filter_1]"><?php _e('Comment ', 'simple-move-comments');?></label>
		<select name="phmc[from_filter_1]" id="phmc_from_filter_1">
		<option value="a"><?php _e('any ', 'simple-move-comments');?></option>
		<option value="b"><?php _e('begins with ', 'simple-move-comments');?></option>
		<option value="c"><?php _e('contains ', 'simple-move-comments');?></option>
		</select>
		<input type="text" name="phmc[from_filter_2]" id="phmc_from_filter_2" />
		<!-- END Filter -->
		<span style="margin-left: 1em; margin-right: 1em;">|</span>
		<!-- START Order -->
		<label for="phmc[from_order]"><?php _e('Sort by ', 'simple-move-comments');?></label>
		<select name="phmc[from_order]" id="phmc_from_order">
		<option value="na"><?php _e('name ascending', 'simple-move-comments');?></option>
		<option value="nd"><?php _e('name descending', 'simple-move-comments');?></option>
		<option value="da"><?php _e('date ascending', 'simple-move-comments');?></option>
		<option value="dd"><?php _e('date descending', 'simple-move-comments');?></option>
		</select>
		<!-- END Order -->
		<br />
		<!-- START Old POST -->
		<label for="phmc[old_id]"><?php _e('From ', 'simple-move-comments');?></label>
		<select name="phmc[old_id]" id="phmc_old_id">
		</select>
		<!-- END Old POST -->
		</div>
		</td></tr>
		<tr><td align="center"><img src="<?php echo WP_PLUGIN_URL.'/'.plugin_basename(dirname(__FILE__));?>/images/jean_victor_balin_arrow_blue_down.png" width="64" height="64" /></td></tr>
		<tr><td>
		<div id="phmc_to_box" style="border: thin solid #000; display: inline-block; padding: 1em; -moz-border-radius: 4px; -webkit-border-radius: 4px;">
		<!-- START Author -->
		<label for="phmc[to_author]"><?php _e('Author ', 'simple-move-comments');?></label>
		<select name="phmc[to_author]" id="phmc_to_author">
		<option value="-1"><?php _e('all', 'simple-move-comments');?></option>
<?php
		$query = 'SELECT '.$wpdb->users.'.ID, '.$wpdb->users.'.display_name FROM '.$wpdb->users.' ORDER BY '.$wpdb->users.'.display_name';
		$myrows = $wpdb->get_results($query);
		foreach ( $myrows as $val ) {
			echo '<option value="'.$val->ID.'">'.$val->display_name.'</option>';
		}
?>
		</select>
		<!-- END Author -->
		<span style="margin-left: 1em; margin-right: 1em;">|</span>
		<!-- START Filter -->
		<label for="phmc[to_filter_1]"><?php _e('Comment ', 'simple-move-comments');?></label>
		<select name="phmc[to_filter_1]" id="phmc_to_filter_1">
		<option value="a"><?php _e('any ', 'simple-move-comments');?></option>
		<option value="b"><?php _e('begins with ', 'simple-move-comments');?></option>
		<option value="c"><?php _e('contains ', 'simple-move-comments');?></option>
		</select>
		<input type="text" name="phmc[to_filter_2]" id="phmc_to_filter_2" />
		<!-- END Filter -->
		<span style="margin-left: 1em; margin-right: 1em;">|</span>
		<!-- START Order -->
		<label for="phmc[to_order]"><?php _e('Sort by ', 'simple-move-comments');?></label>
		<select name="phmc[to_order]" id="phmc_to_order">
		<option value="na"><?php _e('name ascending', 'simple-move-comments');?></option>
		<option value="nd"><?php _e('name descending', 'simple-move-comments');?></option>
		<option value="da"><?php _e('date ascending', 'simple-move-comments');?></option>
		<option value="dd"><?php _e('date descending', 'simple-move-comments');?></option>
		</select>
		<!-- END Order -->
		<br />
		<!-- START New POST -->
		<label for="phmc[new_id]"><?php _e('To ', 'simple-move-comments');?></label>
		<select name="phmc[new_id]" id="phmc_new_id">
		</select>
		<!-- END New POST -->
		</td></tr>
		<tr><td align="center"><p class="submit"><input type="button" value="<?php _e('Move Comments', 'simple-move-comments');?> &raquo;" id="phmc_submit" /></p>
		</td></tr></table>
		</form>
<?php
		if ( file_exists($this->logfile) ) {
			echo '<h3>'.__('History', 'simple-move-comments').'</h3><div style="font-family: Tahoma, Geneva, sans-serif; font-size: x-small; background-color: #FFF; border: thin solid #000; display: inline-block; padding: 1em; -moz-border-radius: 4px; -webkit-border-radius: 4px; overflow: scroll; height: 7em;"><ul>';
			$log = explode("\n", file_get_contents($this->logfile));
			foreach ( array_reverse($log) as $line )
				echo '<li>'.$line.'</li>';
			echo '</ul></div>';
		}
		echo '</div>';
		echo '<div style="display: none; position: fixed; top: 0; left: 0; z-index: 5000; height: 48px; width: 48px;" id="phmc_loading_from"><img src="'.WP_PLUGIN_URL.'/'.plugin_basename(dirname(__FILE__)).'/images/ajax-loader.gif" border="0" alt="'.__('loading...', 'simple-move-comments').'"/></div>';
		echo '<div style="display: none; position: fixed; top: 0; left: 0; z-index: 5000; height: 48px; width: 48px;" id="phmc_loading_to"><img src="'.WP_PLUGIN_URL.'/'.plugin_basename(dirname(__FILE__)).'/images/ajax-loader.gif" border="0" alt="'.__('loading...', 'simple-move-comments').'"/></div>';
		echo '<div style="display: none; position: fixed; top: 0; left: 0; z-index: 5000; background: #c0daff; border: 1px solid #000000; font-size: 12px; padding: 3px;" id="phmc_submit_info"></div>';
	}
}

$ph_move_comments = new ph_move_comments();

?>