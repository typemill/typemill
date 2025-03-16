
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
		element.classList.add("video-container");

		var youtubePlaybutton = document.createElement("button");
		youtubePlaybutton.classList.add("play-video");
		youtubePlaybutton.value = "Play";

		element.appendChild(youtubePlaybutton);
	},

	listenToClick: function(){
		document.addEventListener('click', function (event) {

			/* listen to youtube */
			if (event.target.matches('.play-video')) {

				var youtubeID = event.target.parentNode.id;

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

			if (event.target.matches('.function-delete-img')) {

				event.preventDefault();
				event.stopPropagation();

				var imgUploadField = event.target.closest(".img-upload");
				var imgSrc = imgUploadField.getElementsByClassName("function-img-src")[0];
				imgSrc.src = '';
				var imgUrl = imgUploadField.getElementsByClassName("function-img-url")[0];
				imgUrl.value = '';

			}

		}, true);	
	},

	listenToChange: function()
	{
		document.addEventListener('change', function (changeevent) {

			/* listen to youtube */
			if (changeevent.target.matches('.function-img-file')) {

				if(changeevent.target.files.length > 0)
				{
					let imageFile = changeevent.target.files[0];
					let size = imageFile.size / 1024 / 1024;
				
					if (!imageFile.type.match('image.*'))
					{
						// publishController.errors.message = "Only images are allowed.";
					}
					else if (size > this.maxsize)
					{
						// publishController.errors.message = "The maximal size of images is " + this.maxsize + " MB";
					}
					else
					{
						let reader = new FileReader();
						reader.readAsDataURL(imageFile);
						reader.onload = function(fileevent) 
						{
							var imgUploadField = changeevent.target.closest(".img-upload");
							var imgSrc = imgUploadField.getElementsByClassName("function-img-src")[0];
							imgSrc.src = fileevent.target.result;
							var imgUrl = imgUploadField.getElementsByClassName("function-img-url")[0];
							imgUrl.value = imageFile.name;
						}
					}
				}
			}

		}, true);	
	},

	start: function(){
		this.setYoutubeItems();
		this.addYoutubePlayButtons();		
		this.listenToClick();
		this.listenToChange();
	},
};

typemillUtilities.start();