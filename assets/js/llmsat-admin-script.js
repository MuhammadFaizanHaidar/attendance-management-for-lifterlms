
jQuery(document).ready(function(){
    if ( llmsat_block_editor.block_editor_active == "yes" ) {
        jQuery('#search-submit').on('click', function() {	
            var href = window.location.href.substring(0, window.location.href.indexOf('?'));
            var qs = window.location.href.substring(window.location.href.indexOf('?') + 1, window.location.href.length);
            var newParam = "s" + '=' +  jQuery('#students-search-input').val();

            if (qs.indexOf('s'+ '=') == -1) {
                if (qs == '') {
                    qs = '?'
                } else {
                    qs = qs + '&'
                }
                qs = qs + newParam;

            } else {
                var start = qs.indexOf('s'+ "=");
                var end = qs.indexOf("&", start);
                if (end == -1) {
                    end = qs.length;
                }
                var curParam = qs.substring(start, end);
                qs = qs.replace(curParam, newParam);
            }
            window.location.replace(href + '?' + qs);
        });
    }
    
    jQuery(document).ready(function(){
        var uri = window.location.toString();
        if ( uri.indexOf( "&s=" ) > 0 ) {
            var clean_uri = uri.substring( 0, uri.indexOf( "&s=" ) );
            window.history.replaceState( {}, document.title, clean_uri );
            
        }
    });
    return;
});