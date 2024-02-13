//Навигация и всплывающие окна
document.querySelector('.burger').onclick = function() {
	let parent = this.parentElement;
	if (parent.classList.contains('-active')) {
		parent.classList.remove('-active');
	} else {
		parent.classList.add('-active');
	}
	return false;
}
document.querySelector('.lang .trigger').onclick = function() {
	let parent = this.parentElement;
	if (parent.classList.contains('-active')) {
		parent.classList.remove('-active');
	} else {
		parent.classList.add('-active');
	}
	return false;
}
document.querySelectorAll('.tel-code .trigger').forEach(function(code) {
	code.onclick = function() {
		let parent = this.parentElement;
		if (parent.classList.contains('-active')) {
			parent.classList.remove('-active');
		} else {
			parent.classList.add('-active');
		}
		return false;
	}
});
document.querySelectorAll('.tel-code .code-popup a').forEach(function(code) {
	code.onclick = function() {
		let telcode = this.innerText,
		parent = this.parentElement.parentElement;
		parent.querySelector('input[type="hidden"]').value = telcode;
		parent.querySelector('.trigger').innerHTML = code.innerHTML;
		parent.classList.remove('-active');
		return false;
	}
});


document.addEventListener('click', function(event) {
	let elem = document.querySelector('.body-header .primary.-active');
	if (elem) {
	    var isClickInside = elem.contains(event.target);

	    if (!isClickInside) {
	        elem.classList.remove('-active');
	    }
	}

	elem = document.querySelector('.body-header .lang.-active');
	if (elem) {
	    var isClickInside = elem.contains(event.target);

	    if (!isClickInside) {
	        elem.classList.remove('-active');
	    }
	}

	elem = document.querySelector('.tel-code.-active');
	if (elem) {
	    var isClickInside = elem.contains(event.target);

	    if (!isClickInside) {
	        elem.classList.remove('-active');
	    }
	}
});


document.querySelector('.popup-bg').onclick = function() {
	document.querySelectorAll('.popup').forEach((elem) => elem.classList.remove('-visible'));
	document.body.classList.remove('-fixed');
	this.classList.remove('-visible');
	document.querySelector('.burger').classList.remove('-active');
}

document.querySelectorAll('.popup .close').forEach(function(button) {
	button.onclick = function() {
		document.querySelectorAll('.popup').forEach((elem) => elem.classList.remove('-visible'));
		document.body.classList.remove('-fixed');
		document.querySelector('.burger').classList.remove('-active');
	}
});

function showPopup(selector) {
	let popup = document.querySelector(selector);
	document.querySelector('.popup-bg').classList.add('-visible');
	document.querySelectorAll('.popup').forEach((elem) => elem.classList.remove('-visible'));
	popup.classList.add('-visible');
	document.body.classList.add('-fixed');
	if (popup.clientHeight > window.innerHeight)
		popup.style.top = 0;
	else
		popup.style.top = (window.innerHeight - popup.clientHeight) / 2 + 'px';

	window.onresize = function() {
		if (popup.classList.contains('-visible')) {
			if (popup.clientHeight > window.innerHeight)
				popup.style.top = 0;
			else
				popup.style.top = (window.innerHeight - popup.clientHeight) / 2 + 'px';
		}
	}
}

document.querySelectorAll('.js-popup-auth').forEach(function(button) {
	button.onclick = function() {
		showPopup('.popup.-auth');
		return false;
	}
});

document.querySelectorAll('.js-popup-register').forEach(function(button) {
	button.onclick = function() {
		showPopup('.popup.-register');
		return false;
	}
});

document.querySelectorAll('.js-popup-forgot').forEach(function(button) {
	button.onclick = function() {
		showPopup('.popup.-forgot');
		return false;
	}
});

function handleFirstTab(e) {
	if (e.keyCode === 9) {
		document.body.classList.add('user-is-tabbing');
		window.removeEventListener('keydown', handleFirstTab);
	}
}
window.addEventListener('keydown', handleFirstTab);

const clientsSwiper = new Swiper('.clients .swiper', {
	slidesPerView: 4,
	spaceBetween: 40,
	navigation: {
		prevEl: '.clients .prev',
		nextEl: '.clients .next'
	}
});


var inputs = document.querySelectorAll( '.inputfile' );
Array.prototype.forEach.call( inputs, function( input )
{
	var label	 = input.nextElementSibling,
		labelVal = label.innerHTML;

	input.addEventListener( 'change', function( e )
	{
		var fileName = '';
		if( this.files && this.files.length > 1 )
			fileName = ( this.getAttribute( 'data-multiple-caption' ) || '' ).replace( '{count}', this.files.length );
		else
			fileName = e.target.value.split( '\\' ).pop();

		if( fileName )
			label.innerHTML = fileName;
		else
			label.innerHTML = labelVal;
	});
});
