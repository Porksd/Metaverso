jQuery(document).ready(function ($) {
    iFrameReady(document.getElementById("elementor-preview-iframe"), function () {
        const IFRAME = this;
        $(this).ready(function () {
            const observer = new MutationObserver(function (mutations) {
                for (let i = 0; i < mutations.length; i++) {
                    for (let j = 0; j < mutations[i].addedNodes.length; j++) {
                        $(mutations[i].addedNodes[j].childNodes).find('.tbk-frontend').each(function () {
                            const event = new CustomEvent('TBK::ELEMENTOR::LOAD', {detail: $(this).find('.tbk-inner-content')[0]});
                            IFRAME.dispatchEvent(event);
                        })
                    }
                }
            })

            observer.observe(IFRAME, {
                childList: true,
                subtree  : true
            });
        })
    });

})

function iFrameReady(iFrame, fn) {
    let timer;
    let fired = false;

    function ready() {
        if (!fired) {
            fired = true;
            clearTimeout(timer);
            fn.call(this);
        }
    }

    function readyState() {
        if (this.readyState === "complete") {
            ready.call(this);
        }
    }

    function addEvent(elem, event, fn) {
        if (elem.addEventListener) {
            return elem.addEventListener(event, fn);
        } else {
            return elem.attachEvent("on" + event, function () {
                return fn.call(elem, window.event);
            });
        }
    }

    addEvent(iFrame, "load", function () {
        ready.call(iFrame.contentDocument || iFrame.contentWindow.document);
    });

    function checkLoaded() {
        const doc = iFrame.contentDocument || iFrame.contentWindow.document;
        if (doc.URL.indexOf("about:") !== 0) {
            if (doc.readyState === "complete") {
                ready.call(doc);
            } else {
                // set event listener for DOMContentLoaded on the new document
                addEvent(doc, "DOMContentLoaded", ready);
                addEvent(doc, "readystatechange", readyState);
            }
        } else {
            // still same old original document, so keep looking for content or new document
            timer = setTimeout(checkLoaded, 1);
        }
    }

    checkLoaded();
}