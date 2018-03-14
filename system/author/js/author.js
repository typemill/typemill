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

	/**********************************
	** 		START THEMESWITCH	 	 **
	**********************************/
	
	/* change the theme if choosen in selectbox */
	var themeSwitch		= document.getElementById("themeSwitch"),
		pluginVersions 	= document.getElementsByClassName("fc-plugin-version");
	
	
	if(themeSwitch)
	{
		getTheme(themeSwitch.value);
		getVersions(pluginVersions, themeSwitch.value);
		
		themeSwitch.addEventListener('change', function()
		{
			removeVersionBanner('theme-banner');
			getTheme(themeSwitch.value);
			getVersions(false, themeSwitch.value);
		});
	}
	
	function removeVersionBanner(bannerID)
	{
		var banner = document.getElementById(bannerID);
		if(banner)
		{
			banner.parentElement.removeChild(banner);
		}
	}

	/* use API to get theme informations from theme folder */
	function getTheme(themeName)
	{
		var getUrl 		= window.location,
			baseUrl 	= getUrl .protocol + "//" + getUrl.host + "/" + getUrl.pathname.split('/')[1],
			url 		= baseUrl+'/api/v1/themes?theme='+themeName,
			getPost 	= 'GET',
			themeImg	= document.getElementById("themePrev");

		themeImg.src = baseUrl + '/themes/' + themeName + '/' + themeName + '.jpg';
		
		sendJson(function(response)
		{
			if(response !== 'error')
			{
				var themeData 	= JSON.parse(response),
					fields		= themeData.forms.fields ? themeData.forms.fields : false,
					settings	= themeData.settings ? themeData.settings : false;
				
				/* add the theme information and the theme fields to frontend */
				addThemeInfo(themeData);
				addThemeFields(fields, settings);
			}
			else
			{
				return false;
			}
		}, getPost, url, false);
	}
	
	function addThemeInfo(themeData)
	{
		var themeVersion 	= document.getElementById('themeVersion'),
			themeLicence 	= document.getElementById('themeLicence'),
			themeAuthor 	= document.getElementById('themeAuthor'),
			themeUrl 		= document.getElementById('themeUrl');
			
		if(themeVersion && themeLicence && themeAuthor && themeUrl)
		{
			themeVersion.innerHTML 	= themeData.version;
			themeLicence.innerHTML 	= themeData.licence;
			themeAuthor.innerHTML 	= themeData.author;
			themeUrl.innerHTML 		= '<a id="themeLink" href="' + themeData.homepage + '" target="_blank">Web</a>';		
		}
	}
	
	/* add input fields for theme configurations in frontend */
	function addThemeFields(fields, settings)
	{
		var themeFields = document.getElementById('themeFields');
		themeFields.innerHTML = '';
		
		for (var fieldName in fields) 
		{
			if (fields.hasOwnProperty(fieldName)) 
			{
				var newField = document.createElement('div');
				newField.className = 'medium';
				newField.innerHTML = generateHtmlField(fieldName, fields[fieldName], settings);
				themeFields.appendChild(newField);
			}
		}
	}
	
	/* generate an input field */
	function generateHtmlField(fieldName, fieldDefinitions, settings)
	{
		var html = 	'<span class="label">' + fieldDefinitions.label + '</span>';
		
		if(fieldDefinitions.type == 'textarea')
		{
			var content = settings[fieldName] ? settings[fieldName] : '';
			var attributes = generateHtmlAttributes(fieldDefinitions);
			html += '<textarea name="themesettings['+ fieldName + ']"' + attributes + '>' + content + '</textarea>';
		}
		else if(fieldDefinitions.type == 'checkbox')
		{
			var attributes = generateHtmlAttributes(fieldDefinitions);
			
			html += '<label class="control-group">' + fieldDefinitions.description +
					  '<input type="checkbox" name="themesettings[' + fieldName + ']"'+ attributes + '>' +
					  '<span class="checkmark"></span>' +
					'</label>';
		}
		else if(fieldDefinitions.type == 'checkboxlist')
		{
			
		}
		else if(fieldDefinitions.type == 'select')
		{
			
		}
		else if(fieldDefinitions.type == 'radio')
		{
			
		}
		else
		{
			var value = settings[fieldName] ? settings[fieldName] : '';
			var attributes = generateHtmlAttributes(fieldDefinitions);
			html += '<input name="themesettings[' + fieldName + ']" type="' + fieldDefinitions.type + '" value="'+value+'"' + attributes + '>';
		}
		
		return html;
	}
	
	/* generate field attributes */
	function generateHtmlAttributes(fieldDefinitions)
	{
		var attributes 	= '',
			attr 		= getAttributes(),
			attrValues	= getAttributeValues();
		
		for(var fieldName in fieldDefinitions)
		{
			if(attr.indexOf(fieldName) > -1)
			{
				attributes += ' ' + fieldName;
			}
			if(attrValues.indexOf(fieldName) > -1)
			{
				attributes += ' ' + fieldName + '="' + fieldDefinitions[fieldName] + '"';
			}
		}
		return attributes;
	}
	
	function getAttributes()
	{	
		return ['autofocus','checked','disabled','formnovalidate','multiple','readonly','required'];
	}
	
	function getAttributeValues()
	{
		return ['id','autocomplete','placeholder','size','rows','cols','class','pattern'];
	}
	
	
	/**********************************
	** 		START VERSIONING	 	 **
	**********************************/
			
	function getVersions(plugins, theme)
	{
		var getPost 	= 'GET';
		url 			= 'http://typemill.net/api/v1/checkversion?';
		
		if(plugins)
		{
			var pluginList = '&plugins=';
			for (var i = 0, len = plugins.length; i < len; i++)
			{
				pluginList += plugins[i].id + ',';
			}
			
			url += pluginList;
		}

		if(theme)
		{
			url += '&themes=' + theme; 
		}

		sendJson(function(response)
		{
			if(response !== 'error')
			{
				var versions = JSON.parse(response);
				
				if(versions.version)
				{
					updateTypemillVersion(versions.version);
				}
				if(versions.plugins)
				{
					updatePluginVersions(versions.plugins);
				}
				if(versions.themes[theme])
				{
					updateThemeVersion(versions.themes[theme]);					
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
	
	function updateTypemillVersion(typemillVersion)
	{
		if(!document.getElementById('app-banner'))
		{
			var localTypemillVersion = document.getElementById('baseapp').dataset.version;
			if(cmpVersions(typemillVersion,localTypemillVersion) > 0)
			{
				addUpdateNotice('baseapp', 'app-banner', typemillVersion, 'http://typemill.net');
			}			
		}
	}
	
	function updateThemeVersion(themeVersion)
	{
		var localThemeVersion = document.getElementById('themeVersion').innerHTML;
		var themeUrl = document.getElementById('themeLink').href;
		if(cmpVersions(themeVersion,localThemeVersion) > 0)
		{
			addUpdateNotice('themes', 'theme-banner', themeVersion, themeUrl);
		}
	}
	
	function addUpdateNotice(elementID, bannerID, version, url)
	{
		var updateElement 	= document.getElementById(elementID);
		var banner 			= document.createElement('div');
		banner.id 			= bannerID;
		banner.className 	= 'version-banner';
		banner.innerHTML 	= '<a href="' + url + '">update to ' + version + '</a>';
		updateElement.appendChild(banner);
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
	**	PLUGINS: ACTIVATE/OPEN CLOSE	**
	*************************************/
	
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