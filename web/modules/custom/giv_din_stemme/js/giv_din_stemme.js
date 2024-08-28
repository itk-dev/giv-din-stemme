// https://github.com/chrisguttandin/extendable-media-recorder

import { MediaRecorder, register } from "extendable-media-recorder";
import { connect } from "extendable-media-recorder-wav-encoder";

await register(await connect());

(async () => {
  let volumeCallback = null;
  let volumeInterval = null;
  const volumeVisualizer = document.getElementById("btn-microphone-toggle");
  const toggleButton = document.getElementById("btn-microphone-toggle");
  const soundClips = document.querySelector(".sound-clips");
  const submitButton = document.querySelector("button[value='continue']");
  // If we don't have a finish button, we just use an object that we can set `disabled` on. It's a hack!
  const finishButton = document.querySelector("button[value='finish']") ?? {};
  const fileElement = document.querySelector("#audio_input");
  const durationElement = document.querySelector("#recording_duration");

  function hideElement(element) {
    element.classList.add("hidden");
    element.setAttribute("aria-hidden", true);
  }

  function showElement(element) {
    element.classList.remove("hidden");
    element.setAttribute("aria-hidden", false);
  }

  // Initialize
  try {
    const audioStream = await navigator.mediaDevices.getUserMedia({
      audio: {
        echoCancellation: true,
      },
    });
    const audioContext = new AudioContext();
    const audioSource = audioContext.createMediaStreamSource(audioStream);
    const analyser = audioContext.createAnalyser();
    analyser.fftSize = 512;
    analyser.minDecibels = -127;
    analyser.maxDecibels = 0;
    analyser.smoothingTimeConstant = 0.4;
    audioSource.connect(analyser);
    const volumes = new Uint8Array(analyser.frequencyBinCount);

    volumeCallback = () => {
      analyser.getByteFrequencyData(volumes);
      let volumeSum = 0;
      for (const volume of volumes) volumeSum += volume;
      const averageVolume = volumeSum / volumes.length;
      // Value range: 127 = analyser.maxDecibels - analyser.minDecibels;
      volumeVisualizer.style.setProperty(
        "--volume",
        Math.ceil((averageVolume * 100) / 127) + "px",
      );
    };
  } catch (e) {
    console.error("Failed to initialize volume visualizer.", e);
  }

  // Disable next button while not recording
  submitButton.disabled = finishButton.disabled = true;

  // Main block for doing the audio recording
  if (navigator.mediaDevices.getUserMedia) {
    const constraints = { audio: true };
    let chunks = [];
    let startTime;
    let endTime;
    let durationChecker;

    let onSuccess = function (stream) {
      const mediaRecorder = new MediaRecorder(stream, {
        mimeType: "audio/wav",
      });

      durationChecker = () => {
        // Check if recording is still going.
        if (isRecording()) {
          toggleButton.click();
        }
      };

      function isRecording() {
        return toggleButton.classList.contains("active");
      }

      function stopRecording() {
        mediaRecorder.stop();
        endTime = Date.now();
        toggleButton.classList.remove("active");

        if (volumeInterval !== null) {
          clearInterval(volumeInterval);
          volumeInterval = null;
        }
      }

      function startRecording() {
        toggleButton.classList.add("active");
        startTime = Date.now();
        mediaRecorder.start();

        if (volumeCallback !== null && volumeInterval === null) {
          volumeInterval = setInterval(volumeCallback, 100);
        }

        setTimeout(durationChecker, 60 * 1000)
      }

      toggleButton.addEventListener("click", () => {
        if (isRecording()){
          stopRecording()
        } else {
          startRecording()
        }
      });

      mediaRecorder.onstop = function () {
        const audio = soundClips.querySelector("audio");
        const deleteButton = soundClips.querySelector("button");

        showElement(soundClips);

        const blob = new Blob(chunks, { type: "audio/wav" });
        chunks = [];

        audio.src = window.URL.createObjectURL(blob);

        // Convert blob to file and attach to file element.
        let file = new File([blob], "audio_recording.wav", {
          type: blob.type,
          lastModified: new Date().getTime(),
        });
        let container = new DataTransfer();
        container.items.add(file);
        fileElement.files = container.files;

        // Set duration time
        durationElement.value = Math.round((endTime - startTime) / 1000);

        deleteButton.onclick = () => {
          fileElement.files = new DataTransfer().files;
          startTime = null;
          endTime = null;
          submitButton.disabled = finishButton.disabled = true;
          toggleButton.disabled = false;
          hideElement(soundClips);
        };

        submitButton.disabled = finishButton.disabled = false;
        toggleButton.disabled = true;
      };

      mediaRecorder.ondataavailable = function (e) {
        chunks.push(e.data);
      };
    };

    navigator.mediaDevices.getUserMedia(constraints).then(onSuccess);
  }
})();
