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

  continueButton.classList.add('disabled');
  navigator.mediaDevices

  .getUserMedia({ video: false, audio: true })
  .then((stream) => {
    window.localStream = stream;
    continueButton.classList.remove('disabled', 'pointer-events-none');
    microphoneAllowedDisplay.classList.remove('hidden');
    microphoneDisallowedDisplay.classList.add('hidden');
    helpText.classList.add('hidden');
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
