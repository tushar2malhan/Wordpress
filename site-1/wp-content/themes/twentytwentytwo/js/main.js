console.log('Hye! ')
var portfolioPostsBtn = document.getElementById('portfolio-posts-btn');
var portfolioPostsContainer = document.getElementById('portfolio-posts-container');


if (portfolioPostsBtn){

    portfolioPostsBtn.addEventListener("click", function() {
        console.log('button clicked ')

	var ourRequest = new XMLHttpRequest(); 
    ourRequest.open('GET', 'http://localhost:8000/wp-json/wp/v2/posts'); // wpApiSettings.siteUrl is a global variable that is available in the browser -> http://localhost:8000/
    
    ourRequest.onload = function() {
        if (ourRequest.status >= 200 && ourRequest.status < 400) {
            var Data = JSON.parse(ourRequest.responseText);
            console.log(Data);
            createHTML(Data);
        } else {
            console.log("We connected to the server, but it returned an error.");
        }
    }
    ourRequest.onerror = function() {
        console.log("Connection error");
    }
    ourRequest.send();

    });
}

function createHTML(Data) {
    var ourHTMLString = '';
    for (i = 0; i < Data.length; i++) {
        ourHTMLString += '<h2>' + Data[i].title.rendered + '</h2>';
        ourHTMLString += Data[i].content.rendered;
    }
    portfolioPostsContainer.innerHTML = ourHTMLString;
}



// ajax request to send the post request from the server

var quickAddbButton = document.getElementById("quick-add-button");
if (quickAddbButton) {
    // console.log(1,document.querySelector('.admin-quick-add [name="title"]'))
    quickAddbButton.addEventListener("click", function() {
       
        var ourPostData = {
            // get document from id content
            "title": document.querySelector('.admin-quick-add [name="title"]').value, 
            "status": "publish"
        }

        console.log(2,'Button clicked',ourPostData);
        var createPost = new XMLHttpRequest();
        createPost.open("POST", "http://localhost:8000/wp-json/wp/v2/posts");  // http://localhost:8000/ is the default url made in functions.php
        createPost.setRequestHeader("X-WP-Nonce", wpApiSettings.nonce);
        createPost.setRequestHeader("Content-Type",'application/json;charset=UTF-8');
        createPost.send(JSON.stringify(ourPostData));
        console.log('Posts Added Successfully')

        document.querySelector('.admin-quick-add [name="title"]').value = '';
  
    });
}
    
// alert("hostname is "+wpApiSettings.siteUrl)
console.log("hostname is ",wpApiSettings.siteUrl)