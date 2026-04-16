var scorm = pipwerks.SCORM;
console.log("first")
console.log(scorm)

function init() {
    scorm.init();
}

function set(param, value) {
    scorm.set(param, value);
}

function get(param) {
    scorm.get(param);
}

function end() {
    scorm.quit();
}

window.onload = function () {
    init();
}

window.onunload = function () {
    end();
}

/* Need to way to do for each SCORM version */
var onCompleted = function (result) {
    var masteryScore;
    console.log("Scorm version is => " + scorm.version)

    if (scorm.version == "2004")
        masteryScore = scorm.get("cmi.scaled_passing_score");
    else if (scorm.version == "1.2")
        masteryScore = scorm.get("cmi.student_data.mastery_score") / 100;
    console.log("Mastery score is ", masteryScore)

    if (result.hasOwnProperty("score") && result.score.hasOwnProperty("scaled") && scorm.version == "2004")
        scorm.set("cmi.score.raw", '' + (result.score.scaled * 100) + '');
    else if (result.hasOwnProperty("score") && result.score.hasOwnProperty("scaled"))
        scorm.set("cmi.core.score.raw", result.score.scaled * 100);

    if (scorm.version == "2004") {
        scorm.set("cmi.score.min", '' + 0 + '');
        scorm.set("cmi.score.max", '' + 100 + '');
    } else {
        scorm.set("cmi.core.score.min", 0);
        scorm.set("cmi.core.score.max", 100);
    }

    if (result.hasOwnProperty("score") && result.score.hasOwnProperty("scaled") && scorm.version == "2004")
        scorm.set("cmi.score.scaled", '' + result.score.scaled + '');
    else if (result.hasOwnProperty("score") && result.score.hasOwnProperty("scaled"))
        scorm.set("cmi.core.score.scaled", result.score.scaled * 100);

    if (masteryScore === undefined) 
        scorm.status("set", "completed"); 
    else {
        var noScore = masteryScore < 0 ? true : false
        console.log("No score is", noScore)

        if (result.hasOwnProperty("score") && result.score.hasOwnProperty("scaled"))
            var passed = result.score.scaled >= masteryScore;

        if (scorm.version == "2004") {
            scorm.status("set", "completed");
            if (passed) {
                scorm.set("cmi.success_status", "passed");
            }
            else if (noScore == true) {
                scorm.set("cmi.score.raw", '' + 100 + '');
                scorm.set("cmi.score.scaled", '' + 1 + '');
                scorm.set("cmi.success_status", "passed");
            } else {
                scorm.set("cmi.success_status", "failed");
            }
        }
        else if (scorm.version == "1.2") {
            if (passed) {
                scorm.status("set", "passed")
            }
            else if (noScore == true) {
                scorm.set("cmi.core.score.raw", 100);
                scorm.set("cmi.core.score.scaled", 100);
                scorm.status("set", "passed")
            } else {
                scorm.status("set", "failed")
            }
        }
    }
}

/* Call when event trigger (input, button click, exercice end) */
H5P.externalDispatcher.on('xAPI', function (event) {
    console.log('xAPI event: ' + JSON.stringify(event));
    if (event.data.statement.result) {
        onCompleted(event.data.statement.result);
    }
});