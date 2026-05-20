var Launcher = (function ($) {
  var studentId,
    studentName,
    hostName,
    lmsName,
    lmsLocale,
    scenarioKey,
    integrationKey,
    warpLauncher,
    warpEndpoint

  var session, updateTimerStarted, updateTimer, resultSent

  function handleSessionResponse(newSession) {
    session = newSession

    lmsName = session.lms_name

    $('.lms_name').html(lmsName)
    $('#scenario').html(session.scenario_name)
    $('#image').css(
      'background-image',
      "url('" + session.scenario_image_url + "')"
    )
    $('#logo').attr('src', session.client_image_url)

    // console.log('handleSessionResponse', session);

    handleStateChange(session.state)
  }

  function startUpdateTimer() {
    if (!updateTimerStarted) {
      setTimeout(function () {
        setUpdateTimer()
      }, 5000)
      updateTimerStarted = true
    }
  }

  function setUpdateTimer() {
    updateTimer = setInterval(function () {
      $.getJSON(warpEndpoint, {
        scorm_action: 'show',
        session_id: session.id,
        session_verification_code: session.verification_code,
      })
        .done(function (newSession) {
          handleSessionResponse(newSession)
        })
        .fail(function (error) {
          handleFailure(error)
        })
    }, 5000)

    setTimeout(function () {
      disableUpdateTimer()
    }, 3600000)
  }

  function disableUpdateTimer() {
    if (updateTimer !== undefined) {
      clearInterval(updateTimer)
      updateTimerStarted = false
    }
  }

  function handleExpired() {
    $('#step_expired').show()
    $('#step_loading').hide()
    $('#step_closed').hide()

    $('#step1').hide()
    $('#step1_started').hide()
    $('#step2').hide()
  }

  function handleClosed() {
    $('#step_expired').hide()
    $('#step_loading').hide()
    $('#step_closed').show()

    $('#step1').hide()
    $('#step1_started').hide()
    $('#step2').hide()
  }

  function handleFailure(error) {
    $('#step_error').show()
    $('#step_loading').hide()
    console.log(error)
  }

  function toggleDevice(device) {
    if (device === 'smartphone') {
      $('#device_smartphone').addClass('device__choice--active')
      $('#device_headset').removeClass('device__choice--active')
    } else {
      $('#device_smartphone').removeClass('device__choice--active')
      $('#device_headset').addClass('device__choice--active')
    }
  }

  function disableStep5() {
    $('#step1_started').hide()
    $('#step5_incomplete').hide()
    $('#step5_complete').show()
  }

  function showStep6() {
    $('#step6_incomplete').show()
  }

  function hideStep6() {
    $('#step6_incomplete').hide()
  }

  function makeResultRow() {
    var attempts = session.attempts
    results_container.innerHTML = ''
    var max_index = 0
    attempts.forEach((item, index) => {
      var stars = item.stars
      var row = result_template.content.querySelector('article').cloneNode(true)
      row.querySelector('.step_results_list').innerHTML = index + 1

      if (item.finished) {
        var starsEl = row.querySelector('.stars')
        if (stars !== null) {
          for (var i = 1; i <= 5; i++) {
            if (i <= stars) {
              starsEl.children[i - 1].classList.remove('bi-star')
              starsEl.children[i - 1].classList.add('bi-star-fill')
            }
          }
          if (stars > attempts[max_index].stars) max_index = index
          starsEl.hidden = false
        }
      }
      results_container.appendChild(row)
    })

    results_container.setAttribute('data-attempts', attempts.length)
    if (attempts.length > 1) {
      results_container
        .querySelector(`article:nth-child(${max_index + 1})`)
        .classList.add('has-best')
    }
  }

  function handleStateChange(newState) {
    $('#step_error').hide()
    $('#step_loading').hide()
    $('#step_expired').hide()
    $('#step_closed').hide()

    switch (newState) {
      case 'provisioned':
      case 'confirmation_required':
        $('#step1').show()
        $('#step1_play').show()
        $('#need-help').show()
        $('#step1_started').hide()

        $('#step2').show()
        $('#step2_waiting').show()
        $('#step2_results').hide()
        $('#step2_send_results').hide()
        $('#step2_results_received').hide()

        $('.code').html(
          `${session.code.slice(
            0,
            3
          )}<span class="user-select-none">-</span>${session.code.slice(3)}`
        )

        $('#step1_play > a').attr(
          'href',
          warpLauncher + session.verification_code
        )

        storeSession()
        startUpdateTimer()
        break
      case 'confirmed':
      case 'started':
        $('#step1_play').hide()
        $('#need-help').hide()
        $('#step1_started').show()
        $('#step2').show()

        $('#step2_waiting').show()
        $('#step2_results').hide()
        $('#step2_send_results').hide()
        $('#step2_results_received').hide()

        storeSession()
        break
      case 'ended':
      case 'completed':
        $('#step1').hide()
        $('#step1_started').show()
        $('#step2').show()

        $('#step2_waiting').hide()
        $('#step2_results').show()
        $('#step2_results_header').show()
        makeResultRow()

        $('#step2_send_results').hide()
        $('#step2_results_received').hide()

        if (resultSent) {
          $('#step2_send_results').hide()
          $('#step2_results_received').show()
          $('#step1_started').hide()
          $('#step2_results_header').hide()
          $('#step2_header').hide()
          disableUpdateTimer()
          results_container.classList.add('send')
        } else {
          $('#step2_send_results').show()
          $('#step2_header').show()
          $('#step2_results_header').show()
          $('#step2_results_received').hide()
          results_container.classList.remove('send')
        }
        sendScore()
        break
      case 'closed':
        handleClosed()
        clearSession()
        disableUpdateTimer()
        break
      case 'failed':
        handleFailure('session failed')
        clearSession()
        disableUpdateTimer()
        break
      case 'expired':
        handleExpired()
        clearSession()
        disableUpdateTimer()
        break
      default:
        console.log('Unknown state', newState)
    }
  }

  function clearSession() {
    if (ScormEnabled()) {
      ScormProcessSetValue('cmi.suspend_data', '')
      ScormProcessSetValue('cmi.core.exit', '')

      ScormProcessCommit()
    }
  }

  function sendScore() {
    var score = session.stars * 20
    var completion_score = session.stars_for_completion * 20

    console.log('Reporting score', score)

    if (ScormEnabled()) {
      ScormProcessSetValue('cmi.core.score.raw', score)
      ScormProcessSetValue('cmi.core.score.min', '0')
      ScormProcessSetValue('cmi.core.score.max', '100')

      // if we get a test result, set the lesson status to passed/failed instead of completed
      if (score >= completion_score) {
        ScormProcessSetValue('cmi.core.lesson_status', 'passed')
      } else if (score >= 20) {
        ScormProcessSetValue('cmi.core.lesson_status', 'failed')
      } else {
        // for results without a score, only set completed
        ScormProcessSetValue('cmi.core.lesson_status', 'completed')
      }

      ScormProcessSetValue('cmi.suspend_data', '')
      ScormProcessSetValue('cmi.core.exit', '')

      ScormProcessCommit()
    }
  }

  function getVariables() {
    resultSent = false

    scenarioKey = '62bffec14869f8ee8a1aa34eb96bb968'
    integrationKey = 'd05f1e93e49454373fd96eb80a7045ad'
    warpLauncher = 'https://app.warp.studio/play/'
    warpEndpoint = 'https://app.warp.studio/scorm?callback=?'

    if (ScormEnabled()) {
      // it's a best practice to set the lesson status to incomplete when
      // first launching the course (if the course is not already completed)
      var completionStatus = ScormProcessGetValue('cmi.core.lesson_status')
      if (completionStatus == 'not attempted') {
        ScormProcessSetValue('cmi.core.lesson_status', 'incomplete')
      }

      // set suspend status until we have a score
      ScormProcessSetValue('cmi.core.exit', 'suspend')

      // studentId
      studentId = ScormProcessGetValue('cmi.core.student_id')
      studentName = ScormProcessGetValue('cmi.core.student_name')
      lmsLocale = ScormProcessGetValue('cmi.student_preference.language')
      hostName = window.location.hostname

      if (hostName.length === 0) {
        hostName = 'lms.com'
      }

      ScormProcessCommit()
    } else {
      studentId = '12345'
      studentName = 'Scorm'
      hostName = 'lms.com'
      lmsLocale = 'de'
      scenarioKey = '8e4fb7aae53c0faad3f9c419a9e5dfeb'
      integrationKey = '9f65e801bd11cfa06c294eb6274e5bc5'
      warpLauncher = 'http://localhost:3000/play/'
      warpEndpoint = 'http://localhost:3000/scorm?callback=?'
    }
  }

  function storeSession() {
    if (ScormEnabled()) {
      ScormProcessSetValue('cmi.suspend_data', JSON.stringify(session))
      ScormProcessCommit()
    }
  }

  function setResponsiveness() {
    if (window != window.parent) {
      var link = document.createElement('meta')
      link.setAttribute('name', 'viewport')
      link.content = 'width=device-width, initial-scale=1'
      window.parent.document.getElementsByTagName('head')[0].appendChild(link)
    }
  }

  function toggleLocale(locale) {
    $('[data-i18n-key]').each(function () {
      var e = $(this)
      var k = e.attr('data-i18n-key')
      var l = Translations[locale][k]
      if (l !== undefined) {
        if (e.is('a')) {
          $(this).attr('href', l)
        } else {
          $(this).html(l)
        }
      }
    })

    $('.lms_name').html(lmsName)

    document.documentElement.lang = locale
    const langListEl = document.querySelector('#langs-list')
    langListEl.querySelector('.selected').classList.remove('selected')
    const selectedLangEl = langListEl.querySelector(
      `li[data-lang-key='${locale}']`
    )
    selectedLangEl.classList.add('selected')
    document.querySelector('#selected-lang').innerHTML =
      selectedLangEl.textContent
  }

  function defaultLocale() {
    var availableLocales = Object.keys(Translations)
    var preferredLocales = [lmsLocale, navigator.language]
      .concat(navigator.languages)
      .concat('en')

    var preferredLocale
    $.each(preferredLocales, function (i, v) {
      if (v !== undefined) {
        var simpleLocale = v.split('-')[0]
        if ($.inArray(simpleLocale, availableLocales) >= 0) {
          preferredLocale = simpleLocale
          return false
        }
      }
    })

    return preferredLocale
  }

  function createNewSession() {
    $.getJSON(warpEndpoint, {
      scorm_action: 'create',
      student_id: studentId,
      student_name: studentName,
      lms_hostname: hostName,
      scenario_key: scenarioKey,
      integration_key: integrationKey,
    })
      .done(function (newSession) {
        handleSessionResponse(newSession)
      })
      .fail(function (error) {
        handleFailure(error)
      })
  }

  function getExistingSession() {
    if (ScormEnabled()) {
      var suspended = ScormProcessGetValue('cmi.suspend_data')
      if (suspended !== '') {
        handleSessionResponse(JSON.parse(suspended))
        if (
          session.state === 'failed' ||
          session.state === 'expired' ||
          session.state === 'closed'
        ) {
          return false
        } else {
          setUpdateTimer()
          return true
        }
      }
    }

    return false
  }

  function sendSessionEvent(event) {
    $.getJSON(warpEndpoint, {
      scorm_action: 'update',
      session_id: session.id,
      session_verification_code: session.verification_code,
      session_event: event,
    })
      .done(function (updatedSession) {
        handleSessionResponse(updatedSession)
      })
      .fail(function (error) {
        handleFailure(error)
      })
  }

  function confirm() {
    sendSessionEvent('confirm')
  }

  function reset() {
    resultSent = false
    disableUpdateTimer()
    sendSessionEvent('reset')
  }

  function complete() {
    resultSent = true
    sendSessionEvent('close')
    disableStep5()
    showStep6()
  }

  function load() {
    ScormProcessInitialize()

    setResponsiveness()

    getVariables()

    toggleLocale(defaultLocale())

    if (!getExistingSession()) {
      createNewSession()
    }
  }

  function unload() {
    disableUpdateTimer()

    if (ScormEnabled()) {
      ScormProcessSetValue('cmi.core.exit', '')
      ScormProcessFinish()
    }
  }

  return {
    load: load,
    unload: unload,
    confirm: confirm,
    reset: reset,
    complete: complete,
    toggleDevice: toggleDevice,
    toggleLocale: toggleLocale,
    defaultLocale: defaultLocale,
  }
})(jQuery)
