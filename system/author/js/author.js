(function () 
{
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
	
	function sendJson(callback, getPost, url, jsonData, cors = false)
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
				if(this.status == 200)
				{
					if(httpRequest.responseText && callback)
					{
						callback(httpRequest.response);
					}
				}
				else
				{
					console.log('connection error, status '+this.status);
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
			
			url += pluginList;
		}

		if(name == 'theme')
		{
			var themeList = '&themes=';
			for (var i = 0, len = value.length; i < len; i++)
			{
				themeList += value[i].id + ',';
			}
			
			url += themeList;
		}

		sendJson(function(response)
		{
			if(response !== 'error')
			{
				var versions = JSON.parse(response);
				
				if(name == 'system' && versions.system)
				{
					updateVersions(versions.system);
				}
				if(name == 'plugins' && versions.plugins)
				{
					updateVersions(versions.plugins);
				}
				if(name == 'theme' && versions.themes)
				{
					updateVersions(versions.themes);					
				}
			}
			else
			{
				return false;
			}
		}, getPost, url, false, true);
	}
	
	function updateVersions(elementVersions)
	{
		for (var key in elementVersions)
		{
			if (elementVersions.hasOwnProperty(key))
			{
				singleElement = document.getElementById(key);
				
				if(elementVersions[key] && singleElement && cmpVersions(elementVersions[key], singleElement.innerHTML) > 0)
				{
					singleElement.innerHTML = "<span>update<br/>to " + elementVersions[key] + "</span>";
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
	**			COLOR PICKER			**
	*************************************/
	
    var target = document.querySelectorAll('input[type=color]');
    // set hooks for each target element
    for (var i = 0, len = target.length; i < len; ++i)
	{
		var thisTarget = target[i];
		
		(function(thisTarget){
			
			/* hide the input field and show color box instead */
			var box = document.createElement('div');

			box.className = 'color-box';
			box.style.backgroundColor = thisTarget.value;
			box.setAttribute('data-color', thisTarget.value);
			thisTarget.parentNode.insertBefore(box, thisTarget);
			thisTarget.type = 'hidden';

			var picker = new CP(box),
				code = document.createElement('input');
						
			picker.target.onclick = function(e)
			{
				e.preventDefault();
			};
			
			code.className = 'color-code';
			code.pattern = '^#[A-Fa-f0-9]{6}$';
			code.type = 'text';
			
			picker.on("enter", function() {
				code.value = '#' + CP._HSV2HEX(this.get());
			});	


			picker.on("change", function(color) {
				thisTarget.value = '#' + color;
				this.target.style.backgroundColor = '#' + color;
				code.value = '#' + color;
			});
			
			picker.picker.firstChild.appendChild(code);

			function update() {
				if (this.value.length) {
					picker.set(this.value);
					picker.trigger("change", [this.value.slice(1)]);
				}
			}

			code.oncut = update;
			code.onpaste = update;
			code.onkeyup = update;
			code.oninput = update;
			
			
		})(thisTarget);		
    }	
})();