<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="robots" content="noindex, nofollow">
  <meta name="googlebot" content="noindex, nofollow">
  <title>Crosspoint YouTube Downloader</title>
  <link rel="stylesheet" href="style.css?v=<?php echo rand(1000,9999); ?>">
  <style>
    .logo-container {
      text-align: center;
      margin: 20px auto;
      width: 200px;
    }
    .logo-container img {
      width: 200px;
      height: auto;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Crosspoint YouTube Downloader</h1>
    <form id="downloadForm">
      <label for="youtube_url">YouTube URLs (maximum 5 URLs, one per line):</label>
      <textarea id="youtube_url" name="youtube_url" rows="5" placeholder="Enter YouTube links here (maximum 5 URLs, one per line)..." required></textarea>
      <small class="url-counter">0/5 URLs</small>

      <label for="format">Choose format:</label>
      <select name="format" id="format">
        <option value="mp4">MP4 (Video)</option>
        <option value="mp3">MP3 (Audio)</option>
      </select>

      <div class="button-container">
        <button type="submit" id="downloadButton">Download All</button>
        <button type="button" id="abortButton" class="abort-button" style="display: none;">Cancel Downloads</button>
      </div>
    </form>

    <div id="output" class="output-box"></div>
    
  </div>
  <h3 class="disclaimer">For internal use only. Do not share this link.</h3>
  <div class="logo-container">
    <img src="images/logo.png" alt="Crosspoint Church Logo">
  </div>
  <script>
    let abortController = null;
    let downloadAborted = false;
    const textarea = document.getElementById('youtube_url');
    const urlCounter = document.querySelector('.url-counter');
    const downloadButton = document.getElementById('downloadButton');
    const abortButton = document.getElementById('abortButton');
    const MAX_URLS = 5;

    function toggleAbortButton(show) {
      abortButton.style.display = show ? 'block' : 'none';
      if (show) {
        abortController = new AbortController();
        downloadAborted = false;
      } else {
        abortController = null;
      }
    }

    abortButton.addEventListener('click', function() {
      if (abortController) {
        downloadAborted = true;
        abortController.abort();
        toggleAbortButton(false);
        downloadButton.disabled = false;
        downloadButton.textContent = 'Download All';
        downloadButton.classList.remove('disabled');
        document.getElementById('progress').innerHTML += '<p class="error">Downloads cancelled by user.</p>';
      }
    });

    function updateUrlCounter() {
      const urls = textarea.value.split('\n').filter(url => url.trim());
      urlCounter.textContent = `${urls.length}/${MAX_URLS} URLs`;
      
      if (urls.length > MAX_URLS) {
        urlCounter.classList.add('error');
      } else {
        urlCounter.classList.remove('error');
      }
    }

    textarea.addEventListener('input', function(e) {
      let urls = this.value.split('\n').filter(url => url.trim());
      
      if (urls.length > MAX_URLS) {
        urls = urls.slice(0, MAX_URLS);
        this.value = urls.join('\n');
      }
      
      updateUrlCounter();
    });

    textarea.addEventListener('paste', function(e) {
      const pastedText = e.clipboardData.getData('text');
      const urls = pastedText.split('\n').filter(url => url.trim());
      
      if (urls.length > MAX_URLS) {
        e.preventDefault();
        const limitedUrls = urls.slice(0, MAX_URLS);
        this.value = limitedUrls.join('\n');
        updateUrlCounter();
      }
    });

    async function downloadURL(url, format) {
      try {
        const formData = new FormData();
        formData.append('youtube_url', url.trim());
        formData.append('format', format);

        const response = await fetch('download.php', {
          method: 'POST',
          body: formData,
          signal: abortController ? abortController.signal : null
        });
        
        const data = await response.json();
        if (data.status === 'success') {
          const link = document.createElement('a');
          link.href = data.download_url;
          link.click();
          return { success: true, message: `Successfully downloaded: ${url}` };
        } else {
          return { success: false, message: `Failed to download ${url}: ${data.message}` };
        }
      } catch (error) {
        if (error.name === 'AbortError') {
          throw new Error('Download cancelled');
        }
        throw error;
      }
    }

    document.getElementById('downloadForm').addEventListener('submit', async function (e) {
      e.preventDefault();

      const urls = document.getElementById('youtube_url').value.split('\n').filter(url => url.trim());
      const format = document.getElementById('format').value;
      const outputBox = document.getElementById('output');
      
      if (urls.length === 0) {
        outputBox.innerHTML = '<p class="error">Please enter at least one URL</p>';
        return;
      }

      if (urls.length > MAX_URLS) {
        outputBox.innerHTML = '<p class="error">Maximum 5 URLs allowed. Please remove some URLs and try again.</p>';
        return;
      }

      downloadButton.disabled = true;
      downloadButton.textContent = 'Downloading...';
      downloadButton.classList.add('disabled');
      toggleAbortButton(true);

      outputBox.innerHTML = '<div id="progress"></div>';
      const progressDiv = document.getElementById('progress');
      let hasErrors = false;

      try {
        for (let i = 0; i < urls.length; i++) {
          if (downloadAborted) break;

          const url = urls[i].trim();
          if (!url) continue;

          const videoTitle = await fetch(`https://noembed.com/embed?url=${encodeURIComponent(url)}`, {
            signal: abortController ? abortController.signal : null
          })
            .then(response => response.json())
            .then(data => data.title || url)
            .catch(() => url);
          progressDiv.innerHTML += `<p>Processing ${i + 1}/${urls.length}: ${videoTitle}</p>`;
          
          try {
            const result = await downloadURL(url, format);
            if (!result.success) hasErrors = true;
            progressDiv.innerHTML += `<p class="${result.success ? 'success' : 'error'}">${result.message}</p>`;
          } catch (error) {
            if (error.message === 'Download cancelled') {
              break;
            }
            hasErrors = true;
            progressDiv.innerHTML += `<p class="error">Error processing ${url}: ${error.message}</p>`;
          }
        }

        if (!downloadAborted) {
          if (hasErrors) {
            progressDiv.innerHTML += '<p class="error">Downloads completed with errors. Please check the messages above.</p>';
          } else {
            progressDiv.innerHTML += '<p class="success">All downloads completed successfully! Please check your Downloads folder.</p>';
          }
        }
      } catch (error) {
        if (error.name !== 'AbortError') {
          progressDiv.innerHTML += `<p class="error">Unexpected error: ${error.message}</p>`;
        }
      } finally {
        downloadButton.disabled = false;
        downloadButton.textContent = 'Download All';
        downloadButton.classList.remove('disabled');
        toggleAbortButton(false);
        downloadAborted = false;
      }
    });
  </script>
</body>
</html>
