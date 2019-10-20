
let typemillUtilities = {

	setYoutubeItems: function()
	{
		this.youtubeItems = document.querySelectorAll( ".youtube" );
	},
	addYoutubePlayButtons: function(){
		if(this.youtubeItems)
		{
			for(var i = 0; i < this.youtubeItems.length; i++)
			{
				var youtubeItem = this.youtubeItems[i];
				this.addYoutubePlayButton(youtubeItem);
			}	
		}	
	},

	addYoutubePlayButton: function(element)
	{
		console.info(element.parentNode);
		element.parentNode.classList.add("video-container");
		
		var youtubePlaybutton = document.createElement("button");
		youtubePlaybutton.classList.add("play-video");
		youtubePlaybutton.value = "Play";

		element.parentNode.appendChild(youtubePlaybutton);	
	},

	start: function(){
		this.setYoutubeItems();
		this.addYoutubePlayButtons();
		this.listenToYoutube();
	},

	listenToYoutube: function(){
		document.addEventListener('click', function (event) {

			if (event.target.matches('.play-video')) {

				var youtubeID = event.target.parentNode.getElementsByClassName('youtube')[0].id;

				event.preventDefault();
				event.stopPropagation();

				var iframe = document.createElement( "iframe" );
		
				iframe.setAttribute( "frameborder", "0" );
				iframe.setAttribute( "allowfullscreen", "" );
				iframe.setAttribute( "width", "560" );
				iframe.setAttribute( "height", "315" );
				iframe.setAttribute( "src", "https://www.youtube-nocookie.com/embed/" + youtubeID + "?rel=0&showinfo=0&autoplay=1" );
	
				var videocontainer = event.target.parentNode;
				videocontainer.innerHTML = "";
				videocontainer.appendChild( iframe );
			}
		}, true);	
	},
};