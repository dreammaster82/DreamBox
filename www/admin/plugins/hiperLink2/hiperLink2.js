function sendPost(href){
	var text = document.getElementById('linkText').value.replace(/"/g, '\'');
	text = text.replace(/\n/g, '<br />');
	window.opener.InsText(document.getElementById('idArea').value, '<a href="' + href + '" class="quotes" title="' + text + '" target="_blank">' + document.getElementById('linkName').value + '</a>');
	window.close();
}