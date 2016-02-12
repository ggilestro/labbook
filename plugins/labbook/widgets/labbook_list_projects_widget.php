<?php

class WP_Widget_List_projects extends WP_Widget {

    function WP_Widget_list_projects() {
        $widget_ops = array( 'classname' => 'widget_list_projects', 'description' => __( "A list or dropdown of projects" ) );
        $this->WP_Widget('list_projects', __('List Projects'), $widget_ops);
    }

    function widget( $args, $instance ) {
        extract( $args );

        $title = apply_filters('widget_title', empty( $instance['title'] ) ? __( 'projects' ) : $instance['title']);

        if($instance['child_of'])
            unset($instance['show_option_all']);
        elseif($instance['show_option_all'])
            $instance['show_option_all']=__('All',true);
            
        echo $before_widget;
        if ( $title )
            echo $before_title . $title . $after_title;

        if ($instance['dropdown']) {
            wp_dropdown_projects(apply_filters('widget_projects_dropdown_args', $instance));
?>

<script type='text/javascript'>
/* <![CDATA[ */
    var dropdown = document.getElementById("cat");
    function onCatChange() {
        var cat_id=dropdown.options[dropdown.selectedIndex].value
        if ( cat_id > 0 ) {
            location.href = "<?php echo get_option('home'); ?>/?cat=" + cat_id;
        }else{
            location.href = "<?php echo get_option('home'); ?>/";
        }
    }
    dropdown.onchange = onCatChange;
/* ]]> */
</script>

<?php
        } else {
?>
        <ul>
<?php
        $instance['title_li'] = '';
        list_projects(apply_filters('widget_projects_args', $instance));
?>
        </ul>
<?php
        }

        echo $after_widget;
    }

    function update( $new_instance, $old_instance ) {
        $new_instance['title'] = strip_tags($new_instance['title']);
        $boolfields=array(
            'show_count','show_only_mine','hide_empty','dropdown','show_option_all','hierarchical',
            'use_desc_for_title',
        );
        foreach($boolfields as $f){
            $new_instance[$f] = isset($new_instance[$f]) ? 1 : 0;
        }
        $instance=wp_parse_args($new_instance,$old_instance);
        return $instance;
    }

