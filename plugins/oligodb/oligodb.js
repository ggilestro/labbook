// AJAX functions //

function ajaxRefresholigoTable(){
    var url = window.location.href + " #oligo-table";
    jQuery("#oligo-table").load( url , function() { scrollToBottom(); } );
}

function scrollToBottom(){
    var rowpos = jQuery('#oligo-table tr:last').position();
    jQuery('#oligo-table_body_wrap').scrollTop(rowpos.top);
}

function cssAlert(text, status){
    id_text = "#status_text";
    
    jQuery(id_text).html(text);
    jQuery(id_text).className = status; 
    
    jQuery(id_text).css( "display", "block");
    setTimeout( function() { jQuery(id_text).css( "display", "none") }, 5000 );
}


// onLOAD the accordion //
jQuery(function() {
    jQuery("#accordion").accordion({ 
        event: "click",
        active: false,
        collapsible: true,
        autoHeight: false

    });
});

//

//on the click of the submit button when adding a new oligo
jQuery('#orderoligo-form').live("submit", function(event) {

    var actionURL = jQuery('#addoligo-form').attr('action')
    var postData = '&action=mark_ordered';
    
    jQuery.ajax({
    url : actionURL,
    type: "POST",
    data : postData,
    success: function(data,status,  xhr)
     {

        //if success then just output the text to the status div then clear the form inputs to prepare for new data
        if ( data.indexOf('marked as ordered') > -1 ) {
            cssAlert("Oligos were succesfully marked as ordered", "alert-success");
            jQuery('#order-csv_output').val("");
        }
        else {
            cssAlert("Something went wrong!", "alert-error");
        }
        
         },

    error: function (jqXHR, status, err) 
      {
        cssAlert(err, "alert-error");
         }
     });

    // stop the form from submitting the normal way and refreshing the page
    event.preventDefault();
    return false
});



//on the click of the submit button when adding a new oligo
jQuery('#addoligo-form').live("submit", function(event) {
    $form = jQuery(this);

    //get the form values
    var oligo_name = jQuery('#add-oligo_name').val();
    var oligo_seq = jQuery('#add-oligo_seq').val();
    var template = jQuery('#add-template').val();
    var tags = jQuery('#add-tags').val();
    var owner = jQuery('#add-owner').val();
    var comments = jQuery('#add-comments').val();

    var actionURL = jQuery('#addoligo-form').attr('action')

    //make the postdata
    var postData = '&oligo_name='+oligo_name+'&oligo_seq='+oligo_seq+'&template='+template+'&tags='+tags+'&owner='+owner+'&comments='+comments+'&action=add_oligo';
    //call your .php script in the background, 
    //when it returns it will call the success function if the request was successful or 
    //the error one if there was an issue (like a 404, 500 or any other error status)

    jQuery.ajax({
    url : actionURL,
    type: "POST",
    data : postData,
    success: function(data,status,  xhr)
     {

        //if success then just output the text to the status div then clear the form inputs to prepare for new data
        if ( data.indexOf('oligo added') > -1 ) {
            cssAlert("Oligo succesfully added to the database", "alert-success");
        }
        else {
            cssAlert("Something went wrong!", "alert-error");
        }
        
        ajaxRefresholigoTable();
        
        //keep everything in the module except the sequence
        next_oligo_name = oligo_name.split("-")[0] + "-" + (Number(oligo_name.split("-")[1]) + 1);
        jQuery('#oligo_name').val(next_oligo_name);

        jQuery('#oligo_seq').val('');
        jQuery('#template').val(template);
        jQuery('#tags').val(tags);
        jQuery('#comments').val(comments);
         },

    error: function (jqXHR, status, err) 
      {
        cssAlert(err, "alert-error");
         }
     });

    // stop the form from submitting the normal way and refreshing the page
    event.preventDefault();
    return false
});


