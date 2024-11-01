function init() {
	tinyMCEPopup.resizeToInnerSize();
}

function is_vuact( cadena ) {
	var answer=false;
	var filter=/^http:\/\/www.vuact.com(.+)$/;
	if (filter.test(cadena)) {
		answer=true;
	}
	return answer;
}

function is_youtube( cadena ) {
	var answer=false;
	var filter=/^http:\/\/www.youtube.com(.+)$/;
	if (filter.test(cadena)) {
		answer=true;
	}
	return answer;
}

function is_vimeo( cadena ) {
	var answer=false;
	var filter=/^http(|s):\/\/(|www.)vimeo.com(.+)$/;
	if (filter.test(cadena)) {
		answer=true;
	}
	return answer;
}

function is_googlevideo( cadena ) {
	var answer=false;
	var filter=/^http:\/\/video.google.com(.+)$/;
	if (filter.test(cadena)) {
		answer=true;
	}
	return answer;
}

function insertVuact_embedLink() {

	var tagtext;
	var add_text = false;
	var error = true;

	var vuact_embed = document.getElementById('vuact_embed_insertlinkpanel');

	// who is active ?
	if(vuact_embed.className.indexOf('current') != -1) {
		var link = document.getElementById('vuact_embedlink').value;
		var type = 'error';

		if(is_vuact(link)) {
			type = 'vuact';
			error = false;
		}

		if(is_vimeo(link)) {
			type = 'vimeo';
			error = false;
		}

		if(is_youtube(link)) {
			type = 'youtube';
			error = false;
		}

		if(is_googlevideo(link)) {
			type = 'googlevideo';
			error = false;
		}

		if(error) {
			link = "Not a Vuact, YouTube, Vimeo or Google Video URL: " + link;
		}

		tagtext = "[vuact_embed " + type + "='" + link + "']";
		add_text = true;
	}


	if(add_text) {
		window.tinyMCEPopup.execCommand('mceInsertContent', false, tagtext);
	}
	window.tinyMCEPopup.close();
}

window.addEventListener("message",
	function(e){
		// no reason to check for origin policy
		// if ( e.origin !== "http://www.vuact.com" ) return;
		var link = e.data;
		if(!is_vuact(link)) link = "Not a Vuact URL: " + link;
		var tagtext = "[vuact_embed vuact='" + link + "']";
		window.tinyMCEPopup.execCommand('mceInsertContent', false, tagtext);
		window.tinyMCEPopup.close();
	}, false
);
