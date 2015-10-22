
document.write = function(_sHTML) {
	$('#iiigel-interpreter')[0].innerHTML += _sHTML;
}

document.writeln = function(_sHTML) {
	document.write(_sHTML + "\n");
}