jQuery('#editoligo-form').live("submit", function(event) {    //get the form values
    var oligo_id = jQuery('#edit-oligo_id').val();
    var oligo_name = jQuery('#edit-oligo_name').val();
    var oligo_seq = jQuery('#edit-oligo_seq').val();
    var template = jQuery('#edit-template').val();
    var tags = jQuery('#edit-tags').val();
    var owner_id = jQuery('#edit-owner_id').val();
    var comments = jQuery('#edit-comments').val();

    var actionURL = jQuery('#editoligo-form').attr('action')
    var postData = '&oligo_id='+oligo_id+'&oligo_name='+oligo_name+'&oligo_seq='+oligo_seq+'&template='+template+'&tags='+tags+'&owner_id='+owner_id+'&comments='+comments+'&action=edit_oligo';
    

    jQuery.ajax({
    url : actionURL,
    type: "POST",
    data : postData,
    success: function(data, status, xhr)
     {
       
        //if success then just output the text to the status div then clear the form inputs to prepare for new data
        if ( data.indexOf('Changes saved') > -1 ) {
            cssAlert("Oligo information were succesfully modified", "alert-success");
        }
        else {
            cssAlert("Something went wrong!", "alert-error");
        }
       
       var jQData = jQuery('<div>', {html:data});
       var new_content = jQData.find('#editoligo_content').html();
       jQuery('#editoligo_content').html( new_content );
       jQuery( "#accordion" ).accordion( "option", "active", 1 );
       
     },

    error: function (jqXHR, status, err) 
      {
        cssAlert(err, "alert-error");
         }
     });
    
    ajaxRefresholigoTable();

    
    // stop the form from submitting the normal way and refreshing the page
    event.preventDefault();
    return false
    
});

// Edit the oligo //
function viewOligo(oligo_name) {
    
    var actionURL = jQuery('#editoligo-form').attr('action')
    
    //make the postdata
    var postData = '&oligo_name='+oligo_name+'&action=view_oligo';

    jQuery.ajax({
    url : actionURL,
    type: "POST",
    data : postData,
    success: function(data, status, xhr)
     {
       var jQData = jQuery('<div>', {html:data});
       var new_content = jQData.find('#editoligo_content').html();
       jQuery('#editoligo_content').html( new_content );
       jQuery("#accordion").accordion( "option", "active", 1 );
       jQuery("#btn_editoligo").attr("disabled", false);
     },

    error: function (jqXHR, status, err) 
      {
        cssAlert(err, "alert-error");
         }
     });

    // stop the form from submitting the normal way and refreshing the page
    event.preventDefault();
    return false
}


// Copy and paste of sequence //
function SelectText(element) { 
    element = element.replace('#','');
    var doc = document
        , text = doc.getElementById(element)
        , range, selection
    ;    
    if (doc.body.createTextRange) {
        range = document.body.createTextRange();
        range.moveToElementText(text);
        range.select();
    } else if (window.getSelection) {
        selection = window.getSelection();        
        range = document.createRange();
        range.selectNodeContents(text);
        selection.removeAllRanges();
        selection.addRange(range);
    }
}

function copyToClipboard(element) {
  sequence = jQuery(element).text();
  SelectText(element);
  try {
    var successful = document.execCommand('copy');
    var msg = successful ? 'successful' : 'unsuccessful';
    console.log('Copying text command was ' + msg);
    cssAlert("Sequence succesfully copied to clipboard", "alert-success");
  } catch (err) {
    console.log('Oops, unable to copy');
    cssAlert("Sequence could not be copied to clipboard", "alert-error");

  }
  return false
}

// Nucleotide functions //

function oligodb_count_chars(val){
    var len = val.value.length;
    jQuery("#oligo_len").val(len);
};


function calculate_properties(val, pref) {
    newOligo = new Oligo(val.value);
    val.value = newOligo.Sequence;
    
    jQuery("#"+pref+"-oligo_len").val(newOligo.len);
    jQuery("#"+pref+"-oligo_tm").val(newOligo.Tm());
}

