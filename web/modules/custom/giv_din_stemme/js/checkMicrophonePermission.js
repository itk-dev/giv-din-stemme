/**
 * Gets the local audio stream of the current caller
 * @param callbacks - an object to set the success/error behavior
 * @returns {void}
 */

function getLocalStream() {
  const continueButton = document.getElementById('continue-button');
  const microphoneAllowedDisplay = document.getElementById('microphone-allowed-display');
  const microphoneDisallowedDisplay = document.getElementById('microphone-disallowed-display');
  const helpText = document.getElementById('microphone-help-text');

  navigator.mediaDevices
    .getUserMedia({ video: false, audio: true })
    .then((stream) => {
      window.localStream = stream;

      continueButton.querySelector('.button').classList.remove('btn-disabled', 'pointer-events-none');
      continueButton.querySelector('.button').classList.add('btn-default');

      microphoneAllowedDisplay.classList.remove('hidden');
      microphoneAllowedDisplay.classList.add('table');

      microphoneDisallowedDisplay.classList.add('hidden');
    })
    .catch((err) => {
      helpText.classList.add('animate-wiggle')
      delay(1000).then(() => helpText.classList.remove('animate-wiggle'));
    });
}

function delay(time) {
  return new Promise(resolve => setTimeout(resolve, time));
}

getLocalStream();
