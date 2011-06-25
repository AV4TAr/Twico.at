$(document).ready(function(){

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