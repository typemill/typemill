	/**********************************
	** Global HttpRequest-Function   **
	** for AJAX-Requests             **
	**********************************/

	function prepareHttpRequest()
	{
		var httpRequest;
		if (window.XMLHttpRequest){ // Mozilla, Safari, ...
			httpRequest = new XMLHttpRequest();
		} 
		else if (window.ActiveXObject){ // IE
			try{
				httpRequest = new ActiveXObject("Msxml2.XMLHTTP");
			} 
			catch (e){
				try{
					httpRequest = new ActiveXObject("Microsoft.XMLHTTP");
				}
				catch (e) {}
			}
		}
		if (!httpRequest){
			alert('Giving up :( Cannot create an XMLHTTP instance');
			return false;
		}
		return httpRequest;
	}
	
	function prepareCORSRequest(method, url){
		var xhr = prepareHttpRequest();
		if ("withCredentials" in xhr)
		{
			xhr.open(method, url, true);
		}
		else if (typeof XDomainRequest != "undefined")
		{
			xhr = new XDomainRequest();
			xhr.open(method, url);
		}
		else
		{
			xhr = null;
		}
		return xhr;
	}
	
	function sendJson(callback, getPost, url, jsonData, cors)
	{
		if(cors)
		{
			var httpRequest = prepareCORSRequest(getPost, url);
		}
		else
		{
			var httpRequest = prepareHttpRequest();
			httpRequest.open(getPost, url, true);
		}
				
		httpRequest.onreadystatechange = function(e) 
		{
			if (this.readyState == 4) 
			{				
				if(httpRequest.response && callback)
				{
					callback(httpRequest.response, this.status);
				}				
			}
		};
		
		// httpRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		// httpRequest.setRequestHeader('Content-Type', 'text/plain'); 
		httpRequest.setRequestHeader('Content-Type', 'application/json'); 
		
		// required for slim
		httpRequest.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		
		if(jsonData)
		{
			httpRequest.send(JSON.stringify(jsonData));
		}
		else
		{
			httpRequest.send();
		}
	}

	var openModal 	= document.getElementById("openModal"),
		closeModal	= document.getElementById("closeModal");
		
	if(openModal && closeModal)
	{
		openModal.addEventListener("click", function(e){ e.preventDefault(); toggle("modalWindow", "show"); });
		closeModal.addEventListener("click", function(e){ e.preventDefault(); toggle("modalWindow", "show"); });
	}
	
	var mobileMenu	= document.getElementById("mobile-menu");
	
	if(mobileMenu)
	{
		mobileMenu.addEventListener("click", function(e){ toggle("sidebar-menu", "expand"); });		
	}
	
	function toggle(myid, myclass)
	{
		var toggleElement = document.getElementById(myid);
		toggleElement.classList.toggle(myclass);
	}
	
	if (window.Element && !Element.prototype.closest) {
		Element.prototype.closest =
		function(s) {
			var matches = (this.document || this.ownerDocument).querySelectorAll(s),
				i,
				el = this;
			do {
				i = matches.length;
				while (--i >= 0 && matches.item(i) !== el) {};
			} while ((i < 0) && (el = el.parentElement));
			return el;
		};
	}
	
	
	/**********************************
	** 		START VERSION CHECK	 	 **
	**********************************/

	if(document.getElementById("system"))
	{
		getVersions('system', document.getElementsByClassName("fc-system-version"));
	}
	
	if(document.getElementById("plugins"))
	{
		getVersions('plugins', document.getElementsByClassName("fc-plugin-version"));
	}
	
	if(document.getElementById("themes"))
	{
		getVersions('theme', document.getElementsByClassName("fc-theme-version"));
	}
				
	function getVersions(name, value)
	{
		var getPost 	= 'GET';
		url 			= 'https://typemill.net/api/v1/checkversion?';
		
		if(name == 'plugins')
		{
			var pluginList = '&plugins=';
			for (var i = 0, len = value.length; i < len; i++)
			{
				pluginList += value[i].id + ',';
			}
			
			url = 'https://plugins.typemill.net/api/v1/checkversion?' + pluginList;
		}

		if(name == 'theme')
		{
			var themeList = '&themes=';
			for (var i = 0, len = value.length; i < len; i++)
			{
				themeList += value[i].id + ',';
			}
			
			url = 'https://themes.typemill.net/api/v1/checkversion?' + themeList;
		}

		sendJson(function(response)
		{
			if(response !== 'error')
			{
				var versions = JSON.parse(response);
				
				if(name == 'system' && versions.system)
				{
					updateVersions(versions.system, 'system');
				}
				if(name == 'plugins' && versions.plugins)
				{
					updateVersions(versions.plugins, 'plugins');
				}
				if(name == 'theme' && versions.themes)
				{
					updateVersions(versions.themes, 'themes');					
				}
			}
			else
			{
				return false;
			}
		}, getPost, url, false, true);
	}
	
	function updateVersions(elementVersions,type)
	{
		for (var key in elementVersions)
		{
			if (elementVersions.hasOwnProperty(key))
			{
				singleElement = document.getElementById(key);
				
				if(elementVersions[key] && singleElement && cmpVersions(elementVersions[key], singleElement.innerHTML) > 0)
				{
					if(type == 'themes')
					{
						var html = '<a href="https://themes.typemill.net/' + key + '" target="blank"><span>update<br/>to '  + elementVersions[key] + '</span></a>';
					}
					else if (type == 'plugins')
					{
						var html = '<a href="https://plugins.typemill.net/' + key + '" target="blank"><span>update<br/>to '  + elementVersions[key] + '</span></a>';
					}
					else
					{
						var html = '<a href="https://typemill.net" target="blank"><span>update<br/>to '  + elementVersions[key] + '</span></a>';
					}

					singleElement.innerHTML = html;
					singleElement.classList.add("show-banner");
				}
			}
		}
	}
	
	/* credit: https://stackoverflow.com/questions/6832596/how-to-compare-software-version-number-using-js-only-number */
	function cmpVersions (a, b) 
	{
		var i, diff;
		var regExStrip0 = /(\.0+)+$/;
		var segmentsA = a.replace(regExStrip0, '').split('.');
		var segmentsB = b.replace(regExStrip0, '').split('.');
		var l = Math.min(segmentsA.length, segmentsB.length);

		for (i = 0; i < l; i++) 
		{
			diff = parseInt(segmentsA[i], 10) - parseInt(segmentsB[i], 10);
			if (diff) 
			{
				return diff;
			}
		}
		return segmentsA.length - segmentsB.length;
	}
	
	/*************************************
	** 		CARDS: ACTIVATE/OPEN CLOSE	**
	*************************************/
	
	var cards = document.getElementsByClassName("card");
	if(cards)
	{
		for (var i = 0, len = cards.length; i < len; i++)
		{
			cards[i].addEventListener("click", function(e)
			{
				if(e.target.classList.contains("fc-active"))
				{
					this.getElementsByClassName("fc-settings")[0].classList.toggle("active");
				}
				if(e.target.classList.contains("fc-settings"))
				{
					this.getElementsByClassName("cardFields")[0].classList.toggle("open");
					this.getElementsByClassName("fc-settings")[0].classList.toggle("expand");
				}
			});
		}
	}
	
	/*************************************
	**			Input Type File			**
	*************************************/

	var fileinputs = document.querySelectorAll( ".fileinput" );
						
	for (i = 0; i < fileinputs.length; ++i)
	{
		(function () {
			
			thisfileinput = fileinputs[i];

			var deletefilebutton	= thisfileinput.getElementsByClassName("deletefilebutton")[0];
			var deletefileinput		= thisfileinput.getElementsByClassName("deletefileinput")[0];
			var visiblefilename		= thisfileinput.getElementsByClassName("visiblefilename")[0];
			var hiddenfile			= thisfileinput.getElementsByClassName("hiddenfile")[0];
								
			hiddenfile.onchange = function()
			{
				visiblefilename.value = this.files[0].name;
			}

			deletefilebutton.onclick = function(event)
			{
				event.preventDefault();
				deletefileinput.value = 'delete';
				visiblefilename.value = '';
			}

		}());
	}

	/*************************************
	**			Clear Cache				**
	*************************************/

	var cachebutton = document.getElementById("clearcache");

	if(cachebutton)
	{

		cachebutton.addEventListener("click", function(event){
			
			event.preventDefault();
			
	        myaxios.delete('/api/v1/clearcache',{
	        	data: {
					'csrf_name': 	document.getElementById("csrf_name").value,
					'csrf_value':	document.getElementById("csrf_value").value,
	        	}
			})			
	        .then(function (response) {
	        	cachebutton.disabled = true;
	        	document.getElementById("cacheresult").innerHTML = "<span class='green f6'>Done!</span>";
	        })
	        .catch(function (error)
	        {
	        	document.getElementById("cacheresult").innerHTML = "<span class='red f6'>" + error.response.data.errors + "</span>";
	        });
		});
	}

	/**
	 * Element.closest() polyfill
	 * https://developer.mozilla.org/en-US/docs/Web/API/Element/closest#Polyfill
	 */
	if (!Element.prototype.closest) {
		if (!Element.prototype.matches) {
			Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
		}
		Element.prototype.closest = function (s) {
			var el = this;
			var ancestor = this;
			if (!document.documentElement.contains(el)) return null;
			do {
				if (ancestor.matches(s)) return ancestor;
				ancestor = ancestor.parentElement;
			} while (ancestor !== null);
			return null;
		};
	}