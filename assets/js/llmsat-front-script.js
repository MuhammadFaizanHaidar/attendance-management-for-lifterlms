var toggler = document.getElementsByClassName("llmsat-caret");
var i;

for (i = 0; i < toggler.length; i++) {
  toggler[i].addEventListener("click", function() {
    this.parentElement.querySelector(".llmsat-nested").classList.toggle("active");
    this.classList.toggle("llmsat-caret-down");
  });
}
function llmsat_attendance_btn_ajax( postId, usrid ) {

	// body...
	var post_id = postId;
	var user_ID = usrid;

	jQuery.ajax({
		url : llmsat_ajax_url.ajax_url,
		type : 'post',
		data : {
			action : 'llmsat_attendance_btn_ajax_action',
			pid : post_id,
			uid : user_ID
		},
		success : function( response ) {
			
			var suffix = response.match(/\d+/); // 123
			console.log(suffix[0]);
			if ( suffix[0] == "2" ) {
				jQuery("#llmsat-ajax-response-id span").addClass( 'llmsat-error' );
			} else if( suffix[0] == "1" || suffix[0] == "3") {
				jQuery("#llmsat-ajax-response-id span").addClass( 'llmsat-success' );
			}
			jQuery("#llmsat-ajax-response-id span").html( response.replace(/\d+/g, '') );
		}
	});
}

