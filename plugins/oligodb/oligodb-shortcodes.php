<?php

function oligodb_table_format($oligos)
{
    $display = "<div id=\"oligo-table\">
            <table class=\"oligo-table\">
            <thead><tr>
            <th >ID</th>
            <th >Oligo Sequence</th>
            <th >Len</th>
            <th >TM</th>
            <th >Tags</th>
            <th >Template</th>
            <th >Comments</th>
            <th >Owner</th>
            </tr></thead>
            <tbody id=\"oligo-table_body_wrap\">";

    foreach($oligos as $oligo_data) {
            $display .= oligodb_row_format( $oligo_data );
    }
    
    $display .= "</tbody></table></div>";
    $display .= "<a href=\"javascript:void()\" onclick=\"scrollToBottom();\" >Scroll to bottom</a>";
    
    return apply_filters( 'oligodb_shortcode_output_format', $display );
}


function oligodb_shortcodes($atts = array())
{
    
    if (isset($_GET) and !empty($_GET)) $atts = $_GET;
    
    extract( shortcode_atts( array(
        'oligo_id' => 0,
        'oligo_name' => '',
        'owner' => '',
        'tags' => '',
        'to_order' => false,
        'orderby' => 'oligo_id',
        'order' => 'ASC',
        'paging' => false,
        'limit_per_page' => 50
    ), $atts ) );
    $condition = " WHERE 1 = 1";
        
    if(isset($oligo_id) && is_numeric($oligo_id)) $id = $oligo_id;
    
    if($id && is_numeric($id)) {
        $condition .= " AND oligo_id = ".$id;
    } 
    
    else if ($oligo_name) {
        $namelist = array_map('trim', explode(',', $oligo_name));
        $cond = " AND ";
        foreach ($namelist as $name) {
            $cond .= " oligo_name LIKE '".$name."' OR";
        }               
        $condition .= substr($cond, 0, -3); //removes final " OR"
    }
    
    else {
    
        if($owner)
            $condition .= " AND owner = '".$owner."'";

        if($to_order)
            $condition .= " AND ordered = '127'";

        if ($tags) {
            $tags = html_entity_decode($tags);
            if(!$tags)
                break;
            $taglist = explode(',', $tags);
            $tags_condition = "";
            foreach($taglist as $tag) {
                $tag = trim($tag);
                if($tags_condition) $tags_condition .= " OR ";
                $tags_condition .= "tags = '{$tag}' OR tags LIKE '{$tag},%' OR tags LIKE '%,{$tag},%' OR tags LIKE '%,{$tag}'";
            }
            if($tags_condition) $condition .= " AND ".$tags_condition;
        }

        if($orderby == 'id' || !$orderby) $orderby = 'oligo_id';
        else if ($orderby == 'date_added') $orderby = 'time_added';

        $order = strtoupper($order);
        if($order && $order != 'DESC')    
            $order = 'ASC';
    
        $condition .= " ORDER BY {$orderby} {$order}";
    
        if($paging == true || $paging == 1) {
        
            $num_quotes = oligodb_count($condition);
            
            $total_pages = ceil($num_quotes / $limit_per_page);
            
            
            if(!isset($_GET['quotes_page']) || !$_GET['quotes_page'] || !is_numeric($_GET['quotes_page']))
                $page = 1;
            else
                $page = $_GET['quotes_page'];
            
            if($page > $total_pages) $page = $total_pages;
            
            if($page_nav = oligodb_pagenav($total_pages, $page, 0, 'quotes_page'))
                $page_nav = '<div class="oligodb_pagenav">'.$page_nav.'</div>';
                
            $start = ($page - 1) * $limit_per_page;
            
            $condition .= " LIMIT {$start}, {$limit_per_page}"; 

            if($oligos = oligodb_get_oligos($condition))
                return $page_nav.oligodb_shortcode_output_format($oligos).$page_nav;
            else
                return "";
            
        } // END PAGING TRUE
    }
    
    if($oligos = oligodb_get_oligos($condition))
        return oligodb_table_format($oligos);
    else
        return "";
}

