function handleKeyDown(e) {
	//handleFirstTab
	if (e.keyCode === 9) {
		document.body.classList.add('user-is-tabbing');
		window.removeEventListener('keydown', handleFirstTab);
	}
}
window.addEventListener('keydown', handleKeyDown);
