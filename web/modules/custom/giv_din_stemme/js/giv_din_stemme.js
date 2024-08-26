(async () => {

  let volumeCallback = null;
  let volumeInterval = null;
  const volumeVisualizer = document.getElementById('btn-microphone-toggle');
  const toggleButton = document.getElementById('btn-microphone-toggle');
  const soundClips = document.querySelector(".sound-clips");
  const submitButton = document.querySelector("#read_submit_button");
  const fileElement = document.querySelector("#audio_input");
  const durationElement = document.querySelector("#recording_duration");

  // Initialize
  try {
    const audioStream = await navigator.mediaDevices.getUserMedia({
      audio: {
        echoCancellation: true
      }
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
      for(const volume of volumes)
        volumeSum += volume;
      const averageVolume = volumeSum / volumes.length;
      // Value range: 127 = analyser.maxDecibels - analyser.minDecibels;
      volumeVisualizer.style.setProperty('--volume', Math.ceil((averageVolume * 100 / 127)) + 'px');
    };
  } catch(e) {
    console.error('Failed to initialize volume visualizer.', e);
  }

  // Disable next button while not recording
  submitButton.disabled = true;

  // Main block for doing the audio recording
  if (navigator.mediaDevices.getUserMedia) {
    const constraints = { audio: true };
    let chunks = [];
    let startTime;
    let endTime;

    let onSuccess = function (stream) {
      const mediaRecorder = new MediaRecorder(stream);

      // visualize(stream);

      toggleButton.addEventListener('click', () => {
        if (toggleButton.classList.contains('active')) {
          if (volumeInterval !== null) {
            clearInterval(volumeInterval);
            volumeInterval = null;
          }
          mediaRecorder.stop();
          endTime = Date.now();
          toggleButton.classList.remove('active')
        }
        else {
          toggleButton.classList.add('active');
          startTime = Date.now();
          mediaRecorder.start();
          if (volumeCallback !== null && volumeInterval === null) {
            volumeInterval = setInterval(volumeCallback, 100);
          }
        }
      });

      mediaRecorder.onstop = function (e) {
        const clipContainer = document.createElement("article");
        // TODO: Do we use this clipLabel?
        const clipLabel = document.createElement("p");
        const audio = document.createElement("audio");
        const deleteButton = document.createElement("button");

        soundClips.classList.remove('hidden');

        clipContainer.classList.add("clip", "flex", "gap-2", "border-t", "my-1", "py-2");
        deleteButton.classList.add("btn-danger", "self-center");
        audio.setAttribute("controls", "");
        // TODO: This should be translateable
        deleteButton.textContent = "Delete";

        clipContainer.appendChild(audio);
        clipContainer.appendChild(clipLabel);
        clipContainer.appendChild(deleteButton);
        soundClips.appendChild(clipContainer);

        const blob = new Blob(chunks, { type: "audio/mp3" });
        chunks = [];

        audio.src = window.URL.createObjectURL(blob);

        // Convert blob to file and attach to file element.
        let file = new File([blob], "audio_recording.mp3", {type:blob.type, lastModified:new Date().getTime()});
        let container = new DataTransfer();
        container.items.add(file);
        fileElement.files = container.files;

        // Set duration time
        durationElement.value = Math.round((endTime - startTime) / 1000);

        deleteButton.onclick = (e) => {
          let evtTgt = e.target;
          evtTgt.parentNode.parentNode.removeChild(evtTgt.parentNode);
          fileElement.files = new DataTransfer().files;
          startTime = null;
          endTime = null;
          submitButton.disabled = true;
          toggleButton.disabled = false;
          soundClips.classList.add('hidden');
        };

        submitButton.disabled = false;
        toggleButton.disabled = true;
      };

      mediaRecorder.ondataavailable = function (e) {
        chunks.push(e.data);
      };
    };

    navigator.mediaDevices.getUserMedia(constraints).then(onSuccess);
  }


})();

