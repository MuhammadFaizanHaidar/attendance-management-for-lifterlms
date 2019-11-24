console.log("pkkkkkkk");
jQuery(document).ready(function(){
   
    var pageURL = jQuery(location).attr("href");
    jQuery(document).ready(function(){
        var uri = window.location.toString();
        if ( uri.indexOf( "&s=" ) > 0 ) {
            var clean_uri = uri.substring( 0, uri.indexOf( "&s=" ) );
            window.history.replaceState( {}, document.title, clean_uri );
            
        }
    });
    return;
});