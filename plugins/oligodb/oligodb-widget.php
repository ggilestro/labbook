<?php

function oligodb_widget_init()
{
    if(function_exists('load_plugin_textdomain'))
        load_plugin_textdomain('oligos-collection', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    
    if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
        return;
    
    function oligodb_widget($args) {
        $options = get_option('oligodb');
        $title = isset($options['title'])?apply_filters('the_title', $options['title']):__('Random oligo', 'oligos-collection');
        $show_author = isset($options['show_author'])?$options['show_author']:1;
        $ajax_refresh = isset($options['ajax_refresh'])?$options['ajax_refresh']:1;
        $auto_refresh = isset($options['auto_refresh'])?$options['auto_refresh']:0;
        $random_refresh = isset($options['random_refresh'])?$options['random_refresh']:1;
        if($auto_refresh)
            $auto_refresh = isset($options['refresh_interval'])?$options['refresh_interval']:5;
        $char_limit = $options['char_limit'];
        $tags = $options['tags'];
        $parms = "echo=0&show_author={$show_author}&show_source={$show_source}&ajax_refresh={$ajax_refresh}&auto_refresh={$auto_refresh}&char_limit={$char_limit}&tags={$tags}&random={$random_refresh}";
        if($random_oligo = oligodb_oligo($parms)) {
            extract($args);
            echo $before_widget;
            if($title) echo $before_title . $title . $after_title . "\n";
            echo $random_oligo;
            echo $after_widget;
        }
    }
    
    function oligodb_widget_control()
    {
        
        // default values for options
        $options = array(
            'title' => __('Random oligo', 'oligos-collection'), 
            'show_author' => 1,
            'ajax_refresh' => 1,
            'auto_refresh' => 0,
            'random_refresh' => 1,
            'refresh_interval' => 5,
            'tags' => '',
            'char_limit' => 500
        );

        if($options_saved = get_option('oligodb'))
            $options = array_merge($options, $options_saved);
            
        // Update options in db when user updates options in the widget page
        if(isset($_REQUEST['oligodb-submit']) && $_REQUEST['oligodb-submit']) { 
            $options['title'] 
                = strip_tags(stripslashes($_REQUEST['oligodb-title']));
            $options['show_author'] = (isset($_REQUEST['oligodb-show_author']) && $_REQUEST['oligodb-show_author'])?1:0;
            $options['ajax_refresh'] = (isset($_REQUEST['oligodb-ajax_refresh']) && $_REQUEST['oligodb-ajax_refresh'])?1:0;
            $options['auto_refresh'] = (isset($_REQUEST['oligodb-auto_refresh']) && $_REQUEST['oligodb-auto_refresh'])?1:0;
            $options['refresh_interval'] = $_REQUEST['oligodb-refresh_interval'];
            $options['random_refresh'] = (isset($_REQUEST['oligodb-random_refresh']) && $_REQUEST['oligodb-random_refresh'])?1:0;
            $options['tags'] = strip_tags(stripslashes($_REQUEST['oligodb-tags']));
            $options['char_limit'] = strip_tags(stripslashes($_REQUEST['oligodb-char_limit']));
            if(!$options['char_limit'])
                $options['char_limit'] = __('none', 'oligos-collection');
            update_option('oligodb', $options);
        }

        // Now we define the display of widget options menu
        $show_author_checked = $show_source_checked    = $ajax_refresh_checked = $auto_refresh_checked = $random_refresh_checked = '';
        $int_select = array ( '5' => '', '10' => '', '15' => '', '20' => '', '30' => '', '60' => '');
        if($options['show_author'])
            $show_author_checked = ' checked="checked"';
        if($options['ajax_refresh'])
            $ajax_refresh_checked = ' checked="checked"';
        if($options['auto_refresh'])
            $auto_refresh_checked = ' checked="checked"';
        if($options['random_refresh'])
            $random_refresh_checked = ' checked="checked"';
        $int_select[$options['refresh_interval']] = ' selected="selected"';

        echo "<p style=\"text-align:left;\"><label for=\"oligodb-title\">".__('Title', 'oligos-collection')." </label><input class=\"widefat\" type=\"text\" id=\"oligodb-title\" name=\"oligodb-title\" value=\"".htmlspecialchars($options['title'], ENT_oligoS)."\" /></p>";
        echo "<p style=\"text-align:left;\"><input type=\"checkbox\" id=\"oligodb-show_author\" name=\"oligodb-show_author\" value=\"1\"{$show_author_checked} /> <label for=\"oligodb-show_author\">".__('Show author?', 'oligos-collection')."</label></p>";
        echo "<p style=\"text-align:left;\"><input type=\"checkbox\" id=\"oligodb-ajax_refresh\" name=\"oligodb-ajax_refresh\" value=\"1\"{$ajax_refresh_checked} /> <label for=\"oligodb-ajax_refresh\">".__('Ajax refresh feature', 'oligos-collection')."</label></p>";
        echo "<p style=\"text-align:left;\"><small><a id=\"oligodb-adv_key\" style=\"cursor:pointer;\" onclick=\"jQuery('div#oligodb-adv_opts').slideToggle();\">".__('Advanced options', 'oligos-collection')." &raquo;</a></small></p>";
        echo "<div id=\"oligodb-adv_opts\" style=\"display:none\">";
        echo "<p style=\"text-align:left;\"><input type=\"checkbox\" id=\"oligodb-random_refresh\" name=\"oligodb-random_refresh\" value=\"1\"{$random_refresh_checked} /> <label for=\"oligodb-random_refresh\">".__('Random refresh', 'oligos-collection')."</label><br/><span class=\"setting-description\"><small>".__('Unchecking this will rotate oligos in the order added, latest first.', 'oligos-collection')."</small></span></p>";
        echo "<p style=\"text-align:left;\"><input type=\"checkbox\" id=\"oligodb-auto_refresh\" name=\"oligodb-auto_refresh\" value=\"1\"{$auto_refresh_checked} /> <label for=\"oligodb-auto_refresh\">".__('Auto refresh', 'oligos-collection')."</label> <label for=\"oligodb-refresh_interval\">".__('every', 'oligos-collection')."</label> <select id=\"oligodb-refresh_interval\" name=\"oligodb-refresh_interval\"><option{$int_select['5']}>5</option><option{$int_select['10']}>10</option><option{$int_select['15']}>15</option><option{$int_select['20']}>20</option><option{$int_select['30']}>30</option><option{$int_select['60']}>60</option></select> ".__('sec', 'oligos-collection')."</p>";
        echo "<p style=\"text-align:left;\"><label for=\"oligodb-tags\">".__('Tags filter', 'oligos-collection')." </label><input class=\"widefat\" type=\"text\" id=\"oligodb-tags\" name=\"oligodb-tags\" value=\"".htmlspecialchars($options['tags'], ENT_oligoS)."\" /><br/><span class=\"setting-description\"><small>".__('Comma separated', 'oligos-collection')."</small></span></p>";
        echo "<p style=\"text-align:left;\"><label for=\"oligodb-char_limit\">".__('Character limit', 'oligos-collection')." </label><input class=\"widefat\" type=\"text\" id=\"oligodb-char_limit\" name=\"oligodb-char_limit\" value=\"".htmlspecialchars($options['char_limit'], ENT_oligoS)."\" /></p>";
        echo "</div>";
        echo "<input type=\"hidden\" id=\"oligodb-submit\" name=\"oligodb-submit\" value=\"1\" />";
    }

    if ( function_exists( 'wp_register_sidebar_widget' ) ) {
        wp_register_sidebar_widget( 'oligodb', 'Random oligo', 'oligodb_widget' );
        wp_register_widget_control( 'oligodb', 'Random oligo', 'oligodb_widget_control' );
    } else {
        register_sidebar_widget(array('Random oligo', 'widgets'), 'oligodb_widget');
        register_widget_control('Random oligo', 'oligodb_widget_control', 250, 350);
    }
}

add_action('plugins_loaded', 'oligodb_widget_init');
?>
