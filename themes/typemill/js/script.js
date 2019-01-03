var menu = document.getElementById("menu"),
	navi = document.getElementById("navigation");
				
if(menu)
{
	menu.addEventListener("click", function()
	{
		if(navi.className == "close")
		{
			navi.className = "open";
			menu.className = "active";
		}
		else
		{
			navi.className = "close";
			menu.className = "";
		}
	});
}

var shareButton = document.getElementById("share-button");
var	shareIcons = document.getElementById("share-icons");
	
if(shareButton)
{
	shareButton.addEventListener("click", function()
	{
		if(shareIcons.className == "share-icons show")
		{
			shareIcons.className = "share-icons hide";
		}
		else
		{
			shareIcons.className = "share-icons show";
		}
	});
}

var shareButtonBottom = document.getElementById("share-button-bottom");
var	shareIconsBottom = document.getElementById("share-icons-bottom");

if(shareButtonBottom)
{
	shareButtonBottom.addEventListener("click", function()
	{
		if(shareIconsBottom.className == "share-icons show")
		{
			shareIconsBottom.className = "share-icons hide";
		}
		else
		{
			shareIconsBottom.className = "share-icons show";
		}
	});
}
