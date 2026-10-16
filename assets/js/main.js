/**
 * ElearnMath - Main Frontend Script
 * Interaksi Lonceng Notifikasi, Modal Konfirmasi, AI Scoring, Countdown Timer,
 * Deteksi Keluar Tab Ujian, dan Pengambilan Foto Kamera Webcam
 */

document.addEventListener('DOMContentLoaded', function () {
  // 1. FITUR C: NOTIFIKASI LONCENG INTERAKTIF & HILANGKAN TITIK MERAH
  const bellBtn = document.getElementById('bellNotifBtn');
  const notifDropdown = document.getElementById('notifDropdown');
  const redDotBadge = document.getElementById('redDotBadge');

  if (bellBtn && notifDropdown) {
    bellBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      const isOpen = notifDropdown.classList.contains('show');

      if (!isOpen) {
        notifDropdown.classList.add('show');

        // Jika ada titik merah (unread), lakukan update ke database via AJAX
        if (redDotBadge && redDotBadge.style.display !== 'none') {
          fetch(window.APP_BASE_URL + '/api/notifications.php?action=mark_read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
          })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                redDotBadge.style.opacity = '0';
                setTimeout(() => {
                  redDotBadge.style.display = 'none';
                }, 300);

                document.querySelectorAll('.notif-item.unread').forEach(item => {
                  item.classList.remove('unread');
                });
              }
            })
            .catch(err => console.error('Gagal update notifikasi:', err));
        }
      } else {
        notifDropdown.classList.remove('show');
      }
    });

    document.addEventListener('click', function (e) {
      if (!notifDropdown.contains(e.target) && !bellBtn.contains(e.target)) {
        notifDropdown.classList.remove('show');
      }
    });
  }

  // 2. FITUR D: KONFIRMASI PENGUMPULAN SOAL (MODAL POP-UP)
  const assessmentForm = document.getElementById('assessmentForm');
  const btnTriggerSubmit = document.getElementById('btnTriggerSubmit');
  const submitConfirmModal = document.getElementById('submitConfirmModal');
  const btnConfirmYes = document.getElementById('btnConfirmYes');
  const btnConfirmCancel = document.getElementById('btnConfirmCancel');

  if (assessmentForm && btnTriggerSubmit && submitConfirmModal) {
    btnTriggerSubmit.addEventListener('click', function (e) {
      e.preventDefault();

      const unanswered = checkUnansweredQuestions();
      const modalDesc = submitConfirmModal.querySelector('.modal-desc');

      if (unanswered.length > 0) {
        if (modalDesc) {
          modalDesc.innerHTML = `<span style="color:#E3543F; font-weight:600;">Perhatian:</span> Ada ${unanswered.length} nomor yang belum Anda jawab (Nomor: ${unanswered.join(', ')}).<br><br>Apakah Anda tetap yakin ingin mengumpulkan jawaban sekarang? Jawaban tidak dapat diubah setelah dikumpulkan.`;
        }
      } else {
        if (modalDesc) {
          modalDesc.innerHTML = "Apakah Anda yakin ingin mengumpulkan jawaban ini?<br><strong>Jawaban tidak dapat diubah setelah dikumpulkan.</strong>";
        }
      }

      submitConfirmModal.classList.add('show');
    });

    if (btnConfirmCancel) {
      btnConfirmCancel.addEventListener('click', function () {
        submitConfirmModal.classList.remove('show');
      });
    }

    if (btnConfirmYes) {
      btnConfirmYes.addEventListener('click', function () {
        btnConfirmYes.disabled = true;
        btnConfirmYes.innerText = 'Mengirim Jawaban...';
        assessmentForm.submit();
      });
    }
  }

  function checkUnansweredQuestions() {
    const missing = [];
    const questionCards = document.querySelectorAll('.question-block');
    questionCards.forEach((card, idx) => {
      const qNum = idx + 1;
      const type = card.dataset.type;
      if (type === 'pg') {
        const checked = card.querySelector('input[type="radio"]:checked');
        if (!checked) missing.push(qNum);
      } else if (type === 'essay') {
        const textarea = card.querySelector('textarea');
        const fileInput = card.querySelector('input[type="file"]');
        const hasText = textarea && textarea.value.trim().length > 0;
        const hasFile = fileInput && fileInput.files && fileInput.files.length > 0;
        if (!hasText && !hasFile) missing.push(qNum);
      }
    });
    return missing;
  }

  // 3. FITUR TIMER COUNTDOWN PENGERJAAN UJIAN (AUTO-SUBMIT)
  const timerBadge = document.getElementById('examTimerBadge');
  const timerDisplay = document.getElementById('examTimerText');

  if (timerBadge && timerDisplay && assessmentForm) {
    const durationMinutes = parseInt(timerBadge.dataset.durationMinutes, 10) || 30;
    const serverStartTime = parseInt(timerBadge.dataset.startTime, 10) || 0;
    const storageKey = 'exam_timer_start_' + (timerBadge.dataset.assessmentId || 'assessment');
    
    let startTimestamp = serverStartTime > 0 ? (serverStartTime * 1000) : null;
    if (!startTimestamp) {
      const savedStart = localStorage.getItem(storageKey);
      if (savedStart) {
        startTimestamp = parseInt(savedStart, 10);
      } else {
        startTimestamp = Date.now();
        localStorage.setItem(storageKey, startTimestamp);
      }
    } else {
      localStorage.setItem(storageKey, startTimestamp);
    }

    const endTime = startTimestamp + (durationMinutes * 60 * 1000);

    const updateCountdown = function () {
      const remainingMs = endTime - Date.now();

      if (remainingMs <= 0) {
        if (timerInterval) clearInterval(timerInterval);
        localStorage.removeItem(storageKey);
        timerDisplay.innerText = "00:00 (Waktu Habis!)";
        alert("Waktu pengerjaan telah habis! Jawaban Anda sedang dikumpulkan secara otomatis oleh sistem.");
        assessmentForm.submit();
      } else {
        const totalSecs = Math.floor(remainingMs / 1000);
        const mins = Math.floor(totalSecs / 60);
        const secs = totalSecs % 60;
        const formatted = String(mins).padStart(2, '0') + ":" + String(secs).padStart(2, '0');
        timerDisplay.innerText = formatted;

        // Peringatan visual jika kurang dari 5 menit
        if (mins < 5) {
          timerBadge.style.backgroundColor = '#FEE2E2';
          timerBadge.style.borderColor = '#DC2626';
          timerDisplay.style.color = '#DC2626';
        }
      }
    };

    updateCountdown();
    const timerInterval = setInterval(updateCountdown, 1000);

    // Bersihkan storage timer saat form disubmit manual
    assessmentForm.addEventListener('submit', function () {
      localStorage.removeItem(storageKey);
    });
  }

  // 4. FITUR DETEKSI KELUAR TAB / PINDAH JENDELA BROWSER
  if (assessmentForm && assessmentForm.dataset.trackTab === "true") {
    let tabWarningShown = false;
    let lastLoggedAt = 0;
    const assessmentId = assessmentForm.dataset.assessmentId;
    const assessmentTitle = assessmentForm.dataset.assessmentTitle || 'Ujian Matematika';

    function sendTabSwitchLog(reason) {
      const now = Date.now();
      // Debounce 2 detik untuk menghindari duplikasi trigger blur & visibilitychange bersamaan
      if (now - lastLoggedAt < 2000) return;
      lastLoggedAt = now;

      fetch(window.APP_BASE_URL + '/api/log_tab_switch.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          assessment_id: assessmentId,
          assessment_title: assessmentTitle,
          reason: reason
        })
      })
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            showTabSwitchWarning(res.count);
          }
        })
        .catch(err => {
          console.error('Gagal mencatat log tab switch:', err);
          showTabSwitchWarning();
        });
    }

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) {
        sendTabSwitchLog('Pindah Tab Browser (visibility: hidden)');
      }
    });

    window.addEventListener('blur', function () {
      sendTabSwitchLog('Keluar Jendela / Klik Luar Aplikasi (window: blur)');
    });

    function showTabSwitchWarning(count) {
      if (tabWarningShown) return;
      tabWarningShown = true;

      const countText = count ? ` (Pelanggaran ke-${count})` : '';
      const toast = document.createElement('div');
      toast.style.position = 'fixed';
      toast.style.bottom = '24px';
      toast.style.left = '50%';
      toast.style.transform = 'translateX(-50%)';
      toast.style.backgroundColor = '#991B1B';
      toast.style.color = '#ffffff';
      toast.style.padding = '14px 24px';
      toast.style.borderRadius = '12px';
      toast.style.boxShadow = '0 10px 30px rgba(0,0,0,0.3)';
      toast.style.zIndex = '9999';
      toast.style.fontWeight = '600';
      toast.style.fontSize = '14px';
      toast.style.textAlign = 'center';
      toast.innerHTML = `⚠️ PERINGATAN: Anda terdeteksi meninggalkan halaman ujian!${countText}<br><small style="font-weight:400; opacity:0.9;">Aktivitas perpindahan tab telah dilaporkan secara realtime ke guru pengawas.</small>`;

      document.body.appendChild(toast);
      setTimeout(() => {
        toast.remove();
        tabWarningShown = false;
      }, 5000);
    }
  }

  // 5. HEARTBEAT PRESENSI ONLINE OTOMATIS
  setInterval(function () {
    fetch(window.APP_BASE_URL + '/api/presence.php')
      .catch(() => {});
  }, 30000); // Tiap 30 detik

  // 6. FITUR F: KOREKSI AI GEMINI OLEH GURU VIA AJAX
  document.querySelectorAll('.btn-ai-grade').forEach(btn => {
    btn.addEventListener('click', function () {
      const answerId = this.dataset.answerId;
      const promptText = this.dataset.prompt;
      const answerText = this.dataset.answer;
      const scoreInput = document.getElementById('score_input_' + answerId);
      const feedbackInput = document.getElementById('feedback_input_' + answerId);

      const origText = this.innerHTML;
      this.disabled = true;
      this.innerHTML = '<span>🤖 Menganalisis Jawaban...</span>';

      fetch(window.APP_BASE_URL + '/api/ai_grade.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          answer_id: answerId,
          prompt: promptText,
          answer: answerText
        })
      })
        .then(res => res.json())
        .then(res => {
          this.disabled = false;
          this.innerHTML = origText;
          if (res.success) {
            if (scoreInput) scoreInput.value = res.score;
            if (feedbackInput) feedbackInput.value = res.feedback;
            alert('Koreksi AI Berhasil!\nNilai Rekomendasi: ' + res.score + '\nUmpan Balik: ' + res.feedback + '\n\nAnda dapat mengubah nilai dan catatan umpan balik secara manual sebelum menekan Simpan.');
          } else {
            alert('Gagal evaluasi AI: ' + (res.message || 'Coba koreksi manual.'));
          }
        })
        .catch(err => {
          this.disabled = false;
          this.innerHTML = origText;
          alert('Terjadi kesalahan jaringan saat memanggil AI.');
        });
    });
  });

  // 7. KAMERA WEBCAM MODAL (UPLOAD & AMBIL FOTO PROFIL LANGSUNG)
  const btnOpenCam = document.getElementById('btnOpenCamModal');
  const camModal = document.getElementById('camModal');
  const btnCloseCam = document.getElementById('btnCloseCam');
  const btnCapturePhoto = document.getElementById('btnCapturePhoto');
  const videoElem = document.getElementById('webcamVideo');
  const canvasElem = document.getElementById('webcamCanvas');
  const photoPreview = document.getElementById('photoPreviewImg');
  const hiddenPhotoInput = document.getElementById('hiddenWebcamPhoto');

  let streamTrack = null;

  if (btnOpenCam && camModal && videoElem) {
    btnOpenCam.addEventListener('click', async function () {
      camModal.classList.add('show');
      try {
        const stream = await navigator.mediaDevices.getUserMedia({
          video: { width: { ideal: 640 }, height: { ideal: 640 }, facingMode: 'user' }
        });
        videoElem.srcObject = stream;
        streamTrack = stream.getTracks()[0];
      } catch (err) {
        alert('Gagal mengakses kamera: Pastikan izin kamera telah diberikan pada browser Anda.');
        camModal.classList.remove('show');
      }
    });

    function stopCamera() {
      if (streamTrack) {
        streamTrack.stop();
        streamTrack = null;
      }
      if (videoElem.srcObject) {
        videoElem.srcObject = null;
      }
      camModal.classList.remove('show');
    }

    if (btnCloseCam) btnCloseCam.addEventListener('click', stopCamera);

    if (btnCapturePhoto && canvasElem) {
      btnCapturePhoto.addEventListener('click', function () {
        const ctx = canvasElem.getContext('2d');
        canvasElem.width = videoElem.videoWidth || 400;
        canvasElem.height = videoElem.videoHeight || 400;
        ctx.drawImage(videoElem, 0, 0, canvasElem.width, canvasElem.height);

        const dataUrl = canvasElem.toDataURL('image/jpeg', 0.85);
        if (photoPreview) photoPreview.src = dataUrl;
        if (hiddenPhotoInput) hiddenPhotoInput.value = dataUrl;

        stopCamera();
      });
    }
  }

  // 8. KONFIRMASI HAPUS PENGGUNA ADMIN
  window.confirmDeleteUser = function (userId, username, role) {
    if (confirm(`Apakah Anda yakin ingin menghapus akun [${role.toUpperCase()}] ${username}? Seluruh data terkait (pengerjaan, nilai, relasi kelas) akan dihapus secara permanen.`)) {
      window.location.href = window.APP_BASE_URL + '/admin/users.php?action=delete&id=' + userId;
    }
  };
});