    function form( $instance ) {
        //Defaults
        $defaults = array(
            'show_option_all' => '',
            'orderby' => 'name',
            'order' => 'ASC',
            'show_last_update' => 0,
            'style' => 'list',
            'show_count' => 1,
            'show_only_mine' => 0,
            'hide_empty' => 1,
            'use_desc_for_title' => 1,
            'child_of' => 0,
            'exclude' => '',
            'exclude_tree' => '',
            'current_category' => 0,
            'hierarchical' => true,
            'title' => '',
            'depth' => 0,
            'dropdown'=>0
        );
        $instance = wp_parse_args( (array) $instance, $defaults );
        extract($instance);
        $title = esc_attr( $title );


?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:' ); ?></label>
        <input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

        <p><label for="<?php echo $this->get_field_id('child_of'); ?>"><?php _e( 'Child of:' ); ?></label>
        <select id="<?php echo $this->get_field_id('child_of'); ?>" name="<?php echo $this->get_field_name('child_of'); ?>">
            <?php
                $cl = get_categories(array('hide_empty' => 0, 'hierarchical' => 1));
                foreach ($cl as $c) {
                    $selected=($c->cat_ID==$child_of) ? 'selected' : '';
                    ?><option value="<?=$c->cat_ID?>"<?=$selected?>><?=$c->cat_name?></option><?php
                }
            ?>
        </select>

        <p><label for="<?php echo $this->get_field_id('exclude'); ?>"><?php _e( 'Exclude:' ); ?></label>
        <input id="<?php echo $this->get_field_id('exclude'); ?>" name="<?php echo $this->get_field_name('exclude'); ?>" type="text" value="<?php echo $exclude; ?>" /></p>

        <p><label for="<?php echo $this->get_field_id('include'); ?>"><?php _e( 'Include:' ); ?></label>
        <input id="<?php echo $this->get_field_id('include'); ?>" name="<?php echo $this->get_field_name('include'); ?>" type="text" value="<?php echo $include; ?>" /></p>
        
        <p><label for="<?php echo $this->get_field_id('orderby'); ?>"><?php _e( 'Sort by' ); ?></label>
        <select id="<?php echo $this->get_field_id('orderby'); ?>" name="<?php echo $this->get_field_name('orderby'); ?>">
            <?php
                $arr=array(
                    'id','name','slug','count','term_group'
                );
                foreach ($arr as $str) {
                    $selected=($str==$orderby) ? ' selected' : '';
                    ?><option value="<?=$str?>"<?=$selected?>><?=$str?></option><?php
                }
            ?>
        </select>
        <select id="<?php echo $this->get_field_id('order'); ?>" name="<?php echo $this->get_field_name('order'); ?>">
            <?php
                $arr=array('ASC'=>__('ASC',true),'DESC'=>__('DESC',true));
                foreach ($arr as $k=>$str) {
                    $selected=($k==$order) ? ' selected' : '';
                    ?><option value="<?=$k?>"<?=$selected?>><?=$str?></option><?php
                }
            ?>
        </select>

        </p>

        <p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('dropdown'); ?>" name="<?php echo $this->get_field_name('dropdown'); ?>"<?php checked( $dropdown ); ?> />
        <label for="<?php echo $this->get_field_id('dropdown'); ?>"><?php _e( 'Show as dropdown' ); ?></label><br />

        <p><input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('show_option_all'); ?>" name="<?php echo $this->get_field_name('show_option_all'); ?>"<?php checked( $show_option_all ); ?> />
        <label for="<?php echo $this->get_field_id('show_option_all'); ?>"><?php _e( 'Show Option All' ); ?></label><br />

        <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hide_empty'); ?>" name="<?php echo $this->get_field_name('hide_empty'); ?>"<?php checked( $hide_empty ); ?> />
        <label for="<?php echo $this->get_field_id('hide_empty'); ?>"><?php _e( 'Hide empty' ); ?></label><br />

        <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('show_only_mine'); ?>" name="<?php echo $this->get_field_name('show_only_mine'); ?>"<?php checked( $show_only_mine ); ?> />
        <label for="<?php echo $this->get_field_id('show_only_mine'); ?>"><?php _e( 'Show only user\'s projects' ); ?></label><br />

        <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('show_count'); ?>" name="<?php echo $this->get_field_name('show_count'); ?>"<?php checked( $show_count ); ?> />
        <label for="<?php echo $this->get_field_id('show_count'); ?>"><?php _e( 'Show post counts' ); ?></label><br />

        <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hierarchical'); ?>" name="<?php echo $this->get_field_name('hierarchical'); ?>"<?php checked( $hierarchical ); ?> />
        <label for="<?php echo $this->get_field_id('hierarchical'); ?>"><?php _e( 'Show hierarchy' ); ?></label><br />

        <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('use_desc_for_title'); ?>" name="<?php echo $this->get_field_name('use_desc_for_title'); ?>"<?php checked( $use_desc_for_title ); ?> />
        <label for="<?php echo $this->get_field_id('use_desc_for_title'); ?>"><?php _e( 'Use Desc for Title' ); ?></label></p>
        
<center>Check <a href="http://codex.wordpress.org/Template_Tags/wp_list_categories" target="_blank">wp_list_categories</a> for help with these parameters.</center>

<?php
    }

}
function wp_list_projects_init() {
    register_widget('WP_Widget_list_projects');
}
add_action('widgets_init', 'wp_list_projects_init');


function list_projects( $args = '' ) {
    $defaults = array(
        'show_option_all' => '', 
        'orderby' => 'name',
        'order' => 'ASC',
        'show_last_update' => 0,
        'style' => 'list',
        'show_count' => 1,
        'show_only_mine' => 0,
        'hide_empty' => 1, 
        'use_desc_for_title' => 1,
        'child_of' => 0, 
        'include_children' => true,
        'exclude' => '', 
        'exclude_tree' => '', 
        'current_category' => 0,
        'hierarchical' => true, 
        'title_li' => __( 'Projects' ),
        'echo' => 1, 
        'depth' => 0
    );

    $r = wp_parse_args( $args, $defaults );

    if ( !isset( $r['pad_counts'] ) && $r['show_count'] && $r['hierarchical'] ) {
        $r['pad_counts'] = true;
    }

    if ( isset( $r['show_date'] ) ) {
        $r['include_last_update_time'] = $r['show_date'];
    }

    if ( true == $r['hierarchical'] ) {
        $r['exclude_tree'] = $r['exclude'];
        $r['exclude'] = '';
    }

    extract( $r );

    $projects = get_list_projects( $r );

    // WIDGET OUTPUT HERE
    $output = '';
    if ( $title_li && 'list' == $style )
            $output = '<li class="projects">' . $r['title_li'] . '<ul>';

    if ( empty( $projects ) ) {
        if ( 'list' == $style )
            $output .= '<li>' . __( "No projects" ) . '</li>';
        else
            $output .= __( "No projects" );
    } else {
        global $wp_query;

        if( !empty( $show_option_all ) )
            if ( 'list' == $style )
                $output .= '<li><a href="' .  get_bloginfo( 'url' )  . '">' . $show_option_all . '</a></li>';
            else
                $output .= '<a href="' .  get_bloginfo( 'url' )  . '">' . $show_option_all . '</a>';

        if ( empty( $r['current_project'] ) && is_category() )
            $r['current_project'] = $wp_query->get_queried_object_id();

        if ( $hierarchical )
            $depth = $r['depth'];
        else
            $depth = -1; // Flat.

        $output .= walk_category_tree( $projects, $depth, $r );
    }

    if ( $title_li && 'list' == $style )
        $output .= '</ul></li>';

    $output = apply_filters( 'wp_list_projects', $output );

    if ( $echo )
        echo $output;
    else
        return $output;
}

