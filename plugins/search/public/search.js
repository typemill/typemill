var searchField = document.getElementById("searchField");
var searchButton = document.getElementById("searchButton");

if(searchField && searchButton)
{
	var searchIndex = false;
	var documents = false;
	var holdcontent = false;
	var contentwrapper = false;

	searchField.addEventListener("focus", function(event){

		if(!searchIndex)
		{			
	        myaxios.get('/indexrs51gfe2o2')
	        .then(function (response) {

	            documents = JSON.parse(response.data);

				searchIndex = lunr(function() {
				    this.ref("id");
				    this.field("title", { boost: 10 });
				    this.field("content");
				    for (var key in documents){
				        this.add({
				            "id": documents[key].url,
				            "title": documents[key].title,
				            "content": documents[key].content
				        });
				    }
				});

	        })
	        .catch(function (error) {});			
		}
	});

	searchButton.addEventListener("click", function(event){
		event.preventDefault();

		var term = document.getElementById('searchField').value;
		var results = searchIndex.search(term);

		var resultPages = results.map(function (match) {
			return documents[match.ref];
		});

		resultsString = "<div class='resultwrapper'><h1>Result for " + term + "</h1>";
		resultsString += "<button id='closeSearchResult'>close</button>";
		resultsString += "<ul class='resultlist'>";
		resultPages.forEach(function (r) {
		    resultsString += "<li class='resultitem'>";
		    resultsString +=   "<a class='resultheader' href='" + r.url + "?q=" + term + "'><h3>" + r.title + "</h3></a>";
		    resultsString +=   "<div class='resultsnippet'>" + r.content.substring(0, 200) + " ...</div>";
		    resultsString += "</li>"
		});
		resultsString += "</ul></div>";

		if(!holdcontent)
		{
			contentwrapper = document.getElementById("searchresult").parentNode;
			holdcontent = contentwrapper.innerHTML;
		}

		contentwrapper.innerHTML = resultsString;

		document.getElementById("closeSearchResult").addEventListener("click", function(event){
			contentwrapper.innerHTML = holdcontent;
		});

	}, false);
}

/*
var searchIndex = lunr(function() {
    this.ref("id");
    this.field("title", { boost: 10 });
    this.field("content");
    for (var key in window.pages) {
        this.add({
            "id": key,
            "title": pages[key].title,
            "content": pages[key].content
        });
    }
});

function getQueryVariable(variable) {
  var query = window.location.search.substring(1);
  var vars = query.split("&");
  for (var i = 0; i < vars.length; i++) {
      var pair = vars[i].split("=");
      if (pair[0] === variable) {
          return decodeURIComponent(pair[1].replace(/\+/g, "%20"));
      }
  }
}

var searchTerm = getQueryVariable("q");
// creation of searchIndex from earlier example
var results = searchIndex.search(searchTerm);
var resultPages = results.map(function (match) {
  return pages[match.ref];
});

// resultPages from previous example
resultsString = "";
resultPages.forEach(function (r) {
    resultsString += "<li>";
    resultsString +=   "<a class='result' href='" + r.url + "?q=" + searchTerm + "'><h3>" + r.title + "</h3></a>";
    resultsString +=   "<div class='snippet'>" + r.content.substring(0, 200) + "</div>";
    resultsString += "</li>"
});
document.querySelector("#search-results").innerHTML = resultsString;
*/