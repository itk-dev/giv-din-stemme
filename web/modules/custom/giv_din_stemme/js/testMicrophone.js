(async () => {
  let volumeCallback = null;
  let volumeInterval = null;
  const volumeVisualizer = document.getElementById('btn-microphone-toggle');
  const toggleButton = document.getElementById('btn-microphone-toggle');

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
  // Use
  toggleButton.addEventListener('click', () => {
    if (toggleButton.classList.contains('active')) {
      if (volumeInterval !== null) {
        clearInterval(volumeInterval);
        volumeInterval = null;
      }
      toggleButton.classList.remove('active')
    }
    else {
      toggleButton.classList.add('active');
      if (volumeCallback !== null && volumeInterval === null) {
        volumeInterval = setInterval(volumeCallback, 100);
      }
    }
  });
    // Updating every 100ms (should be same as CSS transition speed)
})();