function user_add_oligo() {

    $action_url = $_SERVER['REQUEST_URI'];
    $owner_id = get_current_user_id();
    $newID = oligodb_getnewID("pGG");

    if( isset($_REQUEST['action']) and ($_REQUEST['action'] == "add_oligo" ) ) {
            extract($_REQUEST);
            $msg = oligodb_addoligo($oligo_seq, $owner, $template, $tags, $comments, $oligo_name);
            //return $msg;
    }

    $display .= <<< EDITFORM

    <h3 class="accordion_title">Add</h3>
    <div id="addoligo_content">
        <span>{$msg}</span>
        <form name="addoligo" method="post" class="pure-form pure-form-stacked" id="addoligo-form" action="{$action_url}">
            <fieldset>
              <label for="Sequence">Sequence</label>
              <input class="pure-input-2-3" id="add-oligo_seq" name="oligo_seq" type="text" placeholder="" required onkeyup="calculate_properties(this, 'add')" onblur="calculate_properties(this, 'add')" >

              <div class="pure-u-1-8">
                  <input class="pure-input-1" id="add-oligo_name" name="oligo_name" type="text" value="{$newID}" readonly>
                  <input type="hidden" id="add-owner" name="owner" value="{$owner_id}">
                  <input type="hidden" id="action" name="action" value="add_oligo" />
              </div>
              <div class="pure-u-1-8">
                  <input class="pure-input-1" id="add-oligo_len" name="oligo_len" type="text" placeholder="LEN" readonly>
              </div>
              <div class="pure-u-1-8"> 
                  <input class="pure-input-1" id="add-oligo_tm" name="oligo_tm" type="text" placeholder="TM" readonly>
              </div>

              <label for="Template">Template Name</label>  
              <input class="pure-input-2-3" id="add-template" name="template" type="text" placeholder="" >

              <label for="Tags">Tags (comma separated)</label>  
              <input class="pure-input-2-3" id="add-tags" name="tags" type="text" placeholder="" >

              <label for="Comments">Comments</label>
              <textarea class="pure-input-2-3" class="form-control" id="add-comments" name="comments" style="height: 100px;"></textarea>

              <button type="submit" name="submit "class="pure-button pure-button-primary" value="add_oligo" id="btn_addoligo">Add Oligo</button>
              <input type="reset" value="Clear" class="pure-button pure-button-primary">
         
            </fieldset>
        </form>
    </div>
EDITFORM;

    return $display;
}


function user_edit_oligo(){

    $action_url = $_SERVER['REQUEST_URI'];
    
    if( isset($_REQUEST['action']) and ($_REQUEST['action'] == "view_oligo" ) ) {

        $oligo_data = oligodb_get_oligo( $_REQUEST['oligo_name'] );
        
        $oligo_seq = $oligo_data['oligo_seq'];
        $oligo_name = $oligo_data['oligo_name'];
        $template = $oligo_data['template'];
        $tags = $oligo_data['tags'];
        $comments = $oligo_data['comments'];
        $owner_name = get_userdata($oligo_data['owner'])->display_name;
        $oligo_len = $oligo_data['oligo_len'];
        $oligo_tm = $oligo_data['oligo_tm'];
        $oligo_id = $oligo_data['oligo_id'];
        $owner_id = $oligo_data['owner'];

    }

    if( isset($_REQUEST['action']) and ($_REQUEST['action'] == "edit_oligo" ) ) {

        extract($_REQUEST);
        $msg = oligodb_editoligo($oligo_id, $oligo_name, $oligo_seq, $owner_id, $template, $tags, $comments);
    }


    $display .= <<< EDITFORM
    <h3 class="accordion_title">Edit</h3>
    <div id="editoligo_content">
        <span>{$msg}</span>
        <form name="editoligo" method="post" class="pure-form pure-form-stacked" id="editoligo-form" action="{$action_url}">
            <fieldset>
                <div>
                  <label for="Sequence">Sequence</label>
                  <input class="pure-input-2-3" id="edit-oligo_seq" name="oligo_seq" type="text" placeholder="" required value="{$oligo_seq}" onkeyup="calculate_properties(this, 'edit')" onblur="calculate_properties(this, 'edit')" >

                  <div class="pure-u-1-8">
                  <input class="pure-input-1" id="edit-oligo_name" name="oligo_name" type="text" value="{$oligo_name}" readonly required >
                  <input type="hidden" id="edit-owner_id" name="owner_id" value="{$owner_id}">
                  <input type="hidden" id="edit-oligo_id" name="oligo_id" value="{$oligo_id}" />
                  <input type="hidden" id="action" name="action" value="edit_oligo" />

                </div>
                <div class="pure-u-1-8">
                  <input class="pure-input-1" id="edit-oligo_len" name="oligo_len" type="text" placeholder="{$oligo_len}" readonly>
                </div>

                <div class="pure-u-1-8">
                  <input class="pure-input-1" id="edit-owner_name" name="owner" type="text" placeholder="{$owner_name} ({$owner_id})" readonly>
                </div>

                <div class="pure-u-1-8"> 
                  <input class="pure-input-1" id="edit-oligo_tm" name="oligo_tm" type="text" placeholder="{$oligo_tm}" readonly>
                </div>

                <label for="Template">Template Name</label>  
                <input class="pure-input-2-3" id="edit-template" name="template" type="text" placeholder="" value="{$template}">

                <label for="Tags">Tags (comma separated)</label>  
                <input class="pure-input-2-3" id="edit-tags" name="tags" type="text" placeholder="" value="{$tags}">

                <label for="Comments">Comments</label>
                <textarea class="pure-input-2-3" class="form-control" id="edit-comments" name="comments" style="height: 100px;">{$comments}</textarea>

                <button type="submit" name="submit "class="pure-button pure-button-primary" value="edit_oligo" id="btn_editoligo" disabled >Edit Oligo</button>
             
            </fieldset>
        </form>
    </div>
EDITFORM;

    return $display;

}