function full_category_list( $args = '') {
    
    $defaults = array('orderby' => 'name',
                      'order' => 'ASC',
                      'hide_empty' => true, 
                      'exclude' => '', 
                      'exclude_tree' => '', 
                      'include' => '',
                      'child_of' => 0, 
                      'get' => ''
                );

    $args = wp_parse_args( $args, $defaults );
    extract($args);
    
    $categories=get_categories($args);
    
    foreach($categories as $category) {
    echo '
    <div class="' . $category->slug . '">
                <h3>' . $category->name . '</h3>
                <img src="' . get_bloginfo( 'template_url' ) . '/images/categories/' . $category->slug . '_feature.jpg" alt="' . $category->name .'" />
                <p>' . $category->description .'</p>
                <h4>Explore</h4>
                <ul>';

                global $post;
                $args = array( 'numberposts' => 3, 'category' => $category->term_id );
                $myposts = get_posts( $args );
                foreach( $myposts as $post ) :  setup_postdata($post);
                    echo  '<li><a href="' . get_permalink($post->ID) . '">' . get_the_title($post->ID) . '</a></li>';
                endforeach;
                echo  '
                        <li><a href="' . get_category_link( $category->term_id ) . '">More...</a></li>
                        </ul>
                        <br />
                        </div>
                      ';
    }
}

function get_list_projects( $args = '' ) {
    $defaults = array('orderby' => 'name',
                      'order' => 'ASC',
                      'hide_empty' => true, 
                      'exclude' => '', 
                      'exclude_tree' => '', 
                      'include' => '',
                      'child_of' => 0, 
                      'get' => ''
                );
    
    $args = wp_parse_args( $args, $defaults );
    extract($args);

    $params = array('pad_counts' => true,
                    'hide_empty' => false,
                    'child_of' => '');


    if($child_of) { $params['child_of'] = $child_of; }
    if($hide_empty) { $params['hide_empty'] = true; }

    $projects=get_categories($params);

    if($include){
        $GLOBALS['filter_include']=explode(',', $include);
        $projects=array_filter($projects,'filter_include_project');
    }else{
        if($exclude){
            $GLOBALS['filter_exclude']=explode(',', $exclude);
            $projects=array_filter($projects,'filter_exclude_project');
        }
        if($show_only_mine){
            $projects=array_filter($projects,'filter_show_only_my_projects');
        }
    }
        
    if($orderby!='name'){
        if($orderby=='count')
            $orderby='category_count';
        elseif($orderby=='id')
            $orderby='term_id';
        
        $projects=filter_flat_sort_projects($projects,$orderby);
    }
    if(strtolower($order) !='asc'){
        $projects=array_reverse($projects);
    }

    foreach ( array_keys( $projects ) as $k )
        _make_cat_compat( $projects[$k] );

    return $projects;
}

function filter_include_project($project) {
    return in_array($project->term_id, $GLOBALS['filter_include']);
}

function filter_child_of_project($project) {
    return $project->category_parent==$GLOBALS['filter_child_of'];
}

function filter_exclude_project($project) {
    return !in_array($project->term_id, $GLOBALS['filter_exclude']);
}

function filter_show_only_my_projects($project) {
    
    global $current_user;
    get_currentuserinfo();
    $cats = get_user_meta($current_user->ID,'_author_cat',true);
    $cats=(array)$cats;
    
    if( current_user_can('administrator') ) {
        return True;
    } else {
        return in_array($project->term_id, $cats);
    }
}
    
function filter_hide_empty_project($project) {
    return $project->category_count>0;
}

function filter_flat_sort_projects($projects,$orderby) {
    foreach($projects as $cat){
        $key=$cat->$orderby;

        if($orderby=='category_count'){
            $key.='-'.$cat->term_id;
        }
        $new[$key]=$cat;
    }
    ksort($new);
    return array_values($new);
}

add_shortcode('project_list', 'list_projects');
//add_shortcode('project_list', 'full_category_list');
?>