function Oligo(sequence) {
    this.Sequence = RemoveNonBase(sequence);
    this.aCount = count_base(sequence, "A");
    this.cCount = count_base(sequence, "C");
    this.gCount = count_base(sequence, "G");
    this.tCount = count_base(sequence, "T");
    this.Tm = Tm;
    this.GC = GC;
    this.MW = MW;
    this.OD = OD;
    this.len = this.Sequence.length;
}

function OD() {
    if (this.Sequence.length > 0) {
        return Math.round(1000000 / (this.gCount * 11.7 + this.cCount * 7.3 + this.aCount * 15.4 + this.tCount * 8.8));
    } else {
        return ""
    }
}

function MW() {
    if (this.Sequence.length > 0) {
        return Math.round(313.2 * this.aCount + 328.2 * this.gCount + 289.2 * this.cCount + 304.2 * this.tCount - 60.96);
    } else {
        return ""
    }
}

function GC() {
    if (this.Sequence.length > 0) {
        return Math.round(100 * (this.gCount + this.cCount) / this.Sequence.length);
    } else {
        return ""
    }
}

function Tm() {
    if (this.Sequence.length > 0) {
        if (this.Sequence.length < 14) {
            return Math.round(2 * (this.aCount + this.tCount) + 4 * (this.gCount + this.cCount))
        } else {
            return Math.round(64.9 + 41 * ((this.gCount + this.cCount - 16.4) / this.Sequence.length))
        }
    } else {
        return ""
    }
}

//function reverse_complement() {
  //var r; // Final reverse - complemented string
  //var x; // nucleotide to convert
  //var n; // converted nucleotide
  //var i;
  //var k; 
   
  //if (this.Sequence.length==0)
    //return ""; // Nothing to do


  //// Go in reverse
  //for (k=this.Sequence.length-1; k>=0; k--) {
    //x = this.Sequence.substr(k,1);
    
    //if (x=="a") n="t"; else
    //if (x=="A") n="T"; else
    //if (x=="g") n="c"; else
    //if (x=="G") n="C"; else
    //if (x=="c") n="g"; else
    //if (x=="C") n="G"; else
    //if (x=="t") n="a"; else
    //if (x=="T") n="A"; else
    //// RNA?
    //if (x=="u") n="a"; else
    //if (x=="U") n="A"; else

    //// IUPAC? (see http://www.bioinformaticthis.Sequence.org/sms/iupac.html)
    //if (x=="r") n="y"; else
    //if (x=="R") n="Y"; else
    //if (x=="y") n="r"; else
    //if (x=="Y") n="R"; else
    //if (x=="k") n="m"; else
    //if (x=="K") n="M"; else
    //if (x=="m") n="k"; else
    //if (x=="M") n="K"; else
    //if (x=="b") n="v"; else
    //if (x=="B") n="V"; else
    //if (x=="d") n="h"; else
    //if (x=="D") n="H"; else
    //if (x=="h") n="d"; else
    //if (x=="H") n="D"; else
    //if (x=="v") n="b"; else
    //if (x=="V") n="B"; else

    //// Leave characters we do not understand as they are.
    //// Also S and W are left unchanged.
    //n = x;
    //r = r + n;
  //}
  //return r;
 //}

function RemoveNonBase(theString) {
    var returnString = ""
    theString = theString.toUpperCase()
    for (var i = 0; i < theString.length; i++) {
        if ((theString.charAt(i) == "A") || (theString.charAt(i) == "G") || (theString.charAt(i) == "C") || (theString.charAt(i) == "T")) {
            returnString += theString.charAt(i)
        }
    }
    return returnString.toUpperCase()
}

function count_base(sequence, base) {
    sequence = sequence.toUpperCase()
    base = base.toUpperCase()
    return sequence.split(base).length -1;
}

