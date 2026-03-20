if(typeof localStorage.player_volume == "undefined") localStorage.player_volume = 0.15;

var dashboardLoader, dashboardBody, dashboardBase, dashboardBackground, dashboardFooter;
var intervals = [];
var searchLists = [];
var pageLoaders = {};
var updateFilters = true;
var currentPageScript = null;

// Функция для выполнения скрипта с обработкой ошибок (вместо eval)
function executePageScript(scriptContent) {
	try {
		// Очистить предыдущий скрипт если он есть
		if(currentPageScript) {
			try {
				if(currentPageScript.cleanup && typeof currentPageScript.cleanup === 'function') {
					currentPageScript.cleanup();
				}
			} catch(e) {
				console.error('Error in page script cleanup:', e);
			}
		}
		
		// Создать функцию из содержимого скрипта
		const pageScriptFunc = new Function(scriptContent);
		currentPageScript = {};
		
		// Выполнить в контексте глобального scope
		pageScriptFunc.call(window);
		
		return true;
	} catch(e) {
		console.error('Error executing page script:', e);
		showToast('<i class="fa-solid fa-xmark"></i>', 'Ошибка загрузки страницы: ' + e.message, 'error');
		return false;
	}
}

// Функция для очистки всех интервалов
function clearAllIntervals() {
	intervals.forEach(interval => {
		try {
			clearInterval(interval);
		} catch(e) {
			console.error('Error clearing interval:', e);
		}
	});
	intervals = [];
}

// Глобальный обработчик ошибок JavaScript
window.addEventListener('error', (event) => {
	console.error('JavaScript Error:', event.error);
	// Не показываем ошибку пользователю - только логируем
	// Это предотвратит полный краш приложения
	event.preventDefault();
});

window.addEventListener('unhandledrejection', (event) => {
	console.error('Unhandled Promise Rejection:', event.reason);
	event.preventDefault();
});

// Функция для нормализации URL - добавляет .php расширение если необходимо
// НО не добавляет .php для путей, которые обрабатываются web server rewrite rules
function normalizePageUrl(url) {
	// Прервать обработку если уже есть расширение файла
	const urlWithoutQuery = url.split('?')[0];
	const urlWithoutHash = urlWithoutQuery.split('#')[0];
	
	// Если уже есть расширение (обычно .php, .html) - не менять
	if(/\.[a-zA-Z0-9]+$/.test(urlWithoutHash)) {
		return url;
	}
	
	// Если это корневой путь или пустая строка
	if(!urlWithoutHash || urlWithoutHash === '/' || urlWithoutHash === './') {
		return url;
	}
	
	// Пути которые обрабатываются web server rewrite rules и НЕ должны получать .php
	// Это пути с параметрами как: profile/username, clan/clanname, browse/levels/123 и т.д.
	const rewriteRulePrefixes = [
		'profile/',
		'clan/',
		'messenger/',
		'browse/levels/',
		'browse/lists/',
		'browse/mappacks/',
		'browse/gauntlets/',
		'browse/accounts/',
		'browse/songs/',
		'browse/sfxs/',
		'mod/roles/'
	];
	
	// Проверить если путь начинается с одного из prefixes с параметром
	for(let prefix of rewriteRulePrefixes) {
		if(urlWithoutHash.startsWith(prefix)) {
			// Если есть слэш после prefix (например profile/username) - это параметр
			// Не добавляем .php, web server обработает через rewrite rule
			return url;
		}
	}
	
	// Добавить .php расширение перед query параметрами для остальных путей
	const queryIndex = url.indexOf('?');
	const hashIndex = url.indexOf('#');
	
	let baseUrl = url;
	let queryAndHash = '';
	
	if(queryIndex !== -1) {
		baseUrl = url.substring(0, queryIndex);
		queryAndHash = url.substring(queryIndex);
	} else if(hashIndex !== -1) {
		baseUrl = url.substring(0, hashIndex);
		queryAndHash = url.substring(hashIndex);
	}
	
	// Если базовый URL не заканчивается на слэш и не имеет расширения
	if(baseUrl && !baseUrl.endsWith('/') && !/\.[a-zA-Z0-9]+$/.test(baseUrl)) {
		baseUrl += '.php';
	}
	
	return baseUrl + queryAndHash;
}

window.addEventListener('load', () => {
	dashboardLoader = document.getElementById("dashboard-loader");
	dashboardBody = document.getElementById("dashboard-body");
	dashboardBase = document.querySelector("base");
	dashboardBackground = document.querySelector("span.background");
	dashboardFooter = document.querySelector("footer");
	
	dashboardBody.classList.add("hide");
	
	window.baseURL = new URL(dashboardBase.getAttribute("href"), window.location.href);
	
	loadAudioPlayer();
	updatePage();
	updateNavbar();
	
	window.addEventListener("popstate", (event) => {
		const newHref = decodeURIComponent(event.target.location.href).substr(baseURL.href.length);
		
		return getPage(newHref, false);
	});
	window.addEventListener("wheel", () => document.querySelector("[dashboard-context-menu].show")?.classList.remove("show"));
	
	setTimeout(() => dashboardLoader.classList.add("hide"), 200);
});

async function getPage(href, loaderType = 'loader') {
	// Нормализировать URL и добавить .php если нужно
	href = normalizePageUrl(href);
	
	if(loaderType && ((window.location.href.endsWith(href) && href.length) || (!href.length && dashboardBase.getAttribute("href") == './'))) return false;
	
	var pageLoaderType = loaderType;
	
	if(loaderType) pageLoaders[href] = loaderType;
	else pageLoaderType = pageLoaders[href];
	
	activateLoaderOfType(pageLoaderType);
	
	switch(true) {
		case href == '@':
			pageLoaderType = false;
			href = window.location.href;
			break;
		case href.startsWith('@'):
			const newParameters = href.substring(1).split("&");
			const urlParams = new URLSearchParams(window.location.search);
			
			newParameters.forEach(newParameter => {
				newParameter = newParameter.split("=");
				
				if(newParameter[1] != 'REMOVE_QUERY') urlParams.set(newParameter[0], newParameter[1]);
				else urlParams.delete(newParameter[0]);
			});
			
			const urlParamsText = urlParams.toString();
			
			href = urlParamsText.length ? window.location.pathname + "?" + urlParamsText : window.location.pathname;
			
			break;
	}
	
	try {
		const pageRequest = await fetch(href);
		
		// Проверить статус ответа
		if(!pageRequest.ok) {
			console.error(`HTTP Error: ${pageRequest.status} ${pageRequest.statusText} for ${href}`);
			activateLoaderOfType(false);
			
			let errorMessage = 'Ошибка загрузки страницы';
			if(pageRequest.status === 500) {
				errorMessage = 'Ошибка сервера (500). Проверьте консоль сервера.';
			} else if(pageRequest.status === 404) {
				errorMessage = 'Страница не найдена: ' + href;
			}
			
			Toastify({
				text: '<i class="fa-solid fa-xmark"></i>' + errorMessage,
				duration: 3000,
				position: "center",
				escapeMarkup: false,
				className: 'error',
			}).showToast();
			
			return false;
		}
		
		const response = await pageRequest.text();
		
		const updatePageDetails = await changePage(response, href, loaderType);
		
		if(updatePageDetails) setTimeout(() => activateLoaderOfType(false), 100);
		
		return true;
	} catch(error) {
		console.error('Network error fetching page:', error);
		activateLoaderOfType(false);
		
		Toastify({
			text: '<i class="fa-solid fa-xmark"></i>Ошибка подключения: ' + error.message,
			duration: 3000,
			position: "center",
			escapeMarkup: false,
			className: 'error',
		}).showToast();
		
		return false;
	}
}

async function postPage(href, form, loaderType = 'loader') {
	return new Promise(async (r) => {
		// Нормализировать URL и добавить .php если нужно
		href = normalizePageUrl(href);
		
		const formData = await getForm(form);
		if(!formData) return r(false);

		var pageLoaderType = loaderType;
	
		if(loaderType) pageLoaders[href] = loaderType;
		else pageLoaderType = pageLoaders[href];
		
		activateLoaderOfType(pageLoaderType);
		
		switch(true) {
			case href == '@':
				href = window.location.href;
				break;
			case href.startsWith('@'):
				const newParameter = href.substring(1).split("=");
				
				const urlParams = new URLSearchParams(window.location.search);
				urlParams.set(newParameter[0], newParameter[1])
				
				break;
		}
		
		try {
			const pageRequest = await fetch(href, {
				method: "POST",
				body: formData
			});
			
			// Проверить статус ответа
			if(!pageRequest.ok) {
				console.error(`HTTP Error: ${pageRequest.status} ${pageRequest.statusText} for ${href}`);
				activateLoaderOfType(false);
				
				let errorMessage = 'Ошибка отправки данных';
				if(pageRequest.status === 500) {
					errorMessage = 'Ошибка сервера (500). Проверьте консоль сервера.';
				} else if(pageRequest.status === 404) {
					errorMessage = 'Страница не найдена: ' + href;
				}
				
				Toastify({
					text: '<i class="fa-solid fa-xmark"></i>' + errorMessage,
					duration: 3000,
					position: "center",
					escapeMarkup: false,
					className: 'error',
				}).showToast();
				
				return r(false);
			}
			
			const response = await pageRequest.text();
			
			href = pageRequest.url;
			
			const updatePageDetails = await changePage(response, href, pageLoaderType);
			
			if(updatePageDetails && pageLoaderType) setTimeout(() => activateLoaderOfType(false), 100);
			
			r(true);
		} catch(error) {
			console.error('Network error posting data:', error);
			activateLoaderOfType(false);
			
			Toastify({
				text: '<i class="fa-solid fa-xmark"></i>Ошибка подключения: ' + error.message,
				duration: 3000,
				position: "center",
				escapeMarkup: false,
				className: 'error',
			}).showToast();
			
			r(false);
		}
	});
}

function changePage(response, href, loaderType = false) {
	return new Promise(r => {
		try {
			const newPageBody = new DOMParser().parseFromString(response, "text/html");
			
			// Проверить есть ли ошибка парсирования
			if(newPageBody.querySelector('parsererror')) {
				console.error('Failed to parse HTML response');
				Toastify({
					text: '<i class="fa-solid fa-xmark"></i>Ошибка парсирования ответа сервера',
					duration: 3000,
					position: "center",
					escapeMarkup: false,
					className: 'error',
				}).showToast();
				return r(true);
			}
			
			const oldPage = document.getElementById("dashboard-page");
			const newPage = newPageBody.getElementById("dashboard-page");
			
			if(newPage == null) {
				const toastBody = newPageBody.getElementById("toast");
				if(toastBody != null) return r(showToastOutOfPage(toastBody));
				
				Toastify({
					text: '<i class="fa-solid fa-xmark"></i>Ошибка загрузки страницы',
					duration: 3000,
					position: "center",
					escapeMarkup: false,
					className: 'error',
				}).showToast();
				
				return r(true);
			}
			
			// Очистить интервалы перед сменой страницы
			clearAllIntervals();
			
			newPage.classList.add("hide");
			
			if(!href.length) href = baseURL.pathname;
			if(loaderType) history.pushState(null, null, href);
			
			const newPageScript = newPageBody.getElementById("pageScript");
			
			const reportModal = newPageBody.getElementById("reportModal");
			const oldReportModal = document.getElementById("reportModal");
			if(oldReportModal != null) oldReportModal.remove();
			
			// Заменить элементы страницы
			oldPage.replaceWith(newPage);
			dashboardBody.scroll(0, 0);
			
			const newBase = newPageBody.querySelector("base");
			const oldBase = document.querySelector("base");
			if(newBase && oldBase) oldBase.replaceWith(newBase);
			
			const newTitle = newPageBody.querySelector("title");
			const oldTitle = document.querySelector("title");
			if(newTitle && oldTitle) oldTitle.replaceWith(newTitle);
			
			const newNav = newPageBody.querySelector("nav");
			const oldNav = document.querySelector("nav");
			if(newNav && oldNav) oldNav.replaceWith(newNav);
			
			const newDashboardScript = newPageBody.getElementById("dashboardScript");
			const oldDashboardScript = document.getElementById("dashboardScript");
			if(newDashboardScript && oldDashboardScript) oldDashboardScript.replaceWith(newDashboardScript);
			
			// Выполнить dashboardScript (глобальный скрипт)
			const dashboardScriptElement = document.getElementById("dashboardScript");
			if(dashboardScriptElement && dashboardScriptElement.textContent) {
				const scriptExecuted = executePageScript(dashboardScriptElement.textContent);
				if(!scriptExecuted) {
					return r(true);
				}
			}
			
			const newDashboardStyle = newPageBody.getElementById("dashboardStyle");
			const oldDashboardStyle = document.getElementById("dashboardStyle");
			if(newDashboardStyle && oldDashboardStyle) oldDashboardStyle.replaceWith(newDashboardStyle);
			
			// Выполнить pageScript если существует
			if(newPageScript != null && newPageScript.textContent) {
				const scriptExecuted = executePageScript(newPageScript.textContent);
				if(!scriptExecuted) {
					newPageScript.remove();
					return r(true);
				}
				newPageScript.remove();
			}
			
			if(reportModal != null) document.querySelector("body").appendChild(reportModal);
			
			dashboardBody = document.getElementById("dashboard-body");
			dashboardBase = document.querySelector("base");
			
			updatePage();
			updateNavbar();
			
			window.baseURL = new URL(dashboardBase.getAttribute("href"), window.location.href);
			
			return r(true);
		} catch(error) {
			console.error('Error changing page:', error);
			
			Toastify({
				text: '<i class="fa-solid fa-xmark"></i>Ошибка при смене страницы: ' + error.message,
				duration: 3000,
				position: "center",
				escapeMarkup: false,
				className: 'error',
			}).showToast();
			
			return r(true);
		}
	});
}

async function updateNavbar() {
	const navbarButtons = document.querySelectorAll("nav button");
	
	navbarButtons.forEach(navbarButton => {
		const href = navbarButton.getAttribute("href");
		const dropdown = navbarButton.getAttribute("dashboard-dropdown");
		
		const pageHref = decodeURIComponent(window.location.href).substr(baseURL.href.length);

		if(href != null && ((href.length && href == pageHref) || (!href.length && dashboardBase.getAttribute("href") == './'))) navbarButton.classList.add("current");
		
		if(dropdown != null) {
			const navbarDropdown = document.querySelector("#" + dropdown + " .dropdown-items");
			navbarDropdown.style = "--dropdown-height: " + navbarDropdown.scrollHeight + "px";
			
			navbarButton.addEventListener("mouseup", (event) => toggleDropdown(dropdown));
		}
	});
}

function toggleDropdown(dropdown) {
	const previousDropdown = document.querySelector(".dropdown.show");
	if(previousDropdown != null && previousDropdown.id != dropdown) previousDropdown.classList.remove("show");
	
	const newDropdown = document.getElementById(dropdown);
	if(newDropdown != null) newDropdown.classList.toggle("show");
}

function showToastOutOfPage(toastBody) {
	Toastify({
		text: toastBody.innerHTML,
		duration: 2000,
		position: "center",
		escapeMarkup: false,
		className: toastBody.getAttribute("state"),
	}).showToast();
	
	const toastifyBody = document.querySelector(".toastify");
	
	const copyElements = toastifyBody.querySelectorAll('[dashboard-copy]');
	copyElements.forEach(async (element) => {
		const textToCopy = element.innerHTML;
	
		if(!textToCopy.length) return;
			
		element.addEventListener("click", async (event) => copyElementContent(textToCopy));
	});
	
	const dateElements = toastifyBody.querySelectorAll('[dashboard-date]');
	dateElements.forEach(async (element) => {
		const dateTime = element.getAttribute("dashboard-date");
		
		const textStyle = element.getAttribute("dashboard-full") != null ? "long" : "short";
		
		element.innerHTML = timeConverter(dateTime, textStyle);
		intervals[intervals.length] = setInterval(async (event) => {
			element.innerHTML = timeConverter(dateTime, textStyle);
		}, 1000);
		
		element.onclick = () => {
			Toastify({
				text: timeConverter(dateTime, false),
				duration: 2000,
				position: "center",
				escapeMarkup: false,
				className: "info",
			}).showToast();
		}
	});
	
	const toastLocation = toastBody.getAttribute("location");
	if(toastLocation.length) {
		const toastLoaderType = toastBody.getAttribute("loader");
		getPage(toastLocation, toastLoaderType);
		return false;
	}
	
	return true;
}

async function showToast(toastIcon, toastText, toastStyle) {
	Toastify({
		text: toastIcon + toastText,
		duration: 2000,
		position: "center",
		escapeMarkup: false,
		className: toastStyle,
	}).showToast();
}

async function updatePage() {
	try {
		if(localStorage.enableLoweredMotion == "1") document.querySelector("body").classList.add("loweredMotion");
		else document.querySelector("body").classList.remove("loweredMotion");
		
		for(const element of document.querySelectorAll("[dashboard-hide=true]")) element.remove();
		for(const element of document.querySelectorAll("[dashboard-show=false]")) element.remove();
		
		const navbar = document.querySelector("nav");
		if(navbar) {
			navbar.addEventListener("mouseenter", () => dashboardBody.classList.remove("hide"));
			navbar.addEventListener("mouseleave", () => dashboardBody.classList.add("hide"));
		}
		
		const removeElements = dashboardBody.querySelectorAll('[dashboard-remove]');
		removeElements.forEach(async (element) => {
			try {
				const elementsToRemove = element.getAttribute("dashboard-remove").split(" ");
				elementsToRemove.forEach(async (remove) => element.removeAttribute(remove));
				element.removeAttribute("dashboard-remove");
			} catch(e) {
				console.error('Error removing attributes:', e);
			}
		});
		
		const copyElements = dashboardBody.querySelectorAll('[dashboard-copy]');
		copyElements.forEach(async (element) => {
			try {
				const textToCopy = element.innerHTML;
				if(!textToCopy.length) return;
				element.addEventListener("click", async (event) => copyElementContent(textToCopy));
			} catch(e) {
				console.error('Error adding copy listener:', e);
			}
		});
		
		const hrefElements = document.querySelectorAll('[href]');
		hrefElements.forEach(async (element) => {
			try {
				const href = element.getAttribute("href");
				if(!href || href == '#' || element.target == '_blank' || element.getAttribute('dashboard-href-new-tab') != null) return;
				
				const hrefLoaderType = element.getAttribute("dashboard-loader-type") ?? 'loader';
				
				element.addEventListener("mouseup", async (event) => {
					if(event.button == 0) {
						event.preventDefault();
						event.stopPropagation();
						getPage(href, hrefLoaderType);
					} else if(event.button == 1) {
						const openNewTab = document.createElement("a");
						openNewTab.href = href;
						openNewTab.target = "_blank";
						openNewTab.click();
					}
				});
				
				element.addEventListener("mousedown", async (event) => {
					event.preventDefault();
					event.stopPropagation();
					return false;
				});
			} catch(e) {
				console.error('Error adding href listener:', e);
			}
		});
		
		const hrefNewTabElements = document.querySelectorAll('[dashboard-href-new-tab]');
		hrefNewTabElements.forEach(async (element) => {
			try {
				const href = element.getAttribute("dashboard-href-new-tab");
				
				element.addEventListener("mouseup", async (event) => {
					if(event.button == 2) return false;
					
					const openNewTab = document.createElement("a");
					openNewTab.href = href;
					openNewTab.target = "_blank";
					openNewTab.click();
				});
				
				element.addEventListener("mousedown", async (event) => {
					event.preventDefault();
					event.stopPropagation();
					return false;
				});
			} catch(e) {
				console.error('Error adding new tab listener:', e);
			}
		});
		
		const disableElements = dashboardBody.querySelectorAll('[dashboard-disable]');
		disableElements.forEach(async (element) => {
			try {
				const isDisable = element.getAttribute("dashboard-disable");
				if(isDisable == 'true') element.disabled = true;
			} catch(e) {
				console.error('Error disabling element:', e);
			}
		});
		
		// Добавить интервалы для дат
		const dateElements = dashboardBody.querySelectorAll('[dashboard-date]');
		dateElements.forEach(async (element, index) => {
			try {
				const dateTime = element.getAttribute("dashboard-date");
				const textStyle = element.getAttribute("dashboard-full") != null ? "long" : "short";
				
				element.innerHTML = timeConverter(dateTime, textStyle);
				
				// Добавить интервал для обновления даты
				const interval = setInterval(async () => {
					try {
						element.innerHTML = timeConverter(dateTime, textStyle);
					} catch(e) {
						console.error('Error updating date:', e);
					}
				}, 1000);
				
				intervals.push(interval);
				
				element.onclick = () => {
					try {
						Toastify({
							text: timeConverter(dateTime, false),
							duration: 2000,
							position: "center",
							escapeMarkup: false,
							className: "info",
						}).showToast();
					} catch(e) {
						console.error('Error showing date toast:', e);
					}
				}
			} catch(e) {
				console.error('Error processing date element:', e);
			}
		});
		
		// Обработать музыку
		try {
			if(typeof player !== 'undefined' && player && player.isPlaying) {
				const songElements = document.querySelectorAll("[dashboard-song='" + player.isPlaying + "'] i");
				songElements.forEach((element) => {
					if(element) {
						element.classList.remove("fa-circle-play");
						element.classList.add("fa-circle-pause");
					}
				});
			}
		} catch(e) {
			console.error('Error updating player:', e);
		}
		
		const songElements = dashboardBody.querySelectorAll('[dashboard-song]');
		songElements.forEach(async (element) => {
			try {
				const songID = element.getAttribute("dashboard-song");
				const songAuthor = element.getAttribute("dashboard-author");
				const songTitle = element.getAttribute("dashboard-title");
				const songURL = element.getAttribute("dashboard-url");
				
				element.onclick = () => {
					try {
						if(typeof player !== 'undefined' && player && player.interact) {
							player.interact(songID, songAuthor, songTitle, songURL);
						}
					} catch(e) {
						console.error('Error playing song:', e);
					}
				}
			} catch(e) {
				console.error('Error adding song listener:', e);
			}
		});
		
		const timeElements = dashboardBody.querySelectorAll('[dashboard-time]');
		timeElements.forEach(async (element) => {
			try {
				const timeValue = element.getAttribute("dashboard-time");
				if(!timeValue) return;
				
				element.innerHTML = convertSeconds(timeValue);
			} catch(e) {
				console.error('Error processing time element:', e);
			}
		});
		
	} catch(e) {
		console.error('Error in updatePage:', e);
	}
}

function timeConverter(timestamp, textStyle = "short") {
	if(!textStyle) {
		const time = new Date(timestamp * 1000);
		
		const dayNumber = time.getDate();
		const day = dayNumber < 10 ? '0' + String(dayNumber) : dayNumber;
		
		const monthNumber = time.getMonth() + 1;
		const month = monthNumber < 10 ? '0' + String(monthNumber) : monthNumber;
		
		const year = time.getFullYear();
		
		const hours = time.getHours();
		
		const minutesNumber = time.getMinutes();
		const minutes = minutesNumber < 10 ? '0' + String(minutesNumber) : minutesNumber;
		
		const secondsNumber = time.getSeconds();
		const seconds = secondsNumber < 10 ? '0' + String(secondsNumber) : secondsNumber;
		
		return day + '.' + month + '.' + year + ", "+ hours + ":" + minutes + ":" + seconds;
	}
	
	const currentTime = new Date();
	var passedTime = Math.round(currentTime.getTime() / 1000) - timestamp;
	var unitType = '';
	
	switch(true) {
		case passedTime >= 31536000:
			passedTime = Math.round(passedTime / 31536000);
			unitType = 'year';
			break;
		case passedTime >= 2592000:
			passedTime = Math.round(passedTime / 2592000);	
			unitType = 'month';
			break;
		case passedTime >= 604800:
			passedTime = Math.round(passedTime / 604800);
			unitType = 'week';
			break;
		case passedTime >= 86400:
			passedTime = Math.round(passedTime / 86400);
			unitType = 'day';
			break;
		case passedTime >= 3600:
			passedTime = Math.round(passedTime / 3600);
			unitType = 'hour';
			break;
		case passedTime >= 60:
			passedTime = Math.round(passedTime / 60);
			unitType = 'minute';
			break;
		case passedTime >= 0:
			unitType = 'second';
			break;
	}
	
	const options = {
		numeric: "auto",
		style: textStyle
	}
	
	const rtf = new Intl.RelativeTimeFormat(localStorage.language.toLowerCase(), options);
	return capitalize(rtf.format(-1 * passedTime, unitType));
}

function copyElementContent(textToCopy, relativeLink = false) {
	if(relativeLink && !textToCopy.startsWith("http://") && !textToCopy.startsWith("https://")) textToCopy = baseURL.href + textToCopy;
	
	navigator.clipboard.writeText(textToCopy);
	
	Toastify({
		text: copiedText,
		duration: 2000,
		position: "center",
		escapeMarkup: false,
		className: "success",
	}).showToast();
}

function showLevelPassword() {
	const levelPasswordElement = document.querySelector("[dashboard-password]");
	
	const levelPasswordOld = levelPasswordElement.innerHTML;
	const levelPasswordNew = levelPasswordElement.getAttribute("dashboard-password");
	
	levelPasswordElement.innerHTML = levelPasswordNew;
	levelPasswordElement.setAttribute("dashboard-password", levelPasswordOld);
}

function capitalize(val) { // https://stackoverflow.com/a/1026087
    return String(val).charAt(0).toUpperCase() + String(val).slice(1);
}

function convertSeconds(time) { // https://stackoverflow.com/a/36981712
	if(time == 0 || isNaN(time)) return "0:00.000";

	time = time / 1000;

	var seconds = time % 60;
	var foo = time - seconds;
	var minutes = Math.round(foo / 60);
	
	if(seconds == 60) {
		seconds = 0;
		minutes++;
	}
	
	if(seconds < 10) seconds = "0" + seconds.toString();
	
	return minutes + ":" + seconds;
}

function downloadSong(songAuthor, songTitle, songURL) {
	fakeA = document.createElement("a");
	fakeA.href = decodeURIComponent(songURL);
	
	const urlFormatArray = fakeA.href.split(".");
	const urlFormat = urlFormatArray[urlFormatArray.length - 1] ?? "mp3";
	
	fakeA.download = songAuthor + " - " + songTitle + "." + urlFormat;
	fakeA.setAttribute("target", "_blank");
	
	fakeA.click();
}

async function favouriteSong(songID) {
	const favouriteButtonsElement = document.querySelector(`[dashboard-favourite="${songID}"]`);
	if(favouriteButtonsElement == null) return false;
	
	favouriteButtonsElement.style.opacity = "0.9";
	favouriteButtonsElement.disabled = true;
	
	const favouriteButtonIcon = favouriteButtonsElement.querySelector("i");
	const favouriteButtonText = favouriteButtonsElement.querySelector("span");
	
	if(favouriteButtonIcon.classList.contains("fa-regular")) {
		favouriteButtonIcon.classList.remove("fa-regular");
		favouriteButtonIcon.classList.add("fa-solid");
		
		favouriteButtonText.innerHTML++;
	} else {
		favouriteButtonIcon.classList.remove("fa-solid");
		favouriteButtonIcon.classList.add("fa-regular");
		
		favouriteButtonText.innerHTML--;
	}
	
	const formData = new FormData();
	formData.set("songID", songID);
	
	await postPage('manage/favouriteSong', formData, false);
	
	favouriteButtonsElement.style.opacity = "1";
	favouriteButtonsElement.disabled = false;
}

async function getForm(form) {
	if(typeof form == 'object') return form;
	
	const formElement = document.querySelector("form[name=" + form + "]");
	const formData = new FormData(formElement);
	const formEntries = formData.entries();
	var formPassed = true;
	
	for(const entry of formEntries) {
		const entryElement = entry[1];
		const entryValue = typeof entryElement == 'object' ? entryElement.name : entryElement;
		
		const formEntryElement = formElement.querySelector("input[name=" + entry[0] + "]");
		const isOptional = formEntryElement.getAttribute("dashboard-not-required");
		const regexValue = formEntryElement.getAttribute("dashboard-regex-check");
		var regexPassed = true;
		
		formEntryElement.classList.remove("regex-fail");
		
		if(regexValue != null) {
			const regexMatch = entryValue.match(new RegExp(regexValue, 'gi'));
			if(regexMatch) regexPassed = false;
		}
		
		if(!regexPassed) formEntryElement.classList.add("regex-fail");
		
		if((!entryValue.trim().length || !regexPassed) && isOptional == null) {
			formElement.classList.add("empty-fields");
			formPassed = false;
		}
	}
	
	if(!formPassed) return false;
	
	return formData;
}

async function searchSomething(url, search) {
	const searchResult = await fetch(url + "?search=" + encodeURIComponent(search)).then(req => req.json());
	
	return searchResult;
}

async function applyFilters(modalID, loaderType = 'list') {
	const formElement = document.querySelector(`form[dashboard-modal="${modalID}"]`);
	const formInputs = formElement.querySelectorAll("input"); // FormData(formElement) is bugged and skips inputs for no reason
	
	const realForm = new FormData();
	
	const arrayEntries = {};
	
	formInputs.forEach(async (input) => {
		const inputName = input.getAttribute("name");
		const inputValue = input.value;

		if(inputName == null || input.disabled || (input.type == "checkbox" && !input.checked) || !inputValue.trim().length) return;
		
		if(inputName.endsWith("[]")) {
			if(arrayEntries[inputName.slice(0, -2)] == null) arrayEntries[inputName.slice(0, -2)] = [];
					
			arrayEntries[inputName.slice(0, -2)].push(inputValue.trim());
		} else realForm.set(inputName, inputValue);
	});
	
	for(const entry of Object.entries(arrayEntries)) {
		const entryName = entry[0];
		const entryValue = entry[1].filter((value, index, self) => self.indexOf(value) === index);
		
		realForm.set(entryName, entryValue.join(','));
	}
	
	updateFilters = true;
	await getPage(window.location.pathname + "?" + new URLSearchParams(realForm).toString(), loaderType);
}

function escapeHTML(text) {
	var map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	
	return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

async function resetSettings() {
	const settingsFormElement = document.querySelector("[dashboard-change-form]");
	const settingsForm = new FormData(settingsFormElement);
	
	const defaultValuesElements = settingsFormElement.querySelectorAll("[dashboard-change-default]");
	defaultValuesElements.forEach(async(element) => {
		const inputType = element.getAttribute("type");
		
		var defaultValue = element.getAttribute("dashboard-change-default");
		if(!defaultValue.length) defaultValue = element.getAttribute("value");
		
		if((inputType != "checkbox" && inputType != "color") || inputType == 'checkbox' && ((defaultValue == false && element.checked) || (defaultValue == true && !element.checked))) element.click();
		element.value = defaultValue;
		
		element.classList.remove("regex-fail");
		
		settingsForm.set(element.name, element.value);
		
		const selectValueCheck = element.getAttribute("dashboard-select-value");
		if(selectValueCheck != null) {
			const selectElement = settingsFormElement.querySelector("[dashboard-select='" + element.name + "']:has([dashboard-select-value][dashboard-change-default]) [dashboard-select-option][value='" + element.value + "']");
			
			if(selectElement != null) selectElement.click();
		}
		
	});
	
	const selectMultipleCheck = settingsFormElement.querySelectorAll("[dashboard-select-multiple-list]");
	selectMultipleCheck.forEach((element) => {
		const searchID = element.getAttribute("dashboard-select-multiple-list");
		
		const selectBadElements = element.querySelectorAll(".option:not([dashboard-select-multiple-option])");
		selectBadElements.forEach((element) => element.remove());
		
		const selectElements = element.querySelectorAll(".option");
		
		searchLists[searchID] = [];
		
		selectElements.forEach((element) => {
			element.style = "";
			
			searchLists[searchID].push(element.getAttribute("value"));
		});
	})
	
	const selectColorCheck = settingsFormElement.querySelectorAll("input[type='color']");
	selectColorCheck.forEach((element) => element.style = `--href-shadow-color: ${element.value}61`);
	
	const saveSettingsButtonsDiv = document.querySelector("[dashboard-change-buttons]");
	if(saveSettingsButtonsDiv != null) saveSettingsButtonsDiv.classList.remove("show");
	
	const extraToggleCheck = settingsFormElement.querySelectorAll("[dashboard-extra-toggle]:has(input[dashboard-change-default])");
	extraToggleCheck.forEach((element) => {
		const inputElement = element.querySelector("input[dashboard-change-default]");
		inputElement.value = inputElement.getAttribute("dashboard-change-default");
		
		element.querySelector(`button[value="${inputElement.value}"]`).click();
	});
}

async function addEmojiToInput(emojiName) {
	const formInput = document.querySelector(`[dashboard-emoji-input]`);
	if(formInput == null) return;
	
	formInput.value += `:${emojiName}:`;
	formInput.focus();
}

async function toggleEmojisDiv() {
	const emojisDiv = document.querySelector("[dashboard-emojis-div]");
	if(emojisDiv == null) return;
	
	emojisDiv.classList.toggle("show");
}

async function getFileData(file) {
	return new Promise(async (r) => {
		const fileReader = new FileReader();
		
		fileReader.onload = () => r(fileReader.result);
		
		fileReader.onerror = async () => {
			console.error(fileReader.error);
			r(false);
		}
		
		fileReader.readAsArrayBuffer(file);
	});
}

async function handleSongUpload(form) {
	const formData = await getForm(form);
	if(!formData) return false;
	
	const songType = formData.get("songType");
	
	showLoaderProgressBar(true, uploadSongProcessingText, 0, 0, 3);
	
	if(songType == 1 || !converterAPIs.length) {
		showLoaderProgressBar(true, uploadSongUploadingText, 2, 0, 3);
		
		return postPage('upload/song', form, false).then(() => {
			showLoaderProgressBar(true, doneText, 3, 0, 3);
			setTimeout(() => showLoaderProgressBar(false), 200);
		});
	}
	
	const originalSongFile = formData.get("songFile");
	
	const fileData = await getFileData(originalSongFile);
	if(!fileData) return;
	
	const fileType = await getFileType(fileData);
	if(fileType.mime != "audio/ogg") {
		showLoaderProgressBar(true, uploadSongConvertingText, 1, 0, 3);
		
		const convertedSongFile = await getConvertedSong(fileData);
		if(typeof convertedSongFile == 'string') {
			showLoaderProgressBar(false);
			
			return showToast(errorIcon, convertedSongFile, "error");
		}
		
		formData.set("songFile", new File([convertedSongFile], "song.ogg"));
	}
	
	showLoaderProgressBar(true, uploadSongUploadingText, 2, 0, 3);
	
	return postPage('upload/song', formData, false).then(() => {
		showLoaderProgressBar(true, doneText, 3, 0, 3);
		setTimeout(() => showLoaderProgressBar(false), 200);
	});
}

async function getConvertedSong(fileBuffer) {
	return new Promise(async (r) => {
		const converterAPI = converterAPIs[random(0, converterAPIs.length - 1)];
		if(!converterAPI) return r(false);
		
		const fileRequest = await fetch(converterAPI + "/?format=ogg", {
			method: "POST",
			body: fileBuffer
		});
		
		if(!fileRequest.ok) {
			const errorMessage = await fileRequest.text();
			return r(errorMessage);
		}
		
		convertedFile = await fileRequest.arrayBuffer();
		
		return r(convertedFile);
	});
}

function random(min, max) {
	return Math.floor(Math.random() * (max - min + 1) + min);
}

async function showLoaderProgressBar(show, text = '', value = 0, min = 0, max = 100) {
	const loaderProgressElement = document.getElementById("dashboard-loader-progress");
	const progressTextElement = loaderProgressElement.querySelector("h1");
	const progressElement = loaderProgressElement.querySelector("progress");
	
	if(!show) return activateLoaderOfType(false);
	
	activateLoaderOfType("progress");
	
	progressTextElement.innerHTML = escapeHTML(text.toString());
	
	progressElement.value = value;
	progressElement.min = min;
	progressElement.max = max;
}

async function handleSFXUpload(form) {
	const formData = await getForm(form);
	if(!formData) return false;
	
	showLoaderProgressBar(true, uploadSongProcessingText, 0, 0, 3);
	
	if(!converterAPIs.length) {
		showLoaderProgressBar(true, uploadSongUploadingText, 2, 0, 3);
		
		return postPage('upload/sfx', form, false).then(() => {
			showLoaderProgressBar(true, doneText, 3, 0, 3);
			setTimeout(() => showLoaderProgressBar(false), 200);
		});
	}
	
	const originalSFXFile = formData.get("sfxFile");
	
	const fileData = await getFileData(originalSFXFile);
	if(!fileData) return;
	
	const fileType = await getFileType(fileData);
	if(fileType.mime != "audio/ogg") {
		showLoaderProgressBar(true, uploadSongConvertingText, 1, 0, 3);
		
		const convertedSFXFile = await getConvertedSong(fileData);
		if(typeof convertedSFXFile == 'string') {
			showLoaderProgressBar(false);
			
			return showToast(errorIcon, convertedSFXFile, "error");
		}
		
		formData.set("sfxFile", new File([convertedSFXFile], "sfx.ogg"));
	}
	
	showLoaderProgressBar(true, uploadSongUploadingText, 2, 0, 3);
	
	return postPage('upload/sfx', formData, false).then(() => {
		showLoaderProgressBar(true, doneText, 3, 0, 3);
		setTimeout(() => showLoaderProgressBar(false), 200);
	});
}

function checkFormSettingsChange(element) {
	if(element == null) return;
	
	const checkChangeButtons = document.querySelector("[dashboard-change-buttons]");
	const formData = new FormData(element);
	const formElements = element.querySelectorAll("input");
	var isFormChanged = false;
	
	formElements.forEach(async (element) => {
		const entryName = element.name;
		var entryValue = element.value;
		
		var defaultValue = element.getAttribute("dashboard-change-default");
		if(defaultValue != null) {
			if(!defaultValue.length) defaultValue = element.getAttribute("value");
			
			const inputType = element.getAttribute("type");
			if(inputType == 'checkbox') entryValue = element.checked;
			
			const formDataValue = formData.get(entryName);
			
			if(entryValue != defaultValue || (formDataValue != null && entryValue != formDataValue)) isFormChanged = true;
		}
	});
	
	if(isFormChanged) checkChangeButtons.classList.add("show");
	else checkChangeButtons.classList.remove("show");
}

async function downloadLevel(levelID) {
	activateLoaderOfType('loader');
	
	const request = await fetch("manage/downloadGMD?levelID=" + levelID).catch((e) => {
		console.error(e);
		
		activateLoaderOfType(false);
	});
	const result = await request.text();
		
	try {
		const resultJSON = JSON.parse(result);
		
		fakeA = document.createElement("a");
		fakeA.href = "data:text/xml;base64," + resultJSON.level.gmd;
		fakeA.download = resultJSON.level.name + ".gmd";
		fakeA.setAttribute("target", "_blank");
		
		fakeA.click();
		
		showToast(successIcon, downloadNowText, "success");
		
		activateLoaderOfType(false);
	} catch(e) {
		console.error(e);
		
		const toastBody = new DOMParser().parseFromString(result, "text/html");
		const toastElement = toastBody.getElementById("toast");
		
		showToastOutOfPage(toastElement);
		
		activateLoaderOfType(false);
	}
}

function escapeRegex(string) { // https://stackoverflow.com/a/3561711
	return string.replace(/[/\-\\^$*+?.()|[\]{}]/g, '\\$&');
}

function activateLoaderOfType(loaderType) {
	for(const element of document.querySelectorAll("[dashboard-loader]")) element.classList.add("hide");
	for(const element of document.querySelectorAll("[dashboard-modal]")) element.classList.remove("show");
	
	dashboardBody.classList.add("hide");
	toggleDropdown(null);
	
	if(!loaderType || !loaderType.length) {
		document.getElementById("dashboard-page").classList.remove("hide");
		return;
	}
	
	if(loaderType == 'loader') dashboardLoader.classList.remove("hide");
	else document.getElementById(`dashboard-loader-${loaderType}`).classList.remove("hide");
}