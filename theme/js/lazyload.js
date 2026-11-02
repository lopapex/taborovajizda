function init(event) {
	// Setup observer
	var options = {
		root: null, 			// root element is viewport
		rootMargin: '100px',	// display image when still 100px outside the vieport region 
		threshold: 0.0			// fire callback when 1st pixel enters the area
	}
	var observer = new IntersectionObserver(inView, options);
	
	// Observe <img class="lazyload">
	var contentImages = document.querySelectorAll('img.envira-gallery-image');
	for (i = 0; i < contentImages.length; ++i) {
		observer.observe(contentImages[i]);
	}
	console.log('Lazyloading init...');
}

function inView(entries, observer) {
	for (var i=0; i<entries.length; i++) {
		if (entries[i].isIntersecting) {
			//if (entries[i].target.classList.contains('lazyload')) {
				let src = entries[i].target.getAttribute('data-src');
				entries[i].target.setAttribute('src', src);
				//entries[i].target.setAttribute('data-safe-src', src);
				//entries[i].target.setAttribute('data-envira-src', src);
				let sizes = entries[i].target.getAttribute('data-sizes');
				entries[i].target.setAttribute('sizes', sizes);
				let srcset = entries[i].target.getAttribute('data-srcset');
				entries[i].target.setAttribute('srcset', srcset);
				entries[i].target.classList.replace('lazyload', 'loaded');
			//}
		}
	}
}

window.onload =  init;
