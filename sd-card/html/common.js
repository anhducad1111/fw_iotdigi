
/* The UI can also be run locally, but you have to set the IP of your device accordingly.
 * And you also might have to disable CORS in your webbrowser!
 * Eg using https://chromewebstore.google.com/detail/allow-cors-access-control/lhobafahddgcelffkeicbaginigeejlf?utm_source=ext_app_menu on chrome
 * Keep empty to disable using it. Enabling it will break access through a forwared port, see */
var domainname_for_testing = "";
//var domainname_for_testing = "192.168.1.151";


/* Returns the domainname with prepended protocol.
Eg. http://iotdigi.fritz.box or http://192.168.1.5 */
function getDomainname() {
    var host = window.location.hostname;
    if (domainname_for_testing != "") {
        console.log("Using pre-defined domainname for testing: " + domainname_for_testing);
        domainname = "http://" + domainname_for_testing
    }
    else {
        domainname = window.location.protocol + "//" + host;
        if (window.location.port != "") {
            domainname = domainname + ":" + window.location.port;
        }
    }

    return domainname;
}

function UpdatePage(_dosession = true) {
    var zw = location.href;
    zw = zw.substr(0, zw.indexOf("?"));
    if (_dosession) {
        window.location = zw + '?session=' + Math.floor((Math.random() * 1000000) + 1);
    }
    else {
        window.location = zw;
    }
}


function LoadHostname() {
    _domainname = getDomainname();


    var xhttp = new XMLHttpRequest();
    xhttp.addEventListener('load', function (event) {
        if (xhttp.status >= 200 && xhttp.status < 300) {
            hostname = xhttp.responseText;
            document.title = hostname + " - AI on the edge";
            document.getElementById("id_title").innerHTML = "Digitizer - AI on the edge - " + hostname;
        }
        else {
            console.warn(request.statusText, request.responseText);
        }
    });

    //     var xhttp = new XMLHttpRequest();
    try {
        url = _domainname + '/info?type=Hostname';
        xhttp.open("GET", url, true);
        xhttp.send();

    }
    catch (error) {
        //               alert("Loading Hostname failed");
    }
}

