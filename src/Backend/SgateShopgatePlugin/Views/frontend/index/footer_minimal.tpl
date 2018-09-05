{extends file="parent:frontend/index/footer_minimal.tpl"}

{block name="frontend_index_minimal_footer"}
    {if !$sgWebCheckout}
        {$smarty.block.parent}
    {/if}

    {block name="frontend_index_page_wrap_shopgate_script"}
        {if $sgWebCheckout}
            <script type="text/javascript">
                {literal}
                ;(function () {
                    var iOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
                    if (!iOS) {
                        document.getElementsByTagName("BODY")[0].setAttribute("style", "margin-top:48px;position:relative;");
                    }
                })();
                ;(function () {
                    /**
                     * Inject Shopgate app scripts, if we're in the app.
                     */
                    document.addEventListener('DOMContentLoaded', function () {

                        enableShopgateAppEvents()

                        // No retry required for Android devices in the app; init contains another check
                        return initShopgateApp()

                        // try to init the app scripts with a retry mechanism as fallback
                        executeWithRetry(40, 3000, initShopgateApp)
                    })

                    /**
                     * Tryies calling the given function and applies a retry mechanism for a given amount of time
                     * and interval until the call succeeds or the time limit is exceeded.
                     *
                     * @param {number} intervalInMs
                     * @param {number} maximumIntervalTimeInMs
                     * @param +{function} cb
                     */
                    function executeWithRetry(intervalInMs, maximumIntervalTimeInMs, cb) {

                        var startTimestampInMs = Date.now()
                        // try before enabling the retry mechanism
                        if (cb()) {
                            return
                        }

                        var interval = setInterval(
                            function () {
                                // stop retrying after some time
                                if (startTimestampInMs + maximumIntervalTimeInMs <= Date.now()) {
                                    clearInterval(interval)
                                    return
                                }
                                // clear interval upon success (no further retries)
                                if (cb()) {
                                    clearInterval(interval)
                                }
                            },
                            intervalInMs
                        )
                    }

                    /**
                     * Inserts a meta tag that tells the in app browser to avoid showing the zoom buttons.
                     */
                    function disableBrowserZoom() {
                        // insert libshopgate meta tag, to tell the Shopgate app to send events
                        var metaTag = document.createElement('meta')
                        metaTag.setAttribute('name', 'viewport')
                        metaTag.setAttribute('content', 'user-scalable=no, width=device-width')
                        document.getElementsByTagName('head').item(0).appendChild(metaTag)
                    }

                    /**
                     * Creates a key for the script cache fucntions
                     *
                     * @param {string} key
                     * @return {string}
                     */
                    function createScriptCacheKey(key) {
                        if ((typeof key === 'string') && key !== '') {
                            return 'sgCodeCache: ' + key
                        }

                        return ''
                    }

                    /**
                     * Inserts a "libshopgate" meta tag into the head of the page,
                     * to enable the Shopgate app event system.
                     */
                    function enableShopgateAppEvents() {
                        // check if insertion is needed
                        var libshopgate = 'libshopgate';
                        if (document.getElementById(libshopgate)) {
                            return
                        }

                        // insert libshopgate as meta tag, to tell the Shopgate app to send events
                        // not using a script tag to avoid "src unavailable" errors in the browsers console
                        var metaTag = document.createElement('meta')
                        metaTag.setAttribute('id', libshopgate)
                        // add a "src" property (not an attribute, because of the iOS app not receiving it otherwise)
                        metaTag.src = libshopgate
                        document.getElementsByTagName('head').item(0).appendChild(metaTag)
                    }

                    /**
                     * Inserts a vew scripts if the current context is right, so the browser can
                     * communicate with the Shopgate App.
                     *
                     * @return {boolean} Returns false if the context is not the Shopgate App.
                     */
                    function initShopgateApp() {
                        /** @typedef {object} window.SGJavascriptBridge */
                        if (!window.SGJavascriptBridge) {
                            return false
                        }

                        // disable in the Shopgate app only
                        disableBrowserZoom()

                        if (typeof initPipelineCall == 'function') {
                            initPipelineCall();
                        }

                        // call startup script
                        //window.SGAppConnector.loadPipelineScript('__init')

                        // close loading spinner after 3 seconds, in case something goes wrong
                        setTimeout(function () {
                            // show a warning message if it is still open
                            window.SGAppConnector.closeLoadingSpinner()
                        }, 3000)

                        // mark the retry attempt as successful
                        return true
                    }

                    window.SGAppConnector = {
                        /**
                         * Stores response callbacks and pass through params for pipeline calls
                         */
                        pipelineResponseHandler: {},

                        /**
                         * Takes any type of variable and checks if the input is a function.
                         *
                         * @param {*|null} func
                         * @return {boolean}
                         */
                        functionExists: function (func) {
                            return (typeof func === 'function')
                        },

                        /**
                         * Takes a length param and creates a random passphrase and returns it.
                         *
                         * @param {number} len
                         * @return {string}
                         */
                        getRandomPassPhrase: function (len) {
                            if (!len) len = 16
                            return (new Array(len))
                                .fill("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!Â§$%&/()=?ÃŸ+*~#'-_.:,;<>|{[]}^Â°")
                                .map(function (x) {
                                    return x[Math.floor(Math.random() * x.length)]
                                }).join('')
                        },

                        /**
                         * Takes a url string and parses a given GET param out of it. Uses the window.location if no url given.
                         *
                         * @param {string} paramName
                         * @param {string|null} url
                         * @return {string|null}
                         */
                        getParameterByName: function (paramName, url) {
                            if (!url) {
                                url = window.location.href
                            }

                            paramName = paramName.replace(/[\[\]]/g, "\\$&")
                            var regex = new RegExp("[?&]" + paramName + "(=([^&#]*)|&|#|$)")
                            var results = regex.exec(url)

                            if (!results) {
                                return null
                            }

                            if (!results[2]) {
                                return ''
                            }

                            return decodeURIComponent(results[2].replace(/\+/g, " "))
                        },

                        /**
                         * Sends an array of app commands to the Shopgate app. The SGJavascriptBridge is required for this.
                         *
                         * @param {object[]} appCommands
                         */
                        sendAppCommands: function (appCommands) {
                            var jsBridgeVersion = '12.0';
                            if ('dispatchCommandsForVersion' in window.SGJavascriptBridge) {
                                window.SGJavascriptBridge.dispatchCommandsForVersion(appCommands, jsBridgeVersion)
                            } else {
                                window.SGJavascriptBridge.dispatchCommandsStringForVersion(JSON.stringify(appCommands), jsBridgeVersion)
                            }
                        },

                        /**
                         * Sends an array of app commands to the Shopgate app. The SGJavascriptBridge is required for this.
                         *
                         * @param {object} appCommand
                         */
                        sendAppCommand: function (appCommand) {
                            this.sendAppCommands([appCommand])
                        },

                        /**
                         * Creates a special app command to close the loading spinner.
                         * A warning can be created, if the command is actually sent.
                         */
                        closeLoadingSpinner: function () {
                            this.sendAppCommand({'c': 'onload'})
                        },

                        /**
                         * Sends out a pipeline request and calls the given callback on response (if set).
                         * A param can be passed through to the callback, when it's called.
                         *
                         * @param {string} pipelineName
                         * @param {boolean} trusted
                         * @param {*|null} data
                         * @param {function|null} callback
                         * @param {*|null} callbackParams
                         */
                        sendPipelineRequest: function (pipelineName, trusted, data, callback, callbackParams) {
                            if (!data) {
                                data = {}
                            }

                            if (!callbackParams) {
                                callbackParams = null
                            }

                            var appCommand = {
                                c: 'sendPipelineRequest',
                                p: {
                                    serial: pipelineName,
                                    name: pipelineName,
                                    input: data
                                }
                            }

                            if (trusted) {
                                appCommand.p['type'] = 'trusted'
                            }

                            // set response callback if available
                            this.pipelineResponseHandler[pipelineName] = {
                                callbackParams: callbackParams,
                                __call: function (err, output, callbackParams) {

                                    if (!window.SGAppConnector.functionExists(callback)) {
                                        console.log("## no callback registered for pipeline call: " + pipelineName)
                                        return;
                                    }

                                    console.log("## running response callback for pipeline call: " + pipelineName)
                                    return callback(err, output, callbackParams)
                                }
                            }

                            this.sendAppCommand(appCommand)
                        },

                        /**
                         * Injects the given script code as a script-tag into the html-head-tag as last element.
                         * Injected code is automatically scoped if not forced global scope.
                         *
                         * @param {string} scriptContent
                         * @param {boolean|null} globalScope
                         */
                        includeScript: function (scriptContent, globalScope) {
                            if (!globalScope) {
                                scriptContent = '(function () {' + scriptContent + ';})();'
                            }
                            var scriptElement = document.createElement('script')
                            scriptElement.setAttribute('type', 'text/javascript')
                            scriptElement.appendChild(document.createTextNode(scriptContent))
                            document.getElementsByTagName('head').item(0).appendChild(scriptElement)
                        },

                        /**
                         * Takes script code and puts it into the localStorage to allow faster loading times when it is needed again.
                         *
                         * @param {string} key
                         * @param {string} scriptCode
                         */
                        saveScriptToCache: function (key, scriptCode) {
                            var cacheKey = createScriptCacheKey(key)
                            if (!cacheKey || (typeof scriptCode !== 'string')) {
                                return
                            }

                            window.localStorage.setItem(cacheKey, btoa(scriptCode))
                        },

                        /**
                         * Tries to load a script from the localStorage.
                         *
                         * @param {string} key
                         * @return {string} Returns an empty string if nothing is available in the cache.
                         */
                        getScriptFromCache: function (key) {
                            var cacheKey = createScriptCacheKey(key)
                            if (cacheKey) {
                                var scriptCode = window.localStorage.getItem(cacheKey)
                                return ((typeof scriptCode === 'string') ? atob(scriptCode) : null)
                            }

                            return null
                        },

                        /**
                         * Loads a script file from a given url and injects it into the current page.
                         *
                         * @param {string} url
                         * @param {boolean|null} globalScope
                         */
                        loadRemoteScript: function (url, globalScope) {
                            var cachedScript = this.getScriptFromCache(url);
                            if (cachedScript) {
                                this.includeScript(cachedScript, globalScope)
                                return
                            }

                            var client = new XMLHttpRequest()
                            client.open('GET', url)
                            client.onreadystatechange = function () {
                                window.SGAppConnector.saveScriptToCache(url, client.responseText)
                                window.SGAppConnector.includeScript(client.responseText, globalScope)
                            }
                            client.send()
                        },

                        /**
                         * Calls a pipeline to get script code to be injected.
                         *
                         * @param {string} scriptName
                         * @param {*|null} passthroughParams Parameters to be passed to the pipelines entry function
                         */
                        loadPipelineScript: function (scriptName, passthroughParams) {
                            var cachedScript = this.getScriptFromCache(scriptName);
                            if (cachedScript) {
                                this.includeScript(cachedScript)
                                if (this.functionExists(window.SGPipelineScript[scriptName])) {
                                    console.log("## -> calling: SGPipelineScript." + scriptName + "(" + JSON.stringify(passthroughParams) + ")")
                                    window.SGPipelineScript[scriptName](passthroughParams)
                                }
                                return
                            }

                            // get script from pipeline if not cached, yet
                            this.sendPipelineRequest('getScript_v1', false, {scriptName: scriptName}, function (err, output, cbParams) {
                                console.log("## -> including pipeline script: " + cbParams.scriptName + ".js")

                                var scriptCode = atob(output.scriptCode)

                                // cache the script in case another page needs it
                                window.SGAppConnector.saveScriptToCache(cbParams.scriptName, scriptCode)

                                window.SGAppConnector.includeScript(scriptCode)
                                if (window.SGAppConnector.functionExists(window.SGPipelineScript[cbParams.scriptName])) {
                                    console.log(
                                        "## -> calling: SGPipelineScript." + cbParams.scriptName +
                                        "(" + JSON.stringify(cbParams.passthroughParams) + ")"
                                    )
                                    window.SGPipelineScript[cbParams.scriptName](passthroughParams)
                                }
                            }, {scriptName: scriptName, passthroughParams: passthroughParams, this: this})
                        }
                    }

                    /**
                     * Empty object to place pipeline script code inside
                     */
                    window.SGPipelineScript = {}

                    window.SGEvent = {
                        __call: function (eventName, eventArguments) {

                            console.log(
                                '# Received event ' + eventName
                                // + ' with args ' + JSON.stringify(eventArguments)
                            )

                            if (!eventArguments || !Array.isArray(eventArguments)) {
                                eventArguments = []
                            }

                            if (SGEvent[eventName]) {
                                SGEvent[eventName].apply(SGEvent, eventArguments)
                            }
                        },

                        /**
                         * Pipeline response event handler.
                         *
                         * @param {object|null} err
                         * @param {string} pipelineName Also known as "serial"
                         * @param {object} output Pipeline response object in non-error case
                         * @return {*|void}
                         */
                        pipelineResponse: function (err, pipelineName, output) {
                            if (err) {
                                console.error("Called pipeline '" + pipelineName + "' resulted in an error: " + JSON.stringify(err))
                            }

                            // call assigned response handler callback and pass through the callbackParams
                            if (window.SGAppConnector.pipelineResponseHandler[pipelineName]) {
                                var responseHandler = window.SGAppConnector.pipelineResponseHandler[pipelineName]
                                return responseHandler.__call(err, output, responseHandler.callbackParams)
                            }
                        },

                        /**
                         * This event is called by the app to check if the lib is ready.
                         *
                         * @returns {boolean}
                         */
                        isDocumentReady: function () {
                            return true
                        }
                    }

                    // add String.prototype.endsWith backwards compatibility
                    if (window.SGAppConnector.functionExists(String.prototype.endsWith)) {
                        String.prototype.endsWith = function (suffix) {
                            return this.indexOf(suffix, this.length - suffix.length) !== -1
                        }
                    }
                })();
                {/literal}
            </script>
        {/if}
        {if $sgActionName === 'confirm' || $sgActionName === 'shippingPayment'}
            <script type="text/javascript">
                function initPipelineCall () {ldelim}
                    window.SGAppConnector.sendPipelineRequest(
                        'onedot.checkout.updateSession.v1',
                        false,
                        {ldelim}'sessionId': '{$sgSessionId}'{rdelim},
                        function (err, serial, output) {ldelim}
                        {rdelim}
                    )
                {rdelim}
            </script>
        {/if}
    {/block}
{/block}