$(document).ready(function(){
    my_hash = location.hash
    if(!my_hash){
        window.location = "/my-hashtags";
    }
    
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
                        $("#loading").html("<i>tweet sent</i>");
                        setTimeout("fadeAndErase($('#loading'))",2000);
                        $("textarea").val("");
                        $("textarea").focus();
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