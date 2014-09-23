function updateClock(force) {
	var clock_counter = document.getElementById('clock_counter');
	clock_counter.value++;
	var clock_display_separator = document.getElementById('clock_display_separator');
	if (clock_counter.value % 2) {
		clock_display_separator.style.visibility = 'hidden';
	} else {
		clock_display_separator.style.visibility = 'visible';
	}
	if (clock_counter.value % 60 == 0 || force) {
		var hours = parseInt(clock_counter.value / 60 / 60);
		var minutes = parseInt(clock_counter.value / 60) % 60;
		if (hours < 10) {
			hours = '0' + hours;
		}
		if (minutes < 10) {
			minutes = '0' + minutes;
		}
		var clock_display_hours = document.getElementById('clock_display_hours');
		var clock_display_minutes = document.getElementById('clock_display_minutes');
		clock_display_hours.replaceChild(document.createTextNode(hours),clock_display_hours.firstChild);
		clock_display_minutes.replaceChild(document.createTextNode(minutes),clock_display_minutes.firstChild);
	}
}

function ParseAjaxResponse(somemixedcode) {
	var source = somemixedcode;
	var scripts = new Array();
	while (source.indexOf("<script") > -1 || source.indexOf("</script") > -1) {
		var s = source.indexOf("<script");
		var s_e = source.indexOf(">", s);
		var e = source.indexOf("</script", s);
		var e_e = source.indexOf(">", e);
		scripts.push(source.substring(s_e + 1, e));
		source = source.substring(0, s) + source.substring(e_e + 1);
	}
	for ( var x = 0; x < scripts.length; x++) {
		try {
			eval(scripts[x]);
		} catch (ex) {
		}
	}
	return source;
} 