function user_order_oligo(){

    $action_url = $_SERVER['REQUEST_URI'];
    $owner_id = get_current_user_id();
    $content = "";

    if( isset($_REQUEST['action']) and ($_REQUEST['action'] == "mark_ordered" ) ) {
        $msg = mark_as_ordered();
    } else {
        $oligo_list = oligodb_get_oligos(" WHERE ordered = 127");
        foreach ($oligo_list as $oligo) {
            $content .= $oligo['oligo_name']."    ".$oligo['oligo_seq']."    0.025    DST\n";
        }
    }
    
    $display .= <<< EDITFORM
    
    <h3 class="accordion_title">Order</h3>
    <div id="orderoligo_content">
        <span>$msg</span>
        <form name="orderoligo" method="post" class="pure-form pure-form-stacked" id="orderoligo-form" action="{$action_url}">
            <fieldset>
                <label for="order_provider">Provider</label>
                    <select id=order_provider>
                      <option value="sigma">Sigma-Aldrich</option>
                      <option value="Eurofins" disabled>Eurofins</option>
                      <option value="VWR" disabled>VWR</option>
                    </select>
                <input type="hidden" id="action" name="action" value="mark_ordered" />
                
                <label for="Comments">Ordering output</label>
                <textarea class="pure-input-2-3" class="form-control" id="order-csv_output" name="csv_output" style="height: 100px;">{$content}</textarea>

                <button type="submit" name="submit "class="pure-button pure-button-primary" value="mark_ordered" id="btn_markordered">Mark all as ordered</button>
            </fieldset>
        </form>
    </div>
EDITFORM;

    return $display;
}

function user_admin_oligos(){

    $display = "<div id=\"status_text\" class=\"alert alert-success\"></div>";
    $display .= "<div id=\"accordion\">";

    $display .= user_add_oligo();
    $display .= user_edit_oligo();
    $display .= user_order_oligo();


    $display .= "</div>";
    return $display;

}


function user_add_oligo_popup() {
    $submit_value = __('Add oligo', 'oligodb');
    $form_name = "addoligo";
    $action_url = get_bloginfo('wpurl')."/wp-admin/admin.php?page=oligodb#addnew";

    $newID = oligodb_getnewid();
    $owner_id = get_current_user_id();
    
    $display = <<< EDITFORM
    <a class="button" href="#popup1">Add a new oligo</a>
    
    <div id="popup1" class="overlay">
        <div class="popup">
        <h2>Add a new oligo</h2>
        <a class="close" href="#">×</a>
            <div class="popupcontent">
                <form name="{$form_name}" method="post" action="{$action_url}" class="user-add-oligo">
                    <section>
                        <div style="float:left;margin-right:20px;">
                        <br style="clear:both;" />
                    </section>
                    <div id=\"message\" class=\"updated fade\"><p>{$msg}</p></div>
                    <section>
                        <div style="float:left;margin-right:20px;">
                            <label>ID</label>Oligo: {$newID}
                        </div>
                    <br style="clear:both;" />
                    </section>
                    <section>
                        <div style="float:left;margin-right:20px;">
                            <label>Sequence</label>
                            <input type="text" style="width:400px;" id="oligodb_oligo" name="oligo_seq" value="${oligo_seq}" onkeyup="calculate_properties(this)" ONBLUR="calculate_properties(this) ">
                        </div>
                        <div style="float:left;padding-right:15px;">
                            <label>Length</label><input type="text" id="oligodb_length" name="oligo_len" size="3" value="${oligo_len}">
                        </div>
                        <div style="float:left;">
                            <label>TM</label><input type="text" id="oligodb_temperature" name="oligo_tm" size="3" value="${oligo_tm}">
                        </div>
                    <br style="clear:both;" />
                    </section>
                    <section>
                        <div style="float:left;margin-right:20px;">
                            <label>Tags</label><input type="text" style="width:260px; id="oligodb_tags" name="tags" size="20" value="{$tags}" /></td>
                        </div>
                        <div style="float:left;">
                            <label>Template</label><input type="text" style="width:260px; id="oligodb_template" name="template" size="20" value="{$template}" />
                        </div>
                    <br style="clear:both;" />
                    </section>
                    <section>
                        <div style="float:left;margin-right:20px;">
                            <label>Comments</label><textarea id="oligodb_comments" name="comments" size="40" value="{$comments}" rows=4/></textarea></td>
                            <input type="hidden" name="owner" value="{$owner_id}" />
                            <input type="hidden" name="action" value="addoligo" />
                        </div>
                    <br style="clear:both;" />
                    </section>
                    <section>
                        <input style="padding-top:10px; name="submit" value="{$submit_value}" type="submit" class="button button-primary" />
                    </section>
                </form>
            </div>
        </div>
    </div>
EDITFORM;

    return $display;
}


add_shortcode('oligodb', 'oligodb_shortcodes');
add_shortcode('add_new_oligo', 'user_admin_oligos');
