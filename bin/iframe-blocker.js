/*! iFrame Blocker v1.2 | (c) repaste GmbH | https://repaste.de */
/*
Datenschutzerklärung:
360 Grad Rundgänge

Diese Seite läd 360 Grad Rundgänge, welche von %COMPANY% bereitgestellt werden.
Beim Aufruf einer Seite mit 360 Grad Rundgängen, werden Sie vor die Wahl gestellt den Rundgang anzuzeigen oder nicht.
Diese Seite nutzt zur Darstellung von 360 Grad Rundgängen eine externe Webseite von %COMPANY_NAME%. Anbieter ist
die %COMPANY_NAME%, %COMPANY_ADDRESS%.
Zu diesem Zweck muss der von Ihnen verwendete Browser Verbindung zu
den Servern von %COMPANY_NAME% aufnehmen. Hierdurch erlangt %COMPANY_NAME% Kenntnis darüber, dass über
Ihre IP-Adresse diese Website aufgerufen wurde. Sofern eine entsprechende Einwilligung abgefragt wurde, erfolgt die
Verarbeitung ausschließlich auf Grundlage von Art. 6 Abs. 1 lit. a DSGVO und § 25 Abs. 1 TTDSG, soweit die
Einwilligung die Speicherung von Cookies oder den Zugriff auf Informationen im Endgerät des Nutzers (z. B.
Device-Fingerprinting) im Sinne des TTDSG umfasst.
Des Weiteren speichert diese Webseite ein Cookie in Ihrem Browser, um Ihnen die erteilten Einwilligungen bzw. deren Widerruf zuordnen zu können.
Die so erfassten Daten werden gespeichert, bis Sie uns zur Löschung auffordern, das Cookie selbst löschen oder der Zweck für die Datenspeicherung entfällt. Zwingende gesetzliche Aufbewahrungspflichten bleiben unberührt.
Die Einwilligung ist jederzeit widerrufbar.
Weitere Informationen zu den 360 Grad Rundgängen finden Sie und in der Datenschutzerklärung von %COMPANY_NAMAE%
unter: %COMPANY_PRIVACY_POLICY%.









Zu diesem Zweck muss der von Ihnen verwendete Browser Verbindung zu den Servern von Google
aufnehmen. Hierdurch erlangt Google Kenntnis darüber, dass über Ihre IP-Adresse diese Website
aufgerufen wurde. Die Nutzung von Google WebFonts erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO.
Der Websitebetreiber hat ein berechtigtes Interesse an der einheitlichen Darstellung des Schriftbildes auf
seiner Website. Sofern eine entsprechende Einwilligung abgefragt wurde, erfolgt die Verarbeitung
ausschließlich auf Grundlage von Art. 6 Abs. 1 lit. a DSGVO und § 25 Abs. 1 TTDSG, soweit die Einwilligung
die Speicherung von Cookies oder den Zugriff auf Informationen im Endgerät des Nutzers (z. B. Device-
Fingerprinting) im Sinne des TTDSG umfasst. Die Einwilligung ist jederzeit widerrufbar.
Wenn Ihr Browser Web Fonts nicht unterstützt, wird eine Standardschrift von Ihrem Computer genutzt.
Weitere Informationen zu Google Web Fonts finden Sie unter
https://developers.google.com/fonts/faq und in der Datenschutzerklärung von Google:
https://policies.google.com/privacy?hl=de.


 */
function deleteCookie(key){
    document.cookie = window.location.hostname + '/' + key + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; Secure; SameSite=Strict;";
}
function setCookie(key, value){
    const d = new Date();
    let days = 10;
    d.setTime(d.getTime() + (days*24*60*60*1000));
    document.cookie = window.location.hostname + '/' + key + "=" + value + "; expires=" + d.toUTCString() + "; path=/; Secure; SameSite=Strict;";
}
function getCookie(key) {
    let name = window.location.hostname + '/' + key + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for(let i = 0; i <ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    return null;
}

let thumbnails = '';
let loader = document.currentScript;

function init(){
    let privacy_policy = loader.getAttribute('data-privacy-policy');
    document.querySelectorAll('[data-iframe-blocker]').forEach(function (iframe){
        let consent_id = iframe.getAttribute('data-iframe-blocker');
        let cookie = getCookie(consent_id);
        let container = iframe.parentElement;
        let thumbnail = iframe.getAttribute('data-thumbnail');
        let title = 'Wir brauchen Ihr Einverständnis!';
        if(iframe.getAttribute('data-iframe-title') !== null)
            title = iframe.getAttribute('data-iframe-title');
        let description = 'Wir verwenden Drittanbieter, um diese Seite einzubinden. Diese können persönliche Daten über Ihre Aktivitäten sammeln. Bitte beachten Sie die Details und geben Sie Ihre Einstimmung.';
        if(iframe.getAttribute('data-iframe-description'))
            description = iframe.getAttribute('data-iframe-description');

        thumbnails += `[data-iframe-blocker="${consent_id}"] ~ .iframe-consent-container .iframe-consent-wrapper:before{background: url(${thumbnail}) no-repeat;background-size: cover;}`;

        let overlay_element = document.createElement('div');
        overlay_element.className = 'iframe-consent-container';
        let more_information_buttons = '';
        if(privacy_policy !== null){
            more_information_buttons += '<div class="button-container"><button class="secondary" onclick="window.open(\'' + privacy_policy + '\',\'_blank\')">Mehr Informationen</button></div>';
            more_information_buttons += '<div class="button-container"><button class="primary iframe-consent-accept">Akzeptieren</button></div>';
        }else{
            more_information_buttons += '<button class="primary iframe-consent-accept">Akzeptieren</button>';
        }
        overlay_element.innerHTML = `
<div class="iframe-consent-wrapper" style="display: none;">
    <div class="iframe-consent-content">
        <div class="iframe-consent-icon">
            <svg viewBox="0 0 28 28" width="20%" xmlns="http://www.w3.org/2000/svg"><g><path d="m10.042 13.5h-2.042a.5.5 0 0 0 0 1h2.042a1.157 1.157 0 0 0 1.158-1.155v-4.084a1.157 1.157 0 0 0 -1.155-1.156h-2.045a.5.5 0 0 0 0 1h2.042a.156.156 0 0 1 .155.156v1.539h-2.197a.5.5 0 0 0 0 1h2.2v1.542a.155.155 0 0 1 -.158.158z"/><path d="m15.918 13.345v-1.387a1.157 1.157 0 0 0 -1.155-1.158h-1.543v-1.539a.156.156 0 0 1 .156-.156h2.042a.5.5 0 0 0 0-1h-2.042a1.158 1.158 0 0 0 -1.156 1.156v4.084a1.158 1.158 0 0 0 1.156 1.155h1.387a1.157 1.157 0 0 0 1.155-1.155zm-1 0a.155.155 0 0 1 -.155.155h-1.387a.155.155 0 0 1 -.156-.155v-1.545h1.543a.155.155 0 0 1 .155.155z"/><path d="m18.413 14.5h.753a1.473 1.473 0 0 0 1.473-1.472v-3.451a1.474 1.474 0 0 0 -1.473-1.472h-.753a1.475 1.475 0 0 0 -1.473 1.472v3.451a1.474 1.474 0 0 0 1.473 1.472zm-.473-4.923a.473.473 0 0 1 .473-.472h.753a.473.473 0 0 1 .473.472v3.451a.473.473 0 0 1 -.473.472h-.753a.473.473 0 0 1 -.473-.472z"/><path d="m22.145 8.824a1.356 1.356 0 1 0 -1.355-1.355 1.357 1.357 0 0 0 1.355 1.355zm0-1.711a.356.356 0 1 1 -.355.356.355.355 0 0 1 .355-.356z"/><path d="m12.644 17.228a.5.5 0 1 0 -.707.707l.835.834c-6.064-.183-10.272-1.702-10.272-2.979 0-.465.7-1.192 2.657-1.84a.5.5 0 1 0 -.314-.95c-2.187.724-3.343 1.688-3.343 2.79 0 2.523 5.829 3.825 11.311 3.982l-.874.874a.5.5 0 0 0 .707.708l1.71-1.71a.5.5 0 0 0 0-.707z"/><path d="m23.157 13a.5.5 0 1 0 -.314.949c1.957.651 2.657 1.376 2.657 1.841 0 1.2-3.642 2.664-9.524 2.953a.5.5 0 0 0 .024 1h.024c5.214-.257 10.476-1.578 10.476-3.953 0-1.102-1.156-2.066-3.343-2.79z"/></g></svg>
        </div>
        <h3>${title}</h3>
        <p>${description}</p>
        <div class="iframe-content-buttons">
            ${more_information_buttons}
        </div>
    </div>
</div>
<div class="iframe-consent-footer" style="display: none;">
  <p><a class="iframe-consent-revoke">Einwilligung wiederrufen</a></p>
</div>
`;

        container.appendChild(overlay_element);
        if(cookie !== null && cookie === 'true'){
            iframe.setAttribute('src', iframe.getAttribute('data-src'));
            container.querySelector('iframe[data-iframe-blocker="' + consent_id + '"] ~ .iframe-consent-container .iframe-consent-footer').style.display = 'block';
        }else
            container.querySelector('iframe[data-iframe-blocker="' + consent_id + '"] ~ .iframe-consent-container .iframe-consent-wrapper').style.display = 'flex';

        container.querySelector('.iframe-consent-accept').addEventListener('click', function(){
            iframe.setAttribute('src', iframe.getAttribute('data-src'));
            setCookie(consent_id, 'true');
            container.querySelector('iframe[data-iframe-blocker="' + consent_id + '"] ~ .iframe-consent-container .iframe-consent-wrapper').style.display = 'none';
            container.querySelector('iframe[data-iframe-blocker="' + consent_id + '"] ~ .iframe-consent-container .iframe-consent-footer').style.display = 'block';
        });
        container.querySelector('.iframe-consent-revoke').addEventListener('click', function(){
            iframe.setAttribute('src', iframe.getAttribute('data-src'));
            deleteCookie(consent_id);
            container.querySelector('iframe[data-iframe-blocker="' + consent_id + '"] ~ .iframe-consent-container .iframe-consent-wrapper').style.display = 'flex';
            container.querySelector('iframe[data-iframe-blocker="' + consent_id + '"] ~ .iframe-consent-container .iframe-consent-footer').style.display = 'none';
            iframe.setAttribute('src', '');
        });
    });
}

init();

let font_family = 'inherit';
if(loader.getAttribute('data-font-family') !== null){
    font_family = loader.getAttribute('data-font-family');
}
let button_more_information = 'red';
if(loader.getAttribute('data-more-information-color') !== null){
    button_more_information = loader.getAttribute('data-more-information-color');
}
let button_accept = 'green';
if(loader.getAttribute('data-accept-color') !== null){
    button_accept = loader.getAttribute('data-accept-color');
}

let style_element = document.createElement('style');
style_element.innerHTML = `
:root{
    --iframe-consent-wrapper-font: ${font_family};
}
.iframe-consent-container .iframe-consent-wrapper:before{
    content: "";
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
    left: 0;
    top: 0;
    z-index: -1;
}
${thumbnails}
.iframe-consent-container{
    z-index: 50;
    pointer-events: none;
    position: absolute;
    top: 0;
    height: 100%;
    width: 100%;
}
.iframe-consent-container p, .iframe-consent-container a, .iframe-consent-container button, .iframe-consent-container h3{
    font-family: var(--iframe-consent-font-family);
}
.iframe-consent-container .iframe-consent-wrapper{
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100%;
    /*left: 50%;
    transform: translate(-50%, -50%);
    top: 50%;*/
    pointer-events: all;
}
.iframe-consent-container .iframe-consent-content{
    padding: 10px;
    width: clamp(250px,70%,400px);
    /*height: min(200px, 40%);*/
    border: 1px solid black;
    border-radius: 10px;
    background-color: #fff;
    text-align: center;
}
.iframe-consent-container .iframe-content-buttons{
    display: flex;
    flex-wrap: wrap;
}
.iframe-consent-container .iframe-content-buttons button{
    display: inline-block;
    line-height: 1;
    text-align: center;
    border: 1px solid transparent;
    padding: 0.786rem 1.5rem;
    font-size: 1rem;
    border-radius: 0.358rem;
    cursor: pointer;
    width: 100%;
    color: #fff;
}
.iframe-consent-container .iframe-content-buttons button.secondary{
    border-color: var(--iframe-consent-more-information-color, ${button_more_information});
    background-color: transparent;
    color: var(--iframe-consent-more-information-color, ${button_more_information});
}
.iframe-consent-container .iframe-content-buttons button.primary{
    background-color: var(--iframe-consent-accept-color, ${button_accept});
    background-size: cover;
}
.iframe-consent-container .iframe-consent-wrapper .iframe-consent-icon{
    display: flex;
    justify-content: center;
}
.iframe-consent-container .iframe-consent-wrapper h3{
    margin-top: 0;
    color: black;
    font-size: 1.4em;
    font-weight: 500;
    text-align: center;
    margin-bottom: 10px;
}
.iframe-consent-container .iframe-consent-wrapper p{
    margin-top: 0;
    color: #333;
    margin-bottom: 30px;
}
.iframe-consent-container .iframe-consent-footer{
    position: absolute;
    bottom: 5px;
    left: 10px;
    width: 200px;
    text-align: left;
    pointer-events: all;
}
.iframe-consent-container .iframe-consent-footer p{
    color: #fff;
    padding: 0;
    margin: 0;
}
.iframe-consent-container .iframe-consent-footer a{
    text-decoration: underline;
    color: #000;
}
.iframe-consent-container .iframe-consent-footer a:hover{
    text-decoration: underline;
}
.dark-mode .iframe-consent-container .iframe-consent-footer a{
    color: #fff;
}
.iframe-consent-container .iframe-consent-revoke{
    cursor: pointer;
}
.iframe-consent-container .iframe-content-buttons .button-container{
    display: flex;
}
@media (min-width: 711px){
    .iframe-consent-container .iframe-content-buttons .button-container{
        -ms-flex: 0 0 50%;
        flex: 0 0 50%;
        width: 50%;
    }
    .iframe-consent-container .iframe-content-buttons .button-container:first-of-type button{
        margin-right: 5px;
    }
    .iframe-consent-container .iframe-content-buttons .button-container:last-of-type button{
        margin-left: 5px;
    }
}
@media (max-width: 710px){
    .iframe-consent-container .iframe-content-buttons .button-container:first-of-type{
        margin-bottom: 10px;
    }
    .iframe-consent-container .iframe-content-buttons .button-container{
        -ms-flex: 0 0 100%;
        flex: 0 0 100%;
        width: 100%;
    }
}
`;
document.querySelector('head').appendChild(style_element);