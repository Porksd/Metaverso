/*
It is master branch code
*/
! function (window, $) {

    var locoConf = window.locoConf;
    var allStrings = locoConf.conf.podata;
    allStrings.shift();
    var ajaxUrl = extradata.ajax_url;
    var nonces = extradata.nonce;
    var locale = locoConf.conf.locale;
    var projectId = '';
    if (locoConf.conf.project != null) {
        projectId = locoConf.conf.project.domain + '-' + locale.lang + '_' + locale.region;
    } else {
        projectId = 'temp-' + locale.lang + '_' + locale.region;
    }


    /*
     Save Translated Strings
     */
    var rpl = {
        '"% s"': '"%s"',
        '"% d"': '"%d"',
        '"% S"': '"%s"',
        '"% D"': '"%d"',
        '% s': ' %s ',
        '% S': ' %s ',
        '% d': ' %d ',
        '% D': ' %d ',
        '٪ s': ' %s ',
        '٪ S': ' %s ',
        '٪ d': ' %d ',
        '٪ D': ' %d ',
        '٪ س': ' %s '
    };
    /*
   since version 1.1
   Google Translate Popup handlers
*/
    newaddAutoTranslationBtn();

    settingsModel();
    createStringsPopup();
    create_G_popup();
    create_deepl_popup();



    // integrates auto traslator button in editor
    function newaddAutoTranslationBtn() {
        if ($("#loco-editor nav").find("#cool-auto-translate-btn").length > 0) {
            $("#loco-editor nav").find("#cool-auto-translate-btn").remove();
        }
        const locoActions = $("#loco-editor nav").find("#loco-actions");
        const otherBtn = '<button class="button has-icon icon-warn" id="atlt_reset_all">Reset Translations</button></fieldset>';
        const allTranslated = '<fieldset><button id="cool-auto-translate-btn" class="button has-icon icon-translate" disabled>Translated</button></fieldset>';
        const proActiveBtn = '<fieldset><button id="cool-auto-translate-btn" class="button has-icon icon-translate">Auto Translate</button></fieldset>';
        locoActions.append(proActiveBtn);
    }

    // open popup on autotranslate button click
    $("#cool-auto-translate-btn").on("click", function () {
        openPopup();
    });
    // create auto translate popup
    function openPopup() {
        $("#atlt-dialog").dialog({
            resizable: false,
            height: "auto",
            width: 400,
            modal: true,
            buttons: {
                Cancel: function () {
                    $(this).dialog("close");
                }
            }
        });
    }

    var encodeHtmlEntity = function (str) {
        var buf = [];
        for (var i = str.length - 1; i >= 0; i--) {
            buf.unshift(['&#', str[i].charCodeAt(), ';'].join(''));
        }
        return buf.join('');
    };

    /*
    load strings in popup table 
    */
    function printStringsInPopup(jsonObj, type) {
        var html = '';
        var totalTChars = 0;
        var index = 1;
        if (jsonObj) {
            for (const key in jsonObj) {

                if (jsonObj.hasOwnProperty(key)) {
                    const element = jsonObj[key];
                    if (element.source != '') {

                        if (type == "google" || type == "yandex") {
                            html += '<tr id="' + key + '" ><td>' + index + '</td><td  class="notranslate source">' + encodeHtmlEntity(element.source) + '</td>';
                        } else {
                            if (key > 2500) {
                                break;
                            }
                            html += '<tr id="' + key + '" ><td>' + index + '</td><td  class="notranslate source">' + encodeHtmlEntity(element.source) + '</td>';
                        }
                        if (type == "google") {
                            html += '<td class="target translate">' + element.source + '</td></tr>';
                        } else if (type == "yandex") {
                            html += '<td translate="yes" class="target translate">' + element.source + '</td></tr>';
                        } else {
                            html += '<td class="target translate"></td></tr>';
                        }
                        index++;
                        totalTChars += element.source.length;
                    }
                }
            }
            if (type == "google") {
                $(".gtstats").each(function () {
                    $(this).find(".totalChars").html(totalTChars);
                });

            } else if (type == "yandex") {
                $(".ytstats").each(function () {
                    $(this).find(".totalChars").html(totalTChars);
                });
            } else {
                $(".dtstats").each(function () {
                    $(this).find(".totalChars").html(totalTChars);
                });
            }

        }
        if (type == "google") {
            $("#string_tbl").html(html);
        } else if (type == "yandex") {
            $("#yandex_string_tbl").html(html);
        } else {
            $("#deepl_string_tbl").html(html);
        }
    }


    function saveTranslatedStrings(translatedObj, projectId) {

        if (translatedObj !== undefined && translatedObj.length) {

            if (translatedObj.length < 5000 && translatedObj.length >= 2500) {
                var firstPart = translatedObj.slice(0, 2500);
                var secondPart = translatedObj.slice(2500);
                sendRequest(firstPart, projectId, part = "first", trigger = false);
                sendRequest(secondPart, projectId, part = "second", trigger = true);
            } else if (translatedObj.length >= 5000) {
                var firstPart = translatedObj.slice(0, 2500);
                var secondPart = translatedObj.slice(2500, 5000);
                var thirdpart = translatedObj.slice(5000);
                sendRequest(firstPart, projectId, part = "first", trigger = false);
                sendRequest(secondPart, projectId, part = "second", trigger = false);
                sendRequest(thirdpart, projectId, part = "third", trigger = true);
            } else {
                sendRequest(translatedObj, projectId, part = "one", trigger = true);
            }


        }
    }

    function sendRequest(stringData, projectId, part, trigger) {
        var data = {
            'action': 'save_translations',
            'data': JSON.stringify(stringData),
            'part': part,
            'project-id': projectId,
        };
        jQuery.post(ajaxUrl, data, function (response) {

            $('#loco-editor nav').find('button').each(function (i, el) {
                var id = el.getAttribute('data-loco');
                if (id == "auto") {
                    if (trigger === true) {

                        $(el).trigger("click");
                    }

                }
            });
        });

    }


    // save strings in db
    var translatedObj = [];
    $(".save_it").on("click", function () {
        $("#stringTemplate tbody tr").each(function (index) {
            var target = $(this).find("td.target").text();
            var source = $(this).find("td.source").text();
            var improvedTarget = strtr(target, rpl);
            translatedObj.push({
                "source": source,
                "target": improvedTarget
            });
        });
        var projectId = $(this).parents(".atlt_custom_model").find("#project_id").val();
        saveTranslatedStrings(translatedObj, projectId);
        $(".atlt_custom_model").fadeOut("slow");

        $("html").addClass("merge-translations");
        updateLocoModel();
    });

    //Click on Loco Default Auto Button
    $(".loco-modal").removeClass("addtranslations");
    $("button.icon-robot[data-loco='auto']").on("click", function (e) {
        $(".loco-modal").removeClass("addtranslations");
        if (e.originalEvent !== undefined) {
            var checkModal = setInterval(function () {
                if ($('.loco-modal #loco-apis-batch').length) {
                    $(".loco-modal").removeClass("addtranslations");
                    $("select#auto-api").show();
                    $("#loco-apis-batch a.icon-help").show(); //Show help button again.
                    $("#loco-apis-batch a.icon-group").show(); //Show human button again.
                    $("#loco-apis-batch #loco-job-progress").show(); //Show translation status again.
                    $(".loco-modal .ui-dialog-titlebar .ui-dialog-title").html("Auto-translate this file");
                    $("#loco-apis-batch button.button-primary span").html("Translate");
                    var opt = 0;
                    $('select#auto-api option').each(function () {
                        opt = opt + 1;
                    });
                    if (opt == 1) {
                        $('.noapiadded').remove();
                        $(".loco-modal #loco-apis-batch").removeClass("loco-alert");
                        $(".loco-modal #loco-apis-batch form").hide();
                        $(".loco-modal #loco-apis-batch").addClass("loco-alert");
                        $(".loco-modal .ui-dialog-titlebar .ui-dialog-title").html("No translation APIs configured");
                        var settingsUrl = extradata.loco_settings_url;
                        $(".loco-modal #loco-apis-batch").append("<div class='noapiadded'><p>Add automatic translation services in the plugin settings.<br>or<br>Use <strong>Auto Translate</strong> addon button.</p><nav><a href='" + settingsUrl + "' class='button button-link has-icon icon-cog'>Settings</a><a href='https://localise.biz/wordpress/plugin/manual/providers' class='button button-link has-icon icon-help' target='_blank'>Help</a><a href='https://localise.biz/wordpress/translation?l=de-DE' class='button button-link has-icon icon-group' target='_blank'>Need a human?</a></nav></div>");
                    }
                    clearInterval(checkModal);
                }
            }, 200); // check every 100ms         
        }
    });

    /*-----------Yandex integration---------*/

    /*
      String Translate Model
    */

    // Get the modal
    var yModal = document.getElementById("atlt_strings_model");

    // Get the modal
    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function (event) {
        if (event.target == yModal) {
            $("#atlt_strings_model").fadeOut("slow");
        }
    };

    // Get the <span> element that closes the modal
    // When the user clicks on <span> (x), close the modal
    $("#atlt_strings_model").find(".close").on("click", function () {
        $("#atlt_strings_model").fadeOut("slow");
    });


    // When the user clicks the button, open the modal 
    $("#atlt_yandex_transate_btn").on("click", function () {
        //  alert('herer');
        var defaultcode = locale.lang ? locale.lang : null;

        switch (defaultcode) {
            case 'bel':
                defaultlang = 'be';
                break;
            case 'he':
                defaultlang = 'iw';
                break;
            case 'snd':
                defaultlang = 'sd';
                break;
            case 'jv':
                defaultlang = 'jv';
                break;
            case 'nb':
                defaultlang = 'no';
                break;

            case 'nn':
                defaultlang = 'no';
                break;
            default:
                defaultlang = defaultcode;
                break;
        }

        $(".save_it").prop("disabled", true);
        $(".ytstats").css("display", "none");
        localStorage.setItem("lang", defaultlang);
        var arr = ['af', 'jv', 'am', 'no', 'ar', 'az', 'ba', 'be', 'bg', 'bn', 'bs', 'ca', 'ceb', 'cs', 'cy', 'da', 'de', 'el', 'en', 'eo', 'es', 'et', 'eu', 'fa', 'fi', 'fr', 'ga', 'gd', 'gl', 'gu', 'he', 'hi', 'hr', 'ht', 'hu', 'hy', 'id', 'is', 'it', 'ja', 'jv', 'ka', 'kk', 'km', 'kn', 'ko', 'ky', 'la', 'lb', 'lo', 'lt', 'lv', 'mg', 'mhr', 'mi', 'mk', 'ml', 'mn', 'mr', 'mrj', 'ms', 'mt', 'my', 'ne', 'nl', 'no', 'pa', 'pap', 'pl', 'pt', 'ro', 'ru', 'si', 'sk', 'sl', 'sq', 'sr', 'su', 'sv', 'sw', 'ta', 'te', 'tg', 'th', 'tl', 'tr', 'tt', 'udm', 'uk', 'ur', 'uz', 'vi', 'xh', 'yi', 'zhCN'];
        if (arr.includes(defaultlang)) {
            //   googleTranslateElementInit();
        } else {
            $(".yandex-notice-container").addClass('notice inline notice-warning')
                .html("Yandex Automatic Translator Does not support this language.");
            $(".choose-lang").hide();

            $(".string_container").hide();
            $(".save_it").hide();
            $("#ytWidget").hide();
        }
        // get only plain strings 
        var plainStrArr = filterRawObject(allStrings, "plain");
        if (plainStrArr.length > 0) {
            printStringsInPopup(plainStrArr, type = "yandex");
        } else {
            $("#ytWidget").hide();
            $(".yandex-notice-container").addClass('notice inline notice-warning')
                .html("There is no plain string available for translations.");
            $(".choose-lang").hide();


            $(".string_container").hide();
            $(".save_it").hide();
        }
        $("#atlt-dialog").dialog("close");
        $("#atlt_strings_model").addClass("yandex-translator").fadeIn("slow");

    });




    /*-----------Google integration---------*/
    /*
       Google Translate Model
     */
    // Get the modal
    var gModal = document.getElementById("atlt_gtranslate_model");

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function (event) {
        if (event.target == gModal) {
            gModal.style.display = "none";
        }
    };

    // Get the <span> element that closes the modal

    // When the user clicks on <span> (x), close the modal
    $("#atlt_gtranslate_model").find(".close").on("click", function () {
        $("#atlt_gtranslate_model").fadeOut("slow");
    });
    // Get the button that opens the modal
    // var gTranslateBtn = document.getElementById("atlt_gtranslate_btn");

    // When the user clicks the button, open the modal 
    $("#atlt_gtranslate_btn").on("click", function () {
        var container = $("#atlt_gtranslate_model");
        var defaultcode = locale.lang ? locale.lang : null;
        switch (defaultcode) {
            case 'bel':
                defaultlang = 'be';
                break;
            case 'he':
                defaultlang = 'iw';
                break;
            case 'snd':
                defaultlang = 'sd';
                break;
            case 'jv':
                defaultlang = 'jw';
                break;
            case 'nb':
                defaultlang = 'no';
                break;

            case 'nn':
                defaultlang = 'no';
                break;
            default:
                defaultlang = defaultcode;
                break;
        }
        var arr = ['en', 'zh', 'no', 'sq', 'am', 'ar', 'hy', 'az', 'eu', 'be', 'bn', 'bs', 'bg', 'ca', 'ceb', 'ny', 'zh-CN', 'zh-TW', 'co', 'hr', 'cs', 'da', 'nl', 'eo', 'et', 'tl', 'fi', 'fr', 'fy', 'gl', 'ka', 'de', 'el', 'gu', 'ht', 'ha', 'haw', 'iw', 'hi', 'hmn', 'hu', 'is', 'ig', 'id', 'ga', 'it', 'ja', 'jw', 'kn', 'kk', 'km', 'rw', 'ko', 'ku', 'ky', 'lo', 'la', 'lv', 'lt', 'lb', 'mk', 'mg', 'ms', 'ml', 'mt', 'mi', 'mr', 'mn', 'my', 'ne', 'no', 'or', 'ps', 'fa', 'pl', 'pt', 'pa', 'ro', 'ru', 'sm', 'gd', 'sr', 'st', 'sn', 'sd', 'si', 'sk', 'sl', 'so', 'es', 'su', 'sw', 'sv', 'tg', 'ta', 'tt', 'te', 'th', 'tr', 'tk', 'uk', 'ur', 'ug', 'uz', 'vi', 'cy', 'xh', 'yi', 'yo', 'zu'];
        if (arr.includes(defaultlang)) {
            googleTranslateElementInit();
        } else {
            $(".google-notice-container").addClass('notice inline notice-warning')
                .html("Google Automatic Translator Does not support this language.");
            $(".choose-lang").hide();

            container.find(".string_container").hide();
            container.find(".save_it").hide();
        }
        // get only plain strings 
        var plainStrArr = filterRawObject(allStrings, "plain");

        if (plainStrArr.length > 0) {
            printStringsInPopup(plainStrArr, type = "google");
        } else {
            $(".google-notice-container").addClass('notice inline notice-warning')
                .html("There is no plain string available for translations.");
            $(".choose-lang").hide();
            container.find(".string_container").hide();
            container.find(".save_it").hide();
        }
        $("#atlt-dialog").dialog("close");
        $("#atlt_gtranslate_model").fadeIn("slow");

    });

    //scroll down model on language changes
    $("#google_translate_element").change(function () {
        // alert("change occured");
        var container = $("#atlt_gtranslate_model");
        container.find(".string_container").scrollTop(0);
        var scrollHeight = container.find('.string_container').get(0).scrollHeight;
        var scrollSpeed = 10000;
        if (scrollHeight > scrollSpeed) {
            scrollSpeed = scrollHeight;
        }
        if (scrollHeight !== undefined && scrollHeight > 100) {
            container.find(".my_translate_progress").fadeIn("slow");
            setTimeout(() => {
                container.find(".string_container").animate({
                    scrollTop: scrollHeight + 2000
                }, scrollSpeed * 2, 'linear');
            }, 2000);

            container.find('.string_container').on('scroll', function () {
                if ($(this).scrollTop() + $(this).innerHeight() + 50 >= $(this)[0].scrollHeight) {
                    setTimeout(() => {
                        container.find(".save_it").prop("disabled", false);
                        container.find(".gtstats").fadeIn("slow");
                        container.find(".my_translate_progress").fadeOut("slow");
                        container.find(".string_container").stop();
                        $('body').css('top', '0');
                    }, 1500);
                }
            });

            if (container.find('.string_container').innerHeight() + 10 >= scrollHeight) {
                setTimeout(() => {
                    container.find(".save_it").prop("disabled", false);
                    container.find(".gtstats").fadeIn("slow");
                    container.find(".my_translate_progress").fadeOut("slow");
                    container.find(".string_container").stop();
                    $('body').css('top', '0');
                }, 1500);
            }
        } else {
            setTimeout(() => {
                container.find(".save_it").prop("disabled", false);
                container.find(".gtstats").fadeIn("slow");
            }, 2000);
        }
    });

    /*
        since version 1.2
        Deepl Translate Popup handlers
    */
    // Get the button that opens the modal
    // Get the <span> element that closes the modal
    var closeDeeplBtn = document.getElementsByClassName("close_deepl")[0];
    // When the user clicks on <span> (x), close the modal
    closeDeeplBtn.onclick = function () {
        $("#atlt_deepl_model").fadeOut("slow");
    }
    var deeplTranslateBtn = document.getElementById("atlt_deepl_btn");
    jQuery("#deepl-transtmplt").hide();
    // When the user clicks the button, open the modal 
    deeplTranslateBtn.onclick = function () {
        var defaultcode = locale.lang ? locale.lang : null;
        console.log(defaultcode);
        defaultlang = defaultcode;
        var container = $("#atlt_deepl_model");

        var domainName = locoConf.conf.project.domain;
        var arr = ['en', 'es', 'fr', 'it', 'nl', 'pl', 'pt', 'pt-br', 'ru', 'ja', 'zh', 'de', 'ro', 'lt', 'lv', 'bg', 'cs', 'da', 'et', 'fi', 'el', 'hu', 'sk', 'sl', 'sv'];
        // var arr = ['en','es','sq','am','ar','hy','az','eu','be','bn','bs','bg','ca','ceb','ny','zh-CN','zh-TW','co','hr','cs','da','nl','eo','et','tl','fi','fr','fy','gl','ka','de','el','gu','ht','ha','haw','iw','hi','hmn','hu','is','ig','id','ga','it','ja','jw','kn','kk','km','rw','ko','ku','ky','lo','la','lv','lt','lb','mk','mg','ms','ml','mt','mi','mr','mn','my','ne','no','or','ps','fa','pl','pt','pa','ro','ru','sm','gd','sr','st','sn','sd','si','sk','sl','so','es','su','sw','sv','tg','ta','tt','te','th','tr','tk','uk','ur','ug','uz','vi','cy','xh','yi','yo','zu'];
        // console.log(filterRawObject(conf.podata,"plain")); 
        if (arr.includes(defaultlang)) {
            // get only plain strings 
            var plainStrArr = filterRawObject(allStrings, "plain");

            if (plainStrArr.length > 0) {
                $("#download_deepl_doc").data("domain-name", domainName);
                printStringsInPopup(plainStrArr, type = "deepl");
            } else {
                container.find(".string_container").hide();
                $(".deepl_save_it").hide();
                $("#download_deepl_doc").hide();
                $("#deepl-open-file").hide();
                $("#deepl-btn-tpload").hide();
                $("table.deepl_steps").hide();
                $(".deepl-notice-container").addClass('notice inline notice-warning')
                    .html("There is no plain string available for translations");
                $(".choose-lang").hide();
            }
            $("#atlt-dialog").dialog("close");
            $("#atlt_deepl_model").fadeIn("slow");
        } else {
            $(".deepl-notice-container").addClass('notice inline notice-warning')
                .html("Deepl Translator does not support this language");
            $(".choose-lang").hide();
            container.find(".string_container").hide();
            $(".deepl_save_it").hide();
            $("#atlt-dialog").dialog("close");
            $("#atlt_deepl_model").fadeIn("slow");
            $("#download_deepl_doc").hide();
            $("#deepl-open-file").hide();
            $("#deepl-btn-tpload").hide();
            $("table.deepl_steps").hide();
        }
        jQuery("body").on("click", "#deepl-btn-tpload", function (event) {
            upload_deepl_file(domainName, defaultcode, plainStrArr);

        });

    }
    $("#download_deepl_doc").on("click", function () {
        generate_doc_file(this);
    });

    function generate_doc_file(thisBtn) {
        var stringsTxt = "";
        $("#deepl_stringTemplate tbody tr").each(function (index) {
            var string = $(this).find("td.source").text();
            stringsTxt += '™' + string;
        });
        var columnTitles = stringsTxt.split(/\™/g);
        var domainName = $(thisBtn).data("domain-name");
        const doc = new docx.Document();
        const tableRow = new docx.Table({
            rows: columnTitles.map((title) => {
                return new docx.TableRow({
                    children: [
                        new docx.TableCell({
                            children: [new docx.Paragraph(title + ' ' + '\n\n\t\n')],
                        })
                    ]
                }) //child close
            })
        });
        doc.addSection({
            children: [
                tableRow
            ],
        }),
            docx.Packer.toBlob(doc).then(blob => {
                saveAs(blob, domainName + "-strings.docx");

                $(thisBtn).prop("disabled", true);
            });
    }
    /**
     * This function is used for file validation
     */
    function matchRuleShort(str, rule) {
        var escapeRegex = (str) => str.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
        return new RegExp("^" + rule.split("*").map(escapeRegex).join(".*") + "$").test(str);
    } // close validation function
    /**
     * This function is used for upload deepl translation file.
     */
    function upload_deepl_file(domainName, defaultcode, plainStrArr) {
        var filename = domainName + '-strings';
        var fullPath = document.getElementById("deepl-open-file").value;
        var res = defaultcode.toUpperCase();
        var valid_exte = filename + " " + res + ".docx";
        var valid_ext = valid_exte.toUpperCase();
        jsonObj = plainStrArr;
        var arrInputs = $("#deepl-open-file");
        var allowedFiles = [valid_ext];
        var wild_char = domainName + "-strings" + " " + "*" + " " + res + ".docx";
        var wild_chars = domainName + "-strings" + " " + res + " " + "*" + ".docx".toUpperCase();
        var wild_char_ext = wild_char.toUpperCase();
        var wild_chars_ext = wild_chars.toUpperCase();
        if (fullPath) {
            var startIndex = (fullPath.indexOf('\\') >= 0 ? fullPath.lastIndexOf('\\') : fullPath.lastIndexOf('/'));
            var filename = fullPath.substring(startIndex);
            if (filename.indexOf('\\') === 0 || filename.indexOf('/') === 0) {
                file = filename.substring(1);
                filename = file.toUpperCase();
            }
            if (matchRuleShort(filename, wild_char_ext) || matchRuleShort(filename, valid_ext) || matchRuleShort(filename, wild_chars_ext)) {
                lblError.innerHTML = "";
                var input = document.getElementById('deepl-open-file');
                var reader = new FileReader();

                function nullGetter(part, scopeManager) {
                    // part.module can be "loop", "rawxml", or empty, (or any other module name that you use)
                    if (!part.module) {
                        // part.value contains the content of the tag, eg "name" in our example
                        return '{' + part.value + '}';
                    }
                    if (part.module === "rawxml") {
                        return "";
                    }
                    return "";
                }
                reader.onload = function () {
                    jQuery("#deepl_stringTemplate").hide();
                    jQuery("#deepl-transtmplt").show();
                    var zip = new JSZip(reader.result);
                    var br = document.createElement("br");
                    var doc = new window.docxtemplater().loadZip(zip);
                    var text = doc.getFullText();
                    // var arr=text.split(/\©/g);
                    // var arr=text.split(/\ \n/g);
                    var arr = text.split(/\n\n\t\n/g);

                    var filtered = arr.filter(function (el) {
                        return el != " ";
                    });
                    //node.innerText = filtered;
                    var demo = "";
                    var index = 1;
                    for (const key in jsonObj) {
                        if (jsonObj.hasOwnProperty(key)) {
                            const element = jsonObj[key];
                            if (element.source != '' && filtered[key] != undefined) {
                                if (key > 2500) {
                                    break;
                                }
                                demo += '<tr id="' + key + '" ><td>' + index + '</td><td  class="notranslate source">' + element.source + '</td>';
                                demo += '<td class="target"  id="output"> ' + filtered.slice(1)[key] + '</td></tr>';
                            }
                            index++;
                        }
                    }
                    jQuery("#deepbody").append(demo);
                };
                reader.readAsBinaryString(input.files[0]);
                $(".deepl_save_it").prop("disabled", false);
                $("#deepl-btn-tpload").prop('disabled', true);
            } else {
                lblError.innerHTML = "Please upload files having extensions: <b>" + allowedFiles.join(', ') + "</b> only.";
                return false;
            }
        }
    } // Close upload translation file function



    $(".deepl_save_it").on("click", function () {

        $("#deepl-transtmplt tbody tr").each(function (index) {
            var target = $(this).find("td.target").text();
            var improvedTarget = strtr(target, rpl);
            var source = $(this).find("td.source").text();
            translatedObj.push({
                "source": source,
                "target": improvedTarget
            });
        });
        var projectId = $(this).parents(".atlt_custom_model").find("#project_id").val();
        saveTranslatedStrings(translatedObj, projectId);
        $(".atlt_custom_model").fadeOut("slow");

        $("html").addClass("merge-translations");
        updateLocoModel();

    });

    /*---------------------end------------------*/



    // update Loco Model after click on merge translation button
    function updateLocoModel() {

        var checkModal = setInterval(function () {
            var locoModel = $('.loco-modal');
            var locoModelApisBatch = $('.loco-modal #loco-apis-batch');

            if (locoModel.length && // model exists check
                locoModel.attr("style").indexOf("none") <= -1 && // has not display none
                locoModel.find('#loco-job-progress').length // element loaded 
            ) {

                $("html").removeClass("merge-translations");
                locoModelApisBatch.find("a.icon-help, a.icon-group, #loco-job-progress").hide();
                locoModelApisBatch.find("select#auto-api").hide();
                var currentState = $("select#auto-api option[value='loco_auto']").prop("selected", "selected");
                locoModelApisBatch.find("select#auto-api").val(currentState.val());
                locoModel.find(".ui-dialog-titlebar .ui-dialog-title").html("Step 3 - Add Translations into Editor and Save");
                locoModelApisBatch.find("button.button-primary span").html("Start Adding Process");

                locoModelApisBatch.find("button.button-primary").on("click", function () {
                    $(this).find('span').html("Adding...");
                });
                locoModel.addClass("addtranslations");
                $('.noapiadded').remove();
                locoModelApisBatch.find("form").show();
                locoModelApisBatch.removeClass("loco-alert");

                clearInterval(checkModal);
            }
        }, 200); // check every 200ms
    }

    // filter string based upon type
    function filterRawObject(rawArray, filterType) {
        filterdArr = [];
        return filterdArr = rawArray.filter((item, index) => {
            if ((item.source !== "" && item.source !== undefined) && (item.target === undefined || item.target == "")) {
                if (ValidURL(item.source)) {
                    return false;
                }
                if (isHTML(item.source)) {
                    return false;
                } else if (isPlacehodersChars(item.source)) {
                    return true;
                } else if (isSpecialChars(item.source)) {
                    return false;
                } else if (isEmoji(item.source)) {
                    return false;
                } else if (item.source.includes('#')) {
                    return false;
                } else {
                    return true;
                }
            }
        });
    }


    // detect String contain URL
    function ValidURL(str) {
        var pattern = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
        if (!pattern.test(str)) {
            return false;
        } else {
            return true;
        }
    }
    // detect Valid HTML in string
    function isHTML(str) {
        var rgex = /<(?=.*? .*?\/ ?>|br|hr|input|!--|wbr)[a-z]+.*?>|<([a-z]+).*?<\/\1>/i;
        if (str !== undefined) {
            return rgex.test(str);
        } else {
            return false;
        }
    }
    //  check special chars in string
    function isSpecialChars(str) {
        var rgex = /[@^{}�|<>]/g;
        if (str !== undefined) {
            return rgex.test(str);
        } else {
            return false;
        }
    }

    function isEmoji(str) {
        var ranges = [
            '(?:[\u2700-\u27bf]|(?:\ud83c[\udde6-\uddff]){2}|[\ud800-\udbff][\udc00-\udfff]|[\u0023-\u0039]\ufe0f?\u20e3|\u3299|\u3297|\u303d|\u3030|\u24c2|\ud83c[\udd70-\udd71]|\ud83c[\udd7e-\udd7f]|\ud83c\udd8e|\ud83c[\udd91-\udd9a]|\ud83c[\udde6-\uddff]|[\ud83c[\ude01-\ude02]|\ud83c\ude1a|\ud83c\ude2f|[\ud83c[\ude32-\ude3a]|[\ud83c[\ude50-\ude51]|\u203c|\u2049|[\u25aa-\u25ab]|\u25b6|\u25c0|[\u25fb-\u25fe]|\u00a9|\u00ae|\u2122|\u2139|\ud83c\udc04|[\u2600-\u26FF]|\u2b05|\u2b06|\u2b07|\u2b1b|\u2b1c|\u2b50|\u2b55|\u231a|\u231b|\u2328|\u23cf|[\u23e9-\u23f3]|[\u23f8-\u23fa]|\ud83c\udccf|\u2934|\u2935|[\u2190-\u21ff])' // U+1F680 to U+1F6FF
        ];
        if (str.match(ranges.join('|'))) {
            return true;
        } else {
            return false;
        }
    }
    // allowed special chars in HTML string
    function isAllowedChars(str) {
        var rgex = /[!@#$%^&*(),?":|<>]/g;
        if (str !== undefined) {
            return rgex.test(str);
        } else {
            return false;
        }
    }

    // allowed special chars in plain text
    function isPlacehodersChars(str) {
        var rgex = /%s|%d|$/g;
        if (str !== undefined) {
            return rgex.test(str);
        } else {
            return false;
        }
    }




    // replace placeholders in strings
    function strtr(s, p, r) {
        return !!s && {
            2: function () {
                for (var i in p) {
                    s = strtr(s, i, p[i]);
                }
                return s;
            },
            3: function () {
                return s.replace(RegExp(p, 'g'), r);
            },
            0: function () {
                return;
            }
        }[arguments.length]();
    }


    // all models

    // create auto translate settings popup
    function settingsModel() {
        let preloaderImg = extradata['preloader_path'];
        let ytPreviewImg = extradata['yt_preview'];
        let gtPreviewImg = extradata['gt_preview'];
        let dplPreviewImg = extradata['dpl_preview'];
        let modelHTML = ` 
  <!-- The Modal -->
  <div id="atlt-dialog" title="Step 1 - Select Translation Provider" style="display:none;">
  <div class="atlt-settings">
  
  <strong class="atlt-heading" style="margin-bottom:10px;display:inline-block;">Translate Using Yandex Page Translate Widget</strong>
  <div class="inputGroup">
  <button id="atlt_yandex_transate_btn" class="notranslate button button-primary">Yandex Translate</button>
  <br/><a href="https://translate.yandex.com/" target="_blank"><img style="margin-top: 5px;" src="${ytPreviewImg}" alt="powered by Yandex Translate Widget"></a>
  </div>
  <hr/>

  <strong class="atlt-heading" style="margin-bottom:10px;display:inline-block;">Translate Using Google Page Translate Widget</strong>
  <div class="inputGroup">
  <button id="atlt_gtranslate_btn" class="notranslate button button-primary">Google Translate</button>
  <br/><a href="https://translate.google.com/" target="_blank"><img style="margin-top: 5px;" src="${gtPreviewImg}" alt="powered by Google Translate Widget"></a>
  </div>
  <hr/>

  <strong class="atlt-heading" style="margin-bottom:10px;display:inline-block;">Translate Using Deepl Doc Translator</strong>
  <div class="inputGroup">
  <button id="atlt_deepl_btn" class="notranslate button button-primary">Deepl Translate</button>
  <br/><a href="https://www.deepl.com/en/translator" target="_blank"><img style="margin-top: 5px;" src="${dplPreviewImg}" alt="powered by DeepL Translate"></a>
  </div>
  <hr/>

  <ul style="margin: 0;">
  <li><span style="color:green">✔</span> Unlimited Translations</li>
  <li><span style="color:green">✔</span> No API Key Required</li>
  <li><span style="color:green">✔</span> Check Languages Support - <a href="https://yandex.com/support/translate/supported-langs.html" target="_blank">Yandex</a>, <a href="https://en.wikipedia.org/wiki/Google_Translate#Supported_languages" target="_blank">Google</a>, <a href="https://www.deepl.com/en/translator" target="_blank">DeepL</a></li>
  </ul>

  </div>
  </div>
`;
        $("body").append(modelHTML);
    }

    /**
     * generate model popup HTML
     */
    function createStringsPopup() {
        let modelHTML = ` 
    <!-- The Modal -->
    <div id="atlt_strings_model" class="modal atlt_custom_model">
        <!-- Modal content -->
        <div class="modal-content">
          <input type="hidden" id="project_id" value="${projectId}">
          <div class="modal-header">
            <span class="close ">&times;</span>
            <h2 class="notranslate">Step 2 - Start Automatic Translation Process</h2>
            <div class="save_btn_cont">
            <button class="notranslate save_it button button-primary" disabled="true">Merge Translation</button>
            </div>

            <div style="display:none" class="ytstats hidden">
              Wahooo! You have saved your valauble time via auto translating 
               <strong class="totalChars"> </strong> characters  using 
                <strong> 
                <a href="https://wordpress.org/support/plugin/automatic-translator-addon-for-loco-translate/reviews/#new-post" target="_new">
                Loco Automatic Translate Addon</a>
              </strong>     
            </div>

          </div>
            <div class="notice inline notice-info is-dismissible">Plugin will not translate any strings with HTML or special characters because Yandex Translator currently
             does not support HTML and special characters translations. 
            You can edit translated strings inside Loco Translate Editor after merging the translations. Only special chracters (%s, %d) fixed at the time of merging of the translations.</div>
            <div class="notice inline notice-info is-dismissible">Machine translations are not 100% correct.
             Please verify strings before using on production website.</div>
          <div class="modal-body">
            <div class="my_translate_progress">Automatic translation is in progress....<br/>It will take few minutes, enjoy ☕ coffee in this time!<br/><br/>Please do not leave this window or browser tab while translation is in progress...</div>
            <h3 class="choose-lang">Choose language <span class="dashicons-before dashicons-translation"></span></h3>
            <div id="ytWidget">..Loading</div>
            <br/>
            <div class="string_container">               
                <table class="scrolldown" id="stringTemplate">
                    <thead>
                    <th class="notranslate">S.No</th>
                    <th class="notranslate">Source Text</th>
                    <th class="notranslate">Translation</th>
                    </thead>
                    <tbody id="yandex_string_tbl">
                    </tbody>
                </table>
            </div>
            <div class="yandex-notice-container"></div>

          </div>
      <div class="modal-footer">
            <div class="save_btn_cont">
            <button class="notranslate save_it button button-primary" disabled="true">Merge Translation</button>
            </div>
            <div style="display:none" class="ytstats">
            Wahooo! You have saved your valauble time via auto translating 
               <strong class="totalChars"></strong> characters  using 
                <strong> 
                <a href="https://wordpress.org/support/plugin/automatic-translator-addon-for-loco-translate/reviews/#new-post" target="_new">
                Loco Automatic Translate Addon</a>
              </strong>     
            </div>
      </div>
        </div>
      </div>`;

        $("body").append(modelHTML);
    }


    /**
     * generate model popup HTML
     */
    function create_G_popup() {
        let modelHTML = ` 
    <!-- The Modal -->
    <div id="atlt_gtranslate_model" class="modal atlt_custom_model">
        <!-- Modal content -->
        <div class="modal-content">
        <input type="hidden" id="project_id" value="${projectId}">
          <div class="modal-header">
            <span class="close ">&times;</span>
            <h2 class="notranslate">Step 2 - Start Automatic Translation Process</h2>
            <div class="save_btn_cont">
            <button class="notranslate save_it button button-primary" disabled="true">Merge Translation</button>
            </div>
            <div style="display:none" class="gtstats">
            Wahooo! You have saved your valauble time via auto translating 
             <strong class="totalChars"> </strong> characters  using 
              <strong> 
              <a href="https://wordpress.org/support/plugin/automatic-translator-addon-for-loco-translate/reviews/#new-post" target="_new">
              Loco Automatic Translate Addon</a>
            </strong>     
          </div>
          </div>
            <div class="notice inline notice-info is-dismissible">Plugin will not translate any strings with HTML or special characters because Google Translate currently does not support HTML and special characters translations. You can edit translated strings inside Loco Translate Editor after merging the translations. Only special chracters (%s, %d) fixed at the time of merging of the translations.</div>
            <div class="notice inline notice-info is-dismissible">Machine translations are not 100% correct. Please verify strings before using on production website.</div>
          <div class="modal-body">
            <div class="my_translate_progress">Automatic translation is in progress....<br/>It will take few minutes, enjoy ☕ coffee in this time!<br/><br/>Please do not leave this window or browser tab while translation is in progress...</div>
            <h3 class="choose-lang">Choose language <span class="dashicons-before dashicons-translation"></span></h3>
            <div id="google_translate_element"></div>
            <div class="string_container">               
                <table class="scrolldown" id="stringTemplate">
                    <thead>
                    <th class="notranslate">S.No</th>
                    <th class="notranslate">Source Text</th>
                    <th class="notranslate">Translation</th>
                    </thead>
                    <tbody id="string_tbl">
                    </tbody>
                </table>
            </div>
            <div class="google-notice-container"></div>
          </div>
          <div class="modal-footer">
            <div class="save_btn_cont">
            <button class="notranslate save_it button button-primary" disabled="true">Merge Translation</button>
            </div>
            <div style="display:none" class="gtstats">
            Wahooo! You have saved your valauble time via auto translating 
             <strong class="totalChars"> </strong> characters  using 
              <strong> 
              <a href="https://wordpress.org/support/plugin/automatic-translator-addon-for-loco-translate/reviews/#new-post" target="_new">
              Loco Automatic Translate Addon</a>
            </strong>     
          </div>
          </div>
        </div>
      </div>`;
        $("body").append(modelHTML);
    }

    /**
     * generate model popup HTML
     */
    function create_deepl_popup() {
        let modelHTML = ` 
    <!-- The Modal -->
    <div id="atlt_deepl_model" class="modal atlt_custom_model">
        <!-- Modal content -->
        <div class="modal-content">
        <input type="hidden" id="project_id" value="${projectId}">
          <div class="modal-header">
            <span class="close_deepl close">&times;</span>
            <h2 class="notranslate">Step 2 - Start Automatic Translation Process</h2>
            <div class="save_btn_cont">
            <button class="notranslate deepl_save_it button button-primary" disabled="true">Merge Translation</button>
            </div>
            <div style="display:none" class="dtstats">
            Wahooo! You have saved your valauble time via auto translating 
             <strong class="totalChars"> </strong> characters  using 
              <strong> 
              <a href="https://wordpress.org/support/plugin/automatic-translator-addon-for-loco-translate/reviews/#new-post" target="_new">
              Loco Automatic Translate Addon</a>
            </strong>     
          </div>
          </div>
            <div class="notice inline notice-info is-dismissible">Plugin will not translate any strings with HTML or special characters. You can edit translated strings inside Loco Translate Editor after merging the translations. Only special chracters (%s, %d) fixed at the time of merging of the translations.</div>
            <div class="notice inline notice-info is-dismissible">Machine translations are not 100% correct. Please verify strings before using on production website.</div>
          <div class="modal-body">
            <table class="deepl_steps">
            <tr>
            <td>
              <h2>Step 1</h2>
              <p>Download translatable docx file.</p>
              <button  class="button button-primary" id="download_deepl_doc">Download Docx</button>
            </td>
            <td>
              <h2>Step 2</h2>
              <p>Visit <a href="https://www.deepl.com/translator" target="_blank">https://www.deepl.com/translator</a><br/>and upload downloaded file inside it<br/>for translations.</p>
            </td>
            </tr>
            <tr>
            <td>
              <h2>Step 3</h2>
              <p>Upload translated docx file here</p>
              <input type="file" id="deepl-open-file">
              <br />
              <span id="lblError" style="color: red;"></span>
              <br />
            </td>
            <td>
              <h2>Step 4</h2>
              <p>Click on "Upload Strings" & "Merge Translation" after this.</p>
              <input type="submit" id="deepl-btn-tpload" value="Upload Strings" />
            </td>
            </tr>
            </table>
        
            <div class="string_container">               
                <table class="scrolldown" id="deepl_stringTemplate">
                    <thead>
                    <th class="notranslate">S.No</th>
                    <th class="notranslate">Source Text</th>
                    <th class="notranslate">Translation</th>
                    </thead>
                    <tbody id="deepl_string_tbl">
                    </tbody>
                </table>
                <table  id="deepl-transtmplt">
                <thead>
                <th class="notranslate">S.No</th>
                <th>Source Text</th>
                <th >Target Text</th>
                </thead>
                <tbody id="deepbody">
                </tbody>
              </table>
            </div>
            <div class="deepl-notice-container"></div>
          </div>
          <div class="modal-footer">
            <div class="save_btn_cont">
            <button class="notranslate deepl_save_it button button-primary" disabled="true">Merge Translation</button>
            </div>
            <div style="display:none" class="dtstats">
            Wahooo! You have saved your valauble time via auto translating 
             <strong class="totalChars"> </strong> characters  using 
              <strong> 
              <a href="https://wordpress.org/support/plugin/automatic-translator-addon-for-loco-translate/reviews/#new-post" target="_new">
              Loco Automatic Translate Addon</a>
            </strong>     
          </div>  
          </div>
        </div>
      </div>`;
        $("body").append(modelHTML);
    }


}(window, jQuery);