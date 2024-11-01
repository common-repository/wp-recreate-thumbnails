function singleThumb(id){
    var redirect_url = "";
        jQuery.ajax({
            url: passed_object.url, 
            method: "POST",
            data: {
                action:"yspl_rbt_create_single_thumb",   
                id:id
            },
            success: function(response){
                redirect_url =  response.data.url;
                jQuery.redirect(redirect_url, { file:response.data.filename} );
            },
        });
        
    }
jQuery(function(){
    jQuery(".btn_regen").click(function(){

        jQuery('#result').html('<img class="loader" src="'+passed_object.ajax_loader+'"> loading...');
        jQuery.ajax({
            xhr: function () {
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", function (evt) {
                if (evt.lengthComputable) {
                    var percentComplete = evt.loaded / evt.total;
                }
            }, false);
            xhr.addEventListener("progress", function (evt) {
                if (evt.lengthComputable) {
                    var percentComplete = evt.loaded / evt.total;
                    jQuery('.progress').css({
                        width: percentComplete * 100 + '%'
                    });
                    if (percentComplete === 1) {
                        jQuery('.progress').addClass('hide');
                    }
                }
            }, false);
            return xhr;
            },
            url: passed_object.url, 
            method: "POST",
            data: {
                action:passed_object.action,   
            },
            error: function() {
                jQuery("#result").html("<h4>Some thing went wrong!</h4>");
            },
            success: function(response){
                var html = "";
                html += "<table class='thumb_table'><th colspan='2'><h4>Created Thumbnails</h4></th><tr class='size-label'><td>Filename</td><td>Generated Files</td></tr>";
                jQuery.each(response.data.data, function(k, v) {
                    var row = "<tr><td>"+v.file.filename+"</td>";
                    row += "<td><ul>";
                    var thumb_div = "";
                    jQuery.each(v.file.thumbnails,function (size,img_file){
                        thumb_div += "<li class='bg-row'><span class='size-label'>"+size+"</span> : <span class='file-label'>"+img_file+"</span></li><br>";
                    });
                    html += row+thumb_div+"</td></ul></tr>";
                });
                html += "</table>"
                jQuery("#result").html(html);
            },
        });
    });
});