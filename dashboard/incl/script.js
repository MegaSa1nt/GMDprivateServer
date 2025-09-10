if(typeof localStorage.player_volume == "undefined") localStorage.player_volume = 0.15;

var dashboardLoader, dashboardBody, dashboardBase, dashboardBackground;
var intervals = [];
var searchLists = [];
var pageLoaders = {};
var updateFilters = true;

window.addEventListener('load', () => {
	dashboardLoader = document.getElementById("dashboard-loader");
	dashboardBody = document.getElementById("dashboard-body");
	dashboardBase = document.querySelector("base");
	dashboardBackground = document.querySelector("span.background");
	
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
	
	const pageRequest = await fetch(href);
	const response = await pageRequest.text();
	
	const updatePageDetails = await changePage(response, href, loaderType);
	
	if(updatePageDetails) activateLoaderOfType(false);
	
	return true;
}

async function postPage(href, form, loaderType = 'loader') {
	return new Promise(async (r) => {
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
		
		const pageRequest = await fetch(href, {
			method: "POST",
			body: formData
		});
		const response = await pageRequest.text();
		
		href = pageRequest.url;
		
		const updatePageDetails = await changePage(response, href, pageLoaderType);
		
		if(updatePageDetails && showLoadingCircle) activateLoaderOfType(false); // false = disable loader
		
		r(true);
	});
}

function changePage(response, href, loaderType = false) {
	return new Promise(r => {
		newPageBody = new DOMParser().parseFromString(response, "text/html");
	
		const newPage = newPageBody.getElementById("dashboard-page");
		
		if(newPage == null) {
			const toastBody = newPageBody.getElementById("toast");
			if(toastBody != null) return r(showToastOutOfPage(toastBody));
			
			Toastify({
				text: failedToLoadText,
				duration: 2000,
				position: "center",
				escapeMarkup: false,
				className: 'error',
			}).showToast();
			
			return r(true);
		}
		
		if(!href.length) href = baseURL.pathname;
		if(loaderType) history.pushState(null, null, href);
		
		const newPageScript = newPageBody.querySelector("#pageScript");
		
		document.getElementById("dashboard-page").replaceWith(newPage);
		document.querySelector("base").replaceWith(newPageBody.querySelector("base"));
		document.querySelector("title").replaceWith(newPageBody.querySelector("title"));
		document.querySelector("nav").replaceWith(newPageBody.querySelector("nav"));
		document.getElementById("dashboardScript").replaceWith(newPageBody.getElementById("dashboardScript"));
		eval(document.getElementById("dashboardScript").textContent);
		document.getElementById("dashboardStyle").replaceWith(newPageBody.getElementById("dashboardStyle"));
		
		if(newPageScript != null) {
			eval(newPageScript.textContent);
			newPageScript.remove();
		}
		
		dashboardBody = document.getElementById("dashboard-body");
		dashboardBase = document.querySelector("base");
		
		updatePage();
		updateNavbar();
		
		window.baseURL = new URL(dashboardBase.getAttribute("href"), window.location.href);
		
		return r(true);
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
	
	document.getElementById(dropdown).classList.toggle("show");
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
	for(const element of document.querySelectorAll("[dashboard-hide=true]")) element.remove();
	for(const element of document.querySelectorAll("[dashboard-show=false]")) element.remove();
	
	const navbar = document.querySelector("nav");
	navbar.addEventListener("mouseenter", () => dashboardBody.classList.remove("hide"));
	navbar.addEventListener("mouseleave", () => dashboardBody.classList.add("hide"));
	
	const removeElements = dashboardBody.querySelectorAll('[dashboard-remove]');
	removeElements.forEach(async (element) => {
		const elementsToRemove = element.getAttribute("dashboard-remove").split(" ");
		
		elementsToRemove.forEach(async (remove) => element.removeAttribute(remove));
		
		element.removeAttribute("dashboard-remove");
	});
	
	const copyElements = dashboardBody.querySelectorAll('[dashboard-copy]');
	copyElements.forEach(async (element) => {
		const textToCopy = element.innerHTML;
	
		if(!textToCopy.length) return;
			
		element.addEventListener("click", async (event) => copyElementContent(textToCopy));
	});
	
	const hrefElements = document.querySelectorAll('[href]');
	hrefElements.forEach(async (element) => {
		const href = element.getAttribute("href");
		
		element.addEventListener("mouseup", async (event) => {
			event.preventDefault();
			event.stopPropagation();
			
			switch(event.button) {
				case 0:
					const hrefLoaderType = element.getAttribute("dashboard-loader-type") ?? 'loader';
					getPage(href, hrefLoaderType);
					
					break;
				case 1:
					const openNewTab = document.createElement("a");
					openNewTab.href = href;
					openNewTab.target = "_blank";
					openNewTab.click();
					
					break;
			}
		});
		
		element.addEventListener("mousedown", async (event) => {
			event.preventDefault();
			event.stopPropagation();
			
			return false;
		});
	});
	
	const hrefNewTabElements = document.querySelectorAll('[dashboard-href-new-tab]');
	hrefNewTabElements.forEach(async (element) => {
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
	});
	
	const disableElements = dashboardBody.querySelectorAll('[dashboard-disable]');
	disableElements.forEach(async (element) => {
		const isDisable = element.getAttribute("dashboard-disable");
		
		if(isDisable == 'true') element.disabled = true;
	});
	
	intervals.forEach(async (interval) => clearInterval(interval));
	
	var index = 0;
	
	const dateElements = dashboardBody.querySelectorAll('[dashboard-date]');
	dateElements.forEach(async (element) => {
		const dateTime = element.getAttribute("dashboard-date");
		const textStyle = element.getAttribute("dashboard-full") != null ? "long" : "short";
		
		index++;
		
		element.innerHTML = timeConverter(dateTime, textStyle);
		intervals[intervals.length + index] = setInterval(async (event) => {
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
	
	if(player.isPlaying) document.querySelectorAll("[dashboard-song='" + player.isPlaying + "'] i").forEach((element) => element.classList.replace("fa-circle-play", "fa-circle-pause"));
	
	const songElements = dashboardBody.querySelectorAll('[dashboard-song]');
	songElements.forEach(async (element) => {
		const songID = element.getAttribute("dashboard-song");
		const songAuthor = element.getAttribute("dashboard-author");
		const songTitle = element.getAttribute("dashboard-title");
		const songURL = element.getAttribute("dashboard-url");
		
		element.onclick = () => player.interact(songID, songAuthor, songTitle, songURL);
	});
	
	const timeElements = dashboardBody.querySelectorAll('[dashboard-time]');
	timeElements.forEach(async (element) => {
		const timeValue = element.getAttribute("dashboard-time");
		
		element.innerHTML = convertSeconds(timeValue);
	});
	
	const checkChangeForm = document.querySelector("[dashboard-change-form]");
	if(checkChangeForm != null) checkChangeForm.oninput = async () => checkFormSettingsChange(checkChangeForm);
	
	const pageButtonsElement = document.querySelector("[dashboard-page-buttons]");
	const pageButtonsDiv = document.querySelector("[dashboard-page-div]");
	
	if(pageButtonsElement != null && pageButtonsDiv != null) {
		const pagePseudoElement = document.createElement("span");
		
		pagePseudoElement.style['min-height'] = pageButtonsElement.offsetHeight;
		pagePseudoElement.style['max-height'] = pageButtonsElement.offsetHeight;
		pageButtonsDiv.appendChild(pagePseudoElement);
	}
	
	const favouriteButtonsElements = document.querySelectorAll("[dashboard-favourite]");
	favouriteButtonsElements.forEach(async (element) => {
		const songID = element.getAttribute("dashboard-favourite");
		
		element.onclick = () => favouriteSong(songID);
	});
	
	const modalButtonElements = document.querySelectorAll("[dashboard-modal-button]");
	modalButtonElements.forEach(async (element) => {
		const modalID = element.getAttribute("dashboard-modal-button");
		const modalElement = document.querySelector(`[dashboard-modal="${modalID}"]`);
		const modalSearchElement = modalElement.querySelector("[dashboard-modal-search]");
		
		element.onclick = () => modalElement.classList.toggle("show");
		dashboardBackground.onclick = () => modalElement.classList.remove("show");
		if(modalSearchElement != null) {
			modalSearchElement.addEventListener("keyup", (event) => {
				if(event.keyCode == 13) applyFilters(modalID);
			});
		}
	});
	
	const selectSearchElements = document.querySelectorAll("[dashboard-select-search]");
	selectSearchElements.forEach(async (element) => {
		const searchID = element.getAttribute("dashboard-select-search");
		const searchToggle = document.querySelector(`[dashboard-select-show="${searchID}"]`);
		const searchValueInput = element.querySelector("[dashboard-select-value]");
		
		if(searchToggle != null) {
			searchToggle.onchange = (e) => {
				if(e.target.checked) {
					element.classList.remove("hide");
					searchValueInput.disabled = false;
				} else {
					element.classList.add("hide");
					searchValueInput.disabled = true;
				}
			}
		}
		
		const searchInput = element.querySelector("[dashboard-select-input]");
		const searchURL = searchInput.getAttribute("dashboard-select-input");
		
		element.addEventListener("focusin", () => element.classList.add("show"));
		document.addEventListener("click", (e) => {
			if(!element.contains(e.target) && element != e.target) element.classList.remove("show");
		});
		
		searchInput.oninput = async (e) => {
			const searchValue = e.target.value;
			clearTimeout(intervals[searchID]);
			
			intervals[searchID] = setTimeout(async () => {
				const searchOptions = element.querySelector("[dashboard-select-options]");
				const searchResults = await searchSomething(searchURL, searchValue);
				
				searchValueInput.value = 0;
				searchOptions.innerHTML = "";
				
				if(!searchResults.length) return;
				
				for await (const searchResult of searchResults) {
					const searchOption = document.createElement("div");
					const searchAttributes = typeof searchResult.attributes != "undefined" ? searchResult.attributes : '';
					const searchElementAfter = typeof searchResult.elementAfter != "undefined" ? searchResult.elementAfter : '';
					
					const searchText = "<text " + searchAttributes + ">" + escapeHTML(searchResult.name) + "</text>" + searchElementAfter;
					
					searchOption.classList.add("option");
					searchOption.innerHTML = searchResult.icon.length ? searchResult.icon + " " + searchText : searchText;
					
					searchOption.onclick = () => {
						searchInput.value = escapeHTML(searchResult.name);
						searchValueInput.value = searchResult.ID;
						
						element.classList.remove("show");
						
						checkFormSettingsChange(document.querySelector("[dashboard-change-form]"));
					}
					
					searchOptions.appendChild(searchOption);
				}
			}, 500);
		}
	});
	
	const selectElements = document.querySelectorAll("[dashboard-select]");
	selectElements.forEach(async (element) => {
		const selectName = element.getAttribute("dashboard-select");
		const selectInput = element.querySelector("[dashboard-select-input]");
		const selectValueInput = element.querySelector("[dashboard-select-value]");
		const selectOptions = element.querySelectorAll("[dashboard-select-option]");
		
		element.addEventListener("focusin", () => element.classList.add("show"));
		document.addEventListener("click", (e) => {
			if(!element.contains(e.target) && element != e.target) element.classList.remove("show");
		});
		
		selectOptions.forEach(async (selectOption) => {
			const selectOptionValue = selectOption.getAttribute("value");
			const selectOptionTitle = selectOption.getAttribute("dashboard-select-option");
			
			selectOption.onclick = () => {
				selectValueInput.value = selectOptionValue;
				selectInput.value = escapeHTML(selectOptionTitle);
				
				selectOptions.forEach(async (element) => element.classList.remove("hide"));
				
				if(selectName.length) {
					const selectTypeElements = document.querySelectorAll(`[dashboard-select-type="${selectName}"]`);
					
					selectTypeElements.forEach((element) => {
						const selectTypeElementValue = element.getAttribute("value");
						const selectTypeInput = element.querySelector("input");
						
						if(selectOptionValue != selectTypeElementValue) {
							element.classList.add("hide");
							if(selectTypeInput != null) selectTypeInput.disabled = true;
						} else {
							element.classList.remove("hide");
							if(selectTypeInput != null) selectTypeInput.disabled = false;
						}
					});
				}
				
				element.classList.remove("show");
				
				checkFormSettingsChange(document.querySelector("[dashboard-change-form]"));
			}
		});
		
		const selectValue = selectValueInput.getAttribute("value");
		if(selectValue != null) element.querySelector("[dashboard-select-option][value='" + selectValue + "']")?.click();
		
		selectInput.oninput = async (e) => {
			const searchValue = e.target.value.trim();
			
			selectOptions.forEach(async (selectOption) => selectOption.classList.remove("hide"));
			if(selectName.length) {
				const selectTypeElements = document.querySelectorAll(`[dashboard-select-type="${selectName}"]`);
				
				selectTypeElements.forEach((element) => {
					const selectTypeInput = element.querySelector("input");
					
					element.classList.add("hide");
					if(selectTypeInput != null) selectTypeInput.disabled = true;
				});
			}
			
			if(!searchValue.length) {
				selectValueInput.value = "";
				return;
			}
			
			const searchValueSplit = "(" + escapeRegex(searchValue).replaceAll(" ", ")(?=.*") + ")";
			const searchValueRegex = new RegExp(searchValueSplit, 'gi');
			
			selectOptions.forEach(async (selectOption) => {
				const selectOptionTitle = selectOption.getAttribute("dashboard-select-option");
				
				const selectOptionRegex = selectOptionTitle.match(searchValueRegex);
				
				if(selectOptionRegex == null) selectOption.classList.add("hide");
			});
		}
	});
	
	const filterButtonElements = document.querySelectorAll("[dashboard-filter-button]");
	filterButtonElements.forEach(async (element) => {
		const difficultyButton = element.querySelector("button");
		const difficultyInputs = element.querySelectorAll("input");
		const difficultyButtonStyle = element.getAttribute("dashboard-filter-button");
		
		difficultyButton.onclick = () => {
			const isActivate = !element.classList.contains("activated");
			
			if(isActivate) {
				element.classList.add("activated");
				difficultyInputs.forEach(element => element.disabled = false);
			} else {
				element.classList.remove("activated");
				difficultyInputs.forEach(element => element.disabled = true);
			}
			
			if(difficultyButtonStyle == "demon") {
				if(!isActivate) {
					const demonDifficulties = document.querySelectorAll('.difficultyButton.activated[dashboard-filter-button="demon"]');
					if(!demonDifficulties.length) element.parentElement.classList.remove("demon");
				} else element.parentElement.classList.add("demon");
			} 
		}
	});
	
	const modalPage = document.querySelector("[dashboard-modal]");
	if(modalPage != null && updateFilters) {
		const url = new URL(window.location.href);
		
		for(const entry of url.searchParams.entries()) {
			const entryName = entry[0];
			const entryValue = escapeHTML(decodeURIComponent(entry[1]));
		
			const input = modalPage.querySelector(`input[name="${entryName}"]`);
			if(input != null) {
				 if(input.type == 'checkbox') {
					 if(entryValue == '1') { // No, i can't move this if to if above
						input.click();
						
						const selectID = input.getAttribute("dashboard-select-show");
						if(selectID != null) {
							const selectDiv = modalPage.querySelector("[dashboard-select-search]");
							const selectValueInput = selectDiv.querySelector("[dashboard-select-value]");
							const selectValue = url.searchParams.get(selectValueInput.name);
							const selectInput = selectDiv.querySelector("[dashboard-select-input]");
							
							if(selectValue.trim().length && selectValue != '0') {
								const search = await searchSomething(selectInput.getAttribute("dashboard-select-input"), selectValue);
								
								if(search.length) {
									selectInput.value = escapeHTML(search[0].name);
									selectValueInput.value = selectValue;
								}
							}
						}
					 }
				 } else {
					 input.value = entryValue;
				 }
			} else {
				const inputs = modalPage.querySelectorAll(`input[name="${entryName}[]"]`);
				if(inputs.length) {
					const inputValues = entryValue.split(",");
					
					inputs.forEach(async (element) => {
						const inputButton = element.parentElement.querySelector("button");
						
						if(inputValues.includes(element.value) && !element.hasAttribute("dashboard-modal-skip")) inputButton.click();
					});
				}
			}
		}
	}
	if(updateFilters) updateFilters = false;
	
	const contextMenuDivs = document.querySelectorAll("[dashboard-context-div]:has([dashboard-context-menu])");
	contextMenuDivs.forEach(async (element) => {
		const contextMenuElement = element.querySelector(":scope > [dashboard-context-menu]");
		
		if(contextMenuElement == null) return;
		
		document.addEventListener('click', () => contextMenuElement.classList.remove("show"));
		element.onclick = () => contextMenuElement.classList.remove("show");
		element.oncontextmenu = async (event) => {
			event.preventDefault();
			event.stopPropagation();
			
			if(!contextMenuElement.classList.contains("show")) {
				contextMenuElement.style.left = (event.clientX + contextMenuElement.clientWidth + 50 >= window.innerWidth) ? window.innerWidth - contextMenuElement.clientWidth - 50 : event.clientX;
				contextMenuElement.style.top = (event.clientY + contextMenuElement.clientHeight + 50 >= window.innerHeight) ? window.innerHeight - contextMenuElement.clientHeight - 50 : event.clientY;
			}

			const contextMenuElements = document.querySelectorAll("[dashboard-context-menu]");
			contextMenuElements.forEach((contextMenu) => {
				if(contextMenu !== contextMenuElement) contextMenu.classList.remove("show");
			});

			contextMenuElement.classList.toggle("show");
			
			return false;
		}
	});
	
	const submitFormElements = document.querySelectorAll("form:has([dashboard-submit])");
	submitFormElements.forEach(async (element) => {
		const submitFormButton = element.querySelector("[dashboard-submit]");
		
		element.addEventListener("keyup", (event) => {
			event.preventDefault();
			event.stopPropagation();
			
			if(event.keyCode == 13) submitFormButton.click();
			
			return false;
		});
	});
	
	const fileInputElements = document.querySelectorAll("[dashboard-file-input]");
	fileInputElements.forEach(async (element) => {
		const fileInputType = element.getAttribute("dashboard-file-input");
		const fileInputText = element.querySelector("h4");
		const fileInputElement = element.querySelector("input");
		
		fileInputText.innerHTML = fileInputText.getAttribute("dashboard-file-empty");
		fileInputText.classList.add("empty");
		
		fileInputElement.onclick = () => {
			fileInputText.innerHTML = loadingText;
			fileInputText.classList.add("empty");
		}
		
		fileInputElement.onchange = async (e) => {
			const insertedFile = e.target.files[0];
			
			if(!insertedFile || insertedFile == undefined) {
				fileInputText.innerHTML = fileInputText.getAttribute("dashboard-file-empty");
				fileInputElement.value = null;
				
				return;
			}
			
			const fileName = escapeHTML(insertedFile.name);
			
			const fileData = await getFileData(insertedFile);
			if(!fileData) {
				fileInputText.innerHTML = fileInputText.getAttribute("dashboard-file-empty");
				fileInputElement.value = null;
				
				showToast(errorIcon, couldntReadFileText, "error");
				
				return;
			}
			
			if(fileData.byteLength > (fileInputType == "song" ? maxSongSize : maxSFXSize)) {
				fileInputText.innerHTML = fileInputText.getAttribute("dashboard-file-empty");
				fileInputElement.value = null;
				
				showToast(errorIcon, (fileInputType == "song" ? maxSongSizeText : maxSFXSizeText), "error");
				
				return;
			}
			
			const allowedFileTypes = ["audio/mpeg", "audio/ogg", "audio/wav", "audio/webm"];
			
			const fileType = await getFileType(fileData);
			if(!fileType || !allowedFileTypes.includes(fileType.mime)) {
				fileInputText.innerHTML = fileInputText.getAttribute("dashboard-file-empty");
				fileInputElement.value = null;
				
				showToast(errorIcon, notAnAudioText, "error");
				
				return;
			}
			
			fileInputText.innerHTML = fileName;
			fileInputText.classList.remove("empty");
		}
	});
	
	const toggleElements = document.querySelectorAll("[dashboard-toggle]");
	toggleElements.forEach(async (element) => {
		const toggleInput = element.querySelector("input");
		
		element.onclick = (event) => {
			if(event.target != toggleInput) toggleInput.click();
		}
	});
	
	const regexElements = document.querySelectorAll("[dashboard-regex-check]");
	regexElements.forEach(async (element) => {
		element.oninput = () => {
			const regexValue = element.getAttribute("dashboard-regex-check");
			var regexPassed = true;
			
			element.classList.remove("regex-fail");
			
			if(regexValue != null) {
				const regexMatch = element.value.match(new RegExp(regexValue, 'gi'));
				if(regexMatch) regexPassed = false;
			}
			
			if(!regexPassed) element.classList.add("regex-fail");
		}
	});
	
	const multipleSelectSearchElements = document.querySelectorAll("[dashboard-select-search-multiple]");
	multipleSelectSearchElements.forEach(async (element) => {
		const searchID = element.getAttribute("dashboard-select-search-multiple");
		const searchToggle = document.querySelector(`[dashboard-select-show="${searchID}"]`);
		const searchValueInput = element.querySelector("[dashboard-select-value]");
		const searchOptions = element.querySelector("[dashboard-select-options]");
		const searchListElement = document.querySelector("[dashboard-select-multiple-list='" + searchID + "']");
		searchLists[searchID] = searchValueInput.value.length ? searchValueInput.value.split(',') : [];
		
		if(searchToggle != null) {
			searchToggle.onchange = (e) => {
				if(e.target.checked) {
					element.classList.remove("hide");
					searchValueInput.disabled = false;
				} else {
					element.classList.add("hide");
					searchValueInput.disabled = true;
				}
			}
		}
		
		const searchInput = element.querySelector("[dashboard-select-input]");
		const searchURL = searchInput.getAttribute("dashboard-select-input");
		
		element.addEventListener("focusin", () => element.classList.add("show"));
		document.addEventListener("click", (e) => {
			if(!element.contains(e.target) && element != e.target) element.classList.remove("show");
		});
		
		searchInput.oninput = async (e) => {
			const searchValue = e.target.value;
			clearTimeout(intervals[searchID]);
			
			intervals[searchID] = setTimeout(async () => {
				const searchResults = await searchSomething(searchURL, searchValue);

				searchOptions.innerHTML = "";
				
				if(!searchResults.length) return;
				
				for await (const searchResult of searchResults) {
					if(searchLists[searchID].includes(searchResult.ID.toString())) continue;
					
					const searchOption = document.createElement("div");
					const searchAttributes = typeof searchResult.attributes != "undefined" ? searchResult.attributes : '';
					const searchElementAfter = typeof searchResult.elementAfter != "undefined" ? searchResult.elementAfter : '';
					
					const searchText = "<text " + searchAttributes + ">" + escapeHTML(searchResult.name) + "</text>" + searchElementAfter;
					
					searchOption.classList.add("option");
					searchOption.innerHTML = searchResult.icon.length ? searchResult.icon + " " + searchText : searchText;
					
					searchOption.setAttribute("value", searchResult.ID);
					
					function addElementToList(searchValue) {
						searchLists[searchID].push(searchValue);
						searchValueInput.value = searchLists[searchID].join(',');
						
						element.classList.remove("show");
						
						searchOption.innerHTML += `<button type="button" class="eyeButton" style="margin-left: auto;">
								<i class="fa-solid fa-xmark"></i>
							</button>`;
						searchOption.onclick = () => removeElementFromList(searchValue);
						
						searchListElement.appendChild(searchOption);
						
						checkFormSettingsChange(document.querySelector("[dashboard-change-form]"));
					}
					
					function removeElementFromList(searchValue) {
						const valueIndex = searchLists[searchID].indexOf(searchValue);
						searchLists[searchID].splice(valueIndex, 1);
						
						searchValueInput.value = searchLists[searchID].join(',');
						
						searchOption.querySelector("button").remove();
						searchOption.onclick = () => addElementToList(searchValue);
						
						searchOptions.appendChild(searchOption);
						
						checkFormSettingsChange(document.querySelector("[dashboard-change-form]"));
					}
					
					searchOption.onclick = () => addElementToList(searchResult.ID);
					
					searchOptions.appendChild(searchOption);
				}
			}, 500);
		}
		
		const searchListOptions = searchListElement.querySelectorAll(".option");
		searchListOptions.forEach(async (element) => {
			const searchValue = element.getAttribute("value");
			
			function removeElementFromList(searchValue) {
				const valueIndex = searchLists[searchID].indexOf(searchValue);
				searchLists[searchID].splice(valueIndex, 1);
					
				searchValueInput.value = searchLists[searchID].join(',');
				
				element.style.display = "none";
				
				checkFormSettingsChange(document.querySelector("[dashboard-change-form]"));
			}
			
			element.onclick = () => removeElementFromList(searchValue);
		});
	});
	
	const runningTextElements = document.querySelectorAll("[dashboard-running-text]");
	runningTextElements.forEach(async (element) => {
		const elementWidth = element.scrollWidth - 300;
		const animationDuration = elementWidth >= 30 ? elementWidth / 10 : 3;
		
		element.style = `--text-width: -${elementWidth}px; animation-duration: ${animationDuration}s;`;
	});
	
	const inputColorElements = document.querySelectorAll("input[type='color']");
	inputColorElements.forEach(async (element) => {
		element.style = `--href-shadow-color: ${element.value}61`;
		
		element.oninput = (event) => element.style = `--href-shadow-color: ${event.target.value}61`;
	});
	
	const extraToggleElements = document.querySelectorAll("[dashboard-extra-toggle]");
	extraToggleElements.forEach(async (element) => {
		const inputElement = element.querySelector("input");
		
		const buttonElements = element.querySelectorAll("button");
		var i = -1;
		buttonElements.forEach(async (buttonElement) => {
			i++;
			const buttonValue = buttonElement.getAttribute("value");
			const buttonIndex = i;
			
			buttonElement.onclick = () => {
				buttonElements.forEach(async (removeButtonStyle) => removeButtonStyle.classList.remove("checked"));
				buttonElement.classList.add("checked");
				
				inputElement.value = buttonValue;
				element.style = `--toggle-value: ${buttonIndex};`;
				
				checkFormSettingsChange(document.querySelector("[dashboard-change-form]"));
			}
		});
		
		if(inputElement.value.length) element.querySelector(`button[value="${inputElement.value}"]`).click();
	});
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
	if(relativeLink) textToCopy = baseURL.href + textToCopy;
	
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
	fakeA.download = songAuthor + " - " + songTitle + ".mp3";
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
		
		if(inputType != "checkbox" || inputType == 'checkbox' && ((defaultValue == false && element.checked) || (defaultValue == true && !element.checked))) element.click();
		element.value = defaultValue;
		
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
	
	if(!show) return loaderProgressElement.classList.add("hide");
	
	loaderProgressElement.classList.remove("hide");
	
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
	
	if(!loaderType || !loaderType.length) {
		document.getElementById("dashboard-page").classList.remove("hide");
		return;
	}
	
	if(loaderType == 'loader') dashboardLoader.classList.remove("hide");
	else document.getElementById(`dashboard-loader-${loaderType}`).classList.remove("hide");
}