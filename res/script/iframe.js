
function adjustIframeHeight(iframe) {
	var size = iframe.contentWindow.document.body.scrollHeight;
	iframe.style.height = Math.max(size, window.innerHeight / 10) + "px";
}
