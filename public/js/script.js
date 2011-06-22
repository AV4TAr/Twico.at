$(document).ready(function(){
    my_hash = location.hash
    
    $("#appendText").val(my_hash);
    limitChars(140, $("#tweetCount"));
    
    $("textarea").live('keyup', function(e) {
        limitChars(140, $("#tweetCount"));
    });
    
    $("input").live('keyup', function(e) {
        limitChars(140, $("#tweetCount"));
    });
    
    $("#twicoform").live('submit',function() {
        tweetText = $("textarea").val()+" "+$("#appendText").val();
        if(jQuery.trim(tweetText)!="" || jQuery.trim($("textarea").val())) {
            $("#loading").html("<img src='/img/ajax-loader.gif'/>");
            try {
                $.getJSON("/tweet/post", {text:tweetText,format:"json"}, function(data){
                    if(data.status=="ok") {
                        $("#loading").html("<small>tweet sent</small>");
                        setTimeout("fadeAndErase($('#loading'))",2000);
                    } else {

                    }
                });
            } catch (err) {
                console.log(err);
            }
        } else {
            $("textarea").focus();
        }
        
        return false;
    });
});

function limitChars(limit, infofield) {
    var infoFieldObj;

    var text = $("textarea").val()+" "+$("#appendText").val();
    if(typeof infofield == 'string') {
        infoFieldObj = $('#' + infofield);
    } else {
        infoFieldObj = infofield;
    }
    if(jQuery.trim(text)!="") {
        var textlength = text.length;
        if(textlength >= limit) {
            textObj.val(text.substr(0,limit));
            infoFieldObj.html('0');
            return false;
        } else {
            infoFieldObj.html(limit-textlength);
            return true;
        }
    } else {
        infoFieldObj.html(limit);
    }
    return false;
}

function fadeAndErase(obj) {
    obj.fadeOut('slow', function() {
        obj.html("");
    });
}