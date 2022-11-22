//Default Apps Filter
function mo_oauth_server_default_clients_input_filter() {
    var input, filter, ul, li, a, i;
    var counter = 0;
    input = document.getElementById("mo_oauth_server_default_clients_input");
    filter = input.value.toUpperCase();
    ul = document.getElementById("mo_oauth_server_default_clients");
    li = ul.getElementsByTagName("li");
    for (i = 0; i < li.length; i++) {
        a = li[i].getElementsByTagName("a")[0];
        if (a.innerHTML.split('<br>')[1].toUpperCase().indexOf(filter) > -1) {
            li[i].style.display = "";
        } else {
            li[i].style.display = "none";
            counter++;
        }
        if(counter>=li.length) {
            document.getElementById("mo_oauth_server_search_res").innerHTML = "<p class='lead muted'>Oops! It looks like your OAuth client is not listed below, you can select custom client to configure the plugin. Please send us a query using support if you need any help in configuration.</p>";
            li[0].style.display = "";
            li[1].style.display = "";
        } else {
            document.getElementById("mo_oauth_server_search_res").innerHTML = "";
        }
    }
}
