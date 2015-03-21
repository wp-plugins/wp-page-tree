<?php
/*
Plugin Name: WP Page Tree
Plugin URI: https://charles.lecklider.org/wordpress/wp-page-tree/
Description: Widget to display a navigable tree of pages.
Version: 1.1.1
Author: Charles Lecklider
Author URI: https://charles.lecklider.org/
License: GPL2
*/

/*  Copyright 2012-2015  Charles Lecklider  (email : wordpress@charles.lecklider.org)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


class WP_Page_Tree_Widget extends WP_Widget
{
	public function __construct()
	{
		parent::__construct('wp_page_tree','WP Page Tree');

		add_action( 'wp_enqueue_scripts', array(&$this,'enqueueStyles') );
	}

	public function form( $instance )
	{
		$title = (isset($instance['title'])) ? $instance['title'] : get_bloginfo('name');
?>
	<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
	</p>
<?php
	}

	public function update( $new_instance, $old_instance )
	{
		$instance = array();
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}

	public function widget( $args, $instance )
	{
		extract($args);

		global $post;

		$posts = array();
		$_post = $post;
		while($_post->post_parent > 0) {
			$qry = new WP_Query(array('post_type'	=> 'page',
									  'page_id'		=> $_post->post_parent));
			$_post = $qry->post;
			$posts[] = $_post->ID;
		}

		echo $before_widget;
		echo "<div id=\"wppt\">\n";
		echo "\t<div style=\"height: 16px\"><img src=\"".plugins_url('icons/folderopen.gif',__FILE__).'" width="19" height="16">&nbsp;<span class="title" id="wppt">'.apply_filters('widget_title',$instance['title'])."</span></div>\n";
		echo $this->renderPages(0,array(),$posts);
		echo "</div>\n";
		echo $after_widget;
	}

	public function enqueueStyles()
	{
		wp_enqueue_style('wp-page-tree',plugins_url('style.css',__FILE__));
	}

	protected function renderPages($parent,$levels,$tree)
	{
		global $wpdb;

		// how many child pages do our child pages have?
		$counts = $wpdb->get_results("SELECT post_parent, COUNT(*) AS c FROM $wpdb->posts WHERE post_status='publish' AND post_parent IN (SELECT ID FROM $wpdb->posts WHERE post_parent=$parent) GROUP BY post_parent",OBJECT_K);
		$html = str_repeat("\t",count($levels)+1)."<ul>\n";

		$qry = new WP_Query(array('post_type'	=> 'page',
								  'post_parent'	=> $parent,
								  'nopaging'	=> true,
								  'orderby'		=> 'menu_order',
								  'order'		=> 'ASC'));
		if ($qry->have_posts()) {
			$pages = $qry->get_posts();
			for($i=0; $i<count($pages)-1; $i++) {
				$html .= $this->renderPage($pages[$i],$levels,$tree,$counts);
			}
			$html .= $this->renderPage($pages[$i],$levels,$tree,$counts,true);
		}
		$html .= str_repeat("\t",count($levels)+1)."</ul>\n";

		return $html;
	}


	protected function renderPage($page,$levels,$posts,$counts,$last=false)
	{
		global $post;

		$html = str_repeat("\t",count($levels)+2).'<li><div>';
		foreach($levels as $level) {
			$img = ($level) ? 'empty' : 'line';
			$html .= '<img src="'.plugins_url("icons/$img.gif",__FILE__).'">';
		}
		$levels[] = $last;
		if (is_object($obj = @$counts[$page->ID]) && $obj->c > 0) {
			if (in_array($page->ID,$posts) || $page->ID == $post->ID) {
				$img = ($last) ? 'minus' : 'minusbottom';
				$html .= '<a class="wppt_minus" href="#"><img src="'.plugins_url("icons/$img.gif",__FILE__).'" width="19" height="16"></a>';
				$folder = ($post->ID == $page->ID) ? 'foldersel' : 'folderopen';
			} else {
				$img = ($last) ? 'plus' : 'plusbottom';
				$html .= '<a class="wppt_plus" href="#"><img src="'.plugins_url("icons/$img.gif",__FILE__).'" width="19" height="16"></a>';
				$folder = 'folder';
			}
			$html .= '<img src="'.plugins_url("icons/$folder.gif",__FILE__).'" width="19" height="16">';
		} else {
			$img = ($last) ? 'join' : 'joinbottom';
			$html .= '<img src="'.plugins_url("icons/$img.gif",__FILE__).'" width="19" height="16">';
			$img = ($post->ID == $page->ID) ? 'pagesel' : 'page';
			$html .= '<img src="'.plugins_url("icons/$img.gif",__FILE__).'" width="19" height="16">';
		}
		$html .= ($page->ID == $post->ID) ? '<span class="selected">' : '<span>';
		$html .= '&nbsp;<a href="'.get_permalink($page->ID).'">'.str_replace(' ','&nbsp;',$page->post_title).'</a></span></div>';
		if (in_array($page->ID,$posts) || $page->ID == $post->ID)
			$html .= "\n".$this->renderPages($page->ID,$levels,$posts).str_repeat("\t",count($levels)+1);
		$html .= "</li>\n";

		return $html;
	}
}
add_action( 'widgets_init',
			function() {
				register_widget('WP_Page_Tree_Widget');
			} );

