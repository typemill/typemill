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

		// if you use application/json, make sure you collect the data in php 
		// with file_get_contents('php://input') instead of $_POST

		// httpRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		// httpRequest.setRequestHeader('Content-Type', 'text/plain'); 
		httpRequest.setRequestHeader('Content-Type', 'application/json'); 
		
		// required by slim ???
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
	
	/* change the theme if choosen in selectbox */
	var themeSwitch	= document.getElementById("themeSwitch");
	if(themeSwitch)
	{
		var themePrev	= document.getElementById("themePrev"),
			themePath	= themePrev.src.split("themes")[0];
			
		themeSwitch.addEventListener('change', function()
		{
			themePrev.src = themePath + 'themes/' + themeSwitch.value + '/' + themeSwitch.value + '-large.jpg';
		});
		
	}

	var pluginVersions = document.getElementsByClassName("fc-plugin-version");
	if(pluginVersions)
	{
		var query = 'plugins=';
		for (var i = 0, len = pluginVersions.length; i < len; i++)
		{
			query += pluginVersions[i].id + ',';
		}
		
		var getPost 	= 'GET';
		url 			= 'http://typemill.net/api/v1/checkversion?' + query;

		sendJson(function(response)
		{
			if(response !== 'error')
			{
				var versions = JSON.parse(response);
				if(versions.plugins)
				{
					updatePluginVersions(versions.plugins);
				}
			}
			else
			{
				return false;
			}
		}, getPost, url, false, true);
	}

	function updatePluginVersions(pluginVersions)
	{		
		for (var key in pluginVersions)
		{
			if (pluginVersions.hasOwnProperty(key))
			{
				pluginElement = document.getElementById(key);
				if(pluginVersions[key] && pluginElement && cmpVersions(pluginVersions[key], pluginElement.innerHTML) > 0)
				{
					pluginElement.innerHTML = "update to " + pluginVersions[key];
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
	
	/* activate/deactivate plugin and open/close settings */
	var plugins = document.getElementsByClassName("plugin");
	if(plugins)
	{
		for (var i = 0, len = plugins.length; i < len; i++)
		{
			plugins[i].addEventListener("click", function(e)
			{
				if(e.target.classList.contains("fc-active"))
				{
					this.getElementsByClassName("fc-settings")[0].classList.toggle("active");
				}
				if(e.target.classList.contains("fc-settings"))
				{
					this.getElementsByClassName("pluginFields")[0].classList.toggle("open");
				}
			});
		}
	}
	
	/* add color picker for color fields */
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