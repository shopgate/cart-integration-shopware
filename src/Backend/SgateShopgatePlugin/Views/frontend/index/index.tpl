{extends file="parent:frontend/index/index.tpl"}

{block name='frontend_index_html'}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {else}
        <html class="no-js" lang="{s name='IndexXmlLang'}{/s}" itemscope="itemscope" itemtype="http://schema.org/WebPage" data-disallow-pull-to-refresh="1">
    {/if}
{/block}

{block name='frontend_index_navigation'}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {/if}
{/block}
{block name='frontend_index_left_last_articles'}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {/if}
{/block}
{block name="frontend_index_footer"}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {/if}
{/block}
{block name="frontend_index_page_wrap"}
    {$smarty.block.parent}
    {block name="frontend_index_shopgate_script"}
        {if $sgWebCheckout}
            {if $promotionsUsedTooOften }
                {$sgPromotionVouchers = ''}
            {/if}
            <script type="text/javascript">
                {literal}
                !function(){if(!(/iPad|iPhone|iPod/.test(navigator.userAgent)&&!window.MSStream)){document.getElementsByTagName("BODY")[0].setAttribute("style","margin-top:48px;position:relative;");var e=".js--modal{margin:0;padding-bottom:1.75rem;top:46px!important;}.is--act-confirm .js--modal.sizing--auto.is--fullscreen{height: calc(100% - 48px)!important;}",n=document.head||document.getElementsByTagName("head")[0];(t=document.createElement("style")).type="text/css",t.appendChild(document.createTextNode(e)),n.appendChild(t)}var t;e='.js--modal a{pointer-events: none;}.js--modal a[data-address-editor="true"]{pointer-events: auto;}',n=document.head||document.getElementsByTagName("head")[0];(t=document.createElement("style")).type="text/css",t.appendChild(document.createTextNode(e)),n.appendChild(t)}(),function(){function i(e){return"string"==typeof e&&""!==e?"sgCodeCache: "+e:""}function e(){return!!window.SGJavascriptBridge&&((e=document.createElement("meta")).setAttribute("name","viewport"),e.setAttribute("content","user-scalable=no, width=device-width"),document.getElementsByTagName("head").item(0).appendChild(e),"function"==typeof initPipelineCall&&initPipelineCall(),setTimeout(function(){window.SGAppConnector.closeLoadingSpinner()},3e3),!0);var e}document.addEventListener("DOMContentLoaded",function(){if(function(){var e="libshopgate";if(document.getElementById(e))return;var n=document.createElement("meta");n.setAttribute("id",e),n.src=e,document.getElementsByTagName("head").item(0).appendChild(n)}(),e())return!0;!function(e,n,t){var i=Date.now();if(t())return;var o=setInterval(function(){i+n<=Date.now()?clearInterval(o):t()&&clearInterval(o)},e)}(40,3e3,e)}),window.SGAppConnector={pipelineResponseHandler:{},functionExists:function(e){return"function"==typeof e},getRandomPassPhrase:function(e){return e||(e=16),new Array(e).fill("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!Â§$%&/()=?ÃŸ+*~#'-_.:,;<>|{[]}^Â°").map(function(e){return e[Math.floor(Math.random()*e.length)]}).join("")},getParameterByName:function(e,n){n||(n=window.location.href),e=e.replace(/[\[\]]/g,"\\$&");var t=new RegExp("[?&]"+e+"(=([^&#]*)|&|#|$)").exec(n);return t?t[2]?decodeURIComponent(t[2].replace(/\+/g," ")):"":null},sendAppCommands:function(e){var n="12.0";"dispatchCommandsForVersion"in window.SGJavascriptBridge?window.SGJavascriptBridge.dispatchCommandsForVersion(e,n):window.SGJavascriptBridge.dispatchCommandsStringForVersion(JSON.stringify(e),n)},sendAppCommand:function(e){this.sendAppCommands([e])},closeLoadingSpinner:function(){this.sendAppCommand({c:"onload"})},sendPipelineRequest:function(i,e,n,o,t){n||(n={}),t||(t=null);var r={c:"sendPipelineRequest",p:{serial:i,name:i,input:n}};e&&(r.p.type="trusted"),this.pipelineResponseHandler[i]={callbackParams:t,__call:function(e,n,t){if(window.SGAppConnector.functionExists(o))return console.log("## running response callback for pipeline call: "+i),o(e,n,t);console.log("## no callback registered for pipeline call: "+i)}},this.sendAppCommand(r)},includeScript:function(e,n){n||(e="(function () {"+e+";})();");var t=document.createElement("script");t.setAttribute("type","text/javascript"),t.appendChild(document.createTextNode(e)),document.getElementsByTagName("head").item(0).appendChild(t)},saveScriptToCache:function(e,n){var t=i(e);t&&"string"==typeof n&&window.localStorage.setItem(t,btoa(n))},getScriptFromCache:function(e){var n=i(e);if(n){var t=window.localStorage.getItem(n);return"string"==typeof t?atob(t):null}return null},loadRemoteScript:function(e,n){var t=this.getScriptFromCache(e);if(t)this.includeScript(t,n);else{var i=new XMLHttpRequest;i.open("GET",e),i.onreadystatechange=function(){window.SGAppConnector.saveScriptToCache(e,i.responseText),window.SGAppConnector.includeScript(i.responseText,n)},i.send()}},loadPipelineScript:function(e,o){var n=this.getScriptFromCache(e);if(n)return this.includeScript(n),void(this.functionExists(window.SGPipelineScript[e])&&(console.log("## -> calling: SGPipelineScript."+e+"("+JSON.stringify(o)+")"),window.SGPipelineScript[e](o)));this.sendPipelineRequest("getScript_v1",!1,{scriptName:e},function(e,n,t){console.log("## -> including pipeline script: "+t.scriptName+".js");var i=atob(n.scriptCode);window.SGAppConnector.saveScriptToCache(t.scriptName,i),window.SGAppConnector.includeScript(i),window.SGAppConnector.functionExists(window.SGPipelineScript[t.scriptName])&&(console.log("## -> calling: SGPipelineScript."+t.scriptName+"("+JSON.stringify(t.passthroughParams)+")"),window.SGPipelineScript[t.scriptName](o))},{scriptName:e,passthroughParams:o,this:this})}},window.SGPipelineScript={},window.SGEvent={__call:function(e,n){console.log("# Received event "+e),n&&Array.isArray(n)||(n=[]),SGEvent[e]&&SGEvent[e].apply(SGEvent,n)},pipelineResponse:function(e,n,t){if(e&&console.error("Called pipeline '"+n+"' resulted in an error: "+JSON.stringify(e)),window.SGAppConnector.pipelineResponseHandler[n]){var i=window.SGAppConnector.pipelineResponseHandler[n];return i.__call(e,t,i.callbackParams)}},isDocumentReady:function(){return!0}},window.SGAppConnector.functionExists(String.prototype.endsWith)&&(String.prototype.endsWith=function(e){return-1!==this.indexOf(e,this.length-e.length)})}();
                {/literal}
            </script>
            <style type="text/css">
                .is--act-cart {ldelim}display: none{rdelim}
                {$sgCustomCss}
            </style>
            {if $sgSessionId || $sgActionName === 'confirm' || $sgActionName === 'shippingPayment' ||  $sgActionName === 'cart'}
                <script type="text/javascript">
                    function initPipelineCall () {ldelim}
                        window.SGAppConnector.sendPipelineRequest(
                            'onedot.checkout.updateSession.v1',
                            false,
                            {ldelim}'sessionId': '{$sgSessionId}', 'promotionVouchers': '{$sgPromotionVouchers}'{rdelim},
                            function (err, serial, output) {ldelim}

                                // Update the cart in the background
                                var commands = [
                                    {ldelim}
                                        'c': 'broadcastEvent',
                                        'p': {ldelim}
                                            'event': 'fetchCartAfterSessionUpdate'
                                        {rdelim}
                                    {rdelim},
                                ]
                                window.SGAppConnector.sendAppCommands(commands);

                                {if $sgActionName === 'shippingPayment'}
                                    {if $sgIsNewCustomer && $sgEmail && $sgHash }
                                        window.SGAppConnector.sendPipelineRequest(
                                            'shopgate.user.loginUser.v1',
                                            true,
                                            {ldelim}'strategy': 'auth_code', 'parameters': {ldelim}'email': '{$sgEmail}', 'hash': '{$sgHash}'{rdelim}{rdelim},
                                            function (err, serial, output) {ldelim}
                                                var commands = [
                                                    {ldelim}
                                                        'c': 'broadcastEvent',
                                                        'p': {ldelim}
                                                            'event': 'userLoggedIn'
                                                        {rdelim}
                                                    {rdelim},
                                                    {ldelim}
                                                        'c': 'broadcastEvent',
                                                        'p': {ldelim}
                                                            'event': 'closeNotification'
                                                        {rdelim}
                                                    {rdelim}
                                                ]
                                                window.SGAppConnector.sendAppCommands(commands);
                                            {rdelim}
                                        )
                                    {/if}
                                {/if}
                            {rdelim}
                        )
                    {rdelim}
                </script>
            {/if}
            {if $sgFrontendRegister}
                <script type="text/javascript">
                    {literal}
                    ;(function () {
                        document.addEventListener('DOMContentLoaded', function () {
                            var $registrationButton = document.getElementById('new-customer-action');
                            setTimeout(function(){
                                if (!$registrationButton.classList.contains('is--active') && !$registrationButton.classList.contains('is--collapsed')) {
                                        $registrationButton.click();
                                }
                            }, 1000);
                        });
                        var targetLink = null;
                        Array.from(document.getElementsByTagName('a')).forEach(function (link) {
                            if (link.getAttribute("target") === '_blank') {
                                targetLink = link;
                                // Overwrite default behavior of the "Continue Shopping"-Button
                                var url = targetLink.getAttribute('href');
                                targetLink.onclick = (function (e) {
                                    e.preventDefault();
                                    var commands = [
                                        {
                                            c: 'openPage',
                                            p: {
                                                src: url,
                                                emulateBrowser: true,
                                                targetTab: 'in_app_browser',
                                                requestManipulation: false,
                                                navigationBarParams: {
                                                    type: 'in-app-browser-default',
                                                    popTab: 'in_app_browser',
                                                    animation: 'none'
                                                }
                                            }
                                        },
                                        {
                                            c: 'showTab',
                                            p: {
                                                targetTab: 'in_app_browser',
                                                transition: 'slideInFromBottom'
                                            }
                                        }
                                    ];
                                    window.SGAppConnector.sendAppCommands(commands);
                                })
                            }
                        })
                    })();
                    {/literal}
                </script>
            {/if}
            {if $sgFrontendAccount && !$sgForgotPassword}
                <script type="text/javascript">
                    function initPipelineCall () {ldelim}
                        {if !$sgAccountView }
                            window.location.href = '/account#show-registration';
                        {/if}
                        window.SGAppConnector.sendPipelineRequest(
                            'onedot.checkout.updateSession.v1',
                            false,
                            {ldelim}'sessionId': '{$sgSessionId}'{rdelim},
                            function (err, serial, output) {ldelim}
                                {if !$sgAccountView }
                                    window.SGAppConnector.sendPipelineRequest(
                                        'shopgate.user.loginUser.v1',
                                        true,
                                        {ldelim}'strategy': 'auth_code', 'parameters': {ldelim}'email': '{$sgEmail}', 'hash': '{$sgHash}'{rdelim}{rdelim},
                                        function (err, serial, output) {ldelim}
                                            var commands = [
                                                {ldelim}
                                                    'c': 'broadcastEvent',
                                                    'p': {ldelim}
                                                        'event': 'userLoggedIn'
                                                        {rdelim}
                                                    {rdelim},
                                                {ldelim}
                                                    'c': 'broadcastEvent',
                                                    'p': {ldelim}
                                                        'event': 'closeNotification'
                                                    {rdelim}
                                                {rdelim},
                                                {ldelim}
                                                    'c': 'broadcastEvent',
                                                    'p': {ldelim}
                                                        'event': 'closeInAppBrowser',
                                                        'parameters': [{ldelim}'redirectTo': '/'{rdelim}]
                                                    {rdelim}
                                                {rdelim}
                                            ]
                                            window.SGAppConnector.sendAppCommands(commands)
                                        {rdelim}
                                    )
                                {/if}
                            {rdelim}
                        )
                    {rdelim}
                </script>
            {/if}
            {if $sgActionName === 'finish'}
                <script type="text/javascript">
                    window.onbeforeunload = function() {ldelim}
                        var commands = [
                            {ldelim}
                                'c': 'broadcastEvent',
                                'p': {ldelim}
                                    'event': 'closeInAppBrowser',
                                    'parameters': [{ldelim}'redirectTo': '/'{rdelim}]
                                {rdelim}
                            {rdelim}
                        ];
                        window.SGAppConnector.sendAppCommands(commands);
                    {rdelim};
                    function initPipelineCall() {ldelim}
                        window.SGAppConnector.sendPipelineRequest('onedot.checkout.updateSession.v1', false, {ldelim}'sessionId': '{$sgSessionId}', 'promotionVouchers': '{$sgPromotionVouchers}'{rdelim}, function (err, serial, output) {ldelim}{rdelim});
                        disableCloseButton();
                        exchangeContinueShoppingButton();
                        var commands = [
                            {ldelim}
                                'c': 'broadcastEvent',
                                'p': {ldelim}
                                    'event': 'checkoutSuccess',
                                    "parameters": [{$sgCheckoutParams}]
                                    {rdelim}
                                {rdelim},
                            {ldelim}
                                'c': 'setNavigationBarParams',
                                'p': {ldelim}
                                    'navigationBarParams': {ldelim}
                                        'rightButton' : true,
                                        'rightButtonType' : 'close',
                                        'rightButtonCallback' : "SGAction.broadcastEvent({ldelim}event: 'closeInAppBrowser','parameters': [{ldelim}'redirectTo': '/'{rdelim}]{rdelim});"
                                        {rdelim}
                                    {rdelim}
                                {rdelim}
                        ];
                        window.SGAppConnector.sendAppCommands(commands);
                    {rdelim}
                    function disableCloseButton() {ldelim}
                        var setNavigationBarParams = {ldelim}
                            'c': 'setNavigationBarParams',
                            'p': {ldelim}
                                'navigationBarParams': {ldelim}
                                    'rightButton': false
                                {rdelim}
                            {rdelim}
                        {rdelim}
                        window.SGAppConnector.sendAppCommand(setNavigationBarParams);
                    {rdelim}
                    function exchangeContinueShoppingButton() {ldelim}
                        if (document.getElementsByClassName('btn')) {ldelim}
                            var targetButton = null;
                            var shopBaseUrl = window.location.protocol + "//" + window.location.host + "/";
                            Array.from(document.getElementsByClassName('btn')).forEach(function (button) {ldelim}
                                if ((new RegExp(shopBaseUrl)).test(button.getAttribute('href'))) {ldelim}
                                    targetButton = button;
                                    if (targetButton.nodeName === 'A') {ldelim}
                                        // Overwrite default behavior of the "Continue Shopping"-Button
                                        targetButton.setAttribute('href', '#0');
                                        targetButton.onclick = (function () {ldelim}
                                            var commands = [
                                                {ldelim}
                                                    'c': 'broadcastEvent',
                                                    'p': {ldelim}
                                                        'event': 'closeInAppBrowser',
                                                        'parameters': [{ldelim}'redirectTo': '/'{rdelim}]
                                                    {rdelim}
                                                {rdelim}
                                            ];
                                            window.SGAppConnector.sendAppCommands(commands);
                                            {rdelim})
                                        {rdelim}
                                    {rdelim}
                                if (button.classList.contains('teaser--btn-print')) {ldelim}
                                    button.remove();
                                {rdelim}
                            {rdelim});
                        {rdelim}
                    {rdelim}
                </script>
            {/if}
        {/if}
    {/block}
{/block}
