function updateClock(force) {
	let clock_counter = document.getElementById('clock_counter');
	clock_counter.value++;
	let clock_display_separator = document.getElementById('clock_display_separator');
	if (clock_counter.value % 2) {
		clock_display_separator.style.visibility = 'hidden';
	} else {
		clock_display_separator.style.visibility = 'visible';
	}
	if (clock_counter.value % 60 == 0 || force) {
		let hours = parseInt(clock_counter.value / 60 / 60);
		let minutes = parseInt(clock_counter.value / 60) % 60;
		if (hours < 10) {
			hours = '0' + hours;
		}
		if (minutes < 10) {
			minutes = '0' + minutes;
		}
		let clock_display_hours = document.getElementById('clock_display_hours');
		let clock_display_minutes = document.getElementById('clock_display_minutes');
		clock_display_hours.replaceChild(document.createTextNode(hours), clock_display_hours.firstChild);
		clock_display_minutes.replaceChild(document.createTextNode(minutes), clock_display_minutes.firstChild);
	}
}

