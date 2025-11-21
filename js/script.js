// ページロード完了時の処理
window.addEventListener('load', function () {
    const loadingScreen = document.getElementById('loadingScreen');
    const gallery = document.getElementById('gallery');

    if (loadingScreen) {
        // ローディング画面をフェードアウト
        loadingScreen.classList.add('hidden');

        // アニメーション完了後に要素を削除
        setTimeout(() => {
            loadingScreen.style.display = 'none';
        }, 500);
    }

    if (gallery) {
        // ギャラリーを表示
        gallery.style.display = 'grid';
    }
});

// DOM要素の取得
const galleryItems = document.querySelectorAll('.gallery-item');
const imageModal = document.getElementById('imageModal');
const passwordModal = document.getElementById('passwordModal');
const modalImage = document.getElementById('modalImage');
const downloadBtn = document.getElementById('downloadBtn');
const closeBtn = document.querySelector('.close');
const closePasswordBtn = document.querySelector('.close-password');
const passwordInput = document.getElementById('passwordInput');
const passwordSubmit = document.getElementById('passwordSubmit');
const errorMessage = document.getElementById('errorMessage');
const sizeSlider = document.getElementById('sizeSlider');

// 画像サイズスライダー
if (sizeSlider) {
    // Helper to set columns and gap
    const updateGalleryLayout = (sliderValue) => {
        // Invert: Right (10) = Bigger Images (1 col), Left (1) = Smaller Images (10 cols)
        // Formula: columns = 11 - sliderValue
        const columns = 11 - parseInt(sliderValue);

        // Gap calculation: 120 / columns
        const gap = Math.floor(120 / columns);

        document.documentElement.style.setProperty('--gallery-columns', columns);
        document.documentElement.style.setProperty('--gallery-gap', gap + 'px');
    };

    // Load saved size (slider value)
    const savedSliderValue = localStorage.getItem('gallerySliderValue');
    if (savedSliderValue) {
        sizeSlider.value = parseInt(savedSliderValue);
        updateGalleryLayout(savedSliderValue);
    } else {
        // Default
        updateGalleryLayout(sizeSlider.value);
    }

    sizeSlider.addEventListener('input', function () {
        const newVal = this.value;
        updateGalleryLayout(newVal);
        localStorage.setItem('gallerySliderValue', newVal);
    });
}

// 現在選択されている画像情報
let currentOriginalPath = '';
let currentFileName = '';

// ギャラリーアイテムクリックイベント (index.phpのみ)
if (imageModal && modalImage) {
    galleryItems.forEach(item => {
        // admin.phpではonclick属性が使われているため、ここではイベントリスナーを追加しない
        // もしくは、admin-gallery-itemクラスがある場合は除外する
        if (item.classList.contains('admin-gallery-item')) return;

        item.addEventListener('click', function () {
            const thumbPath = this.getAttribute('data-thumb');
            currentOriginalPath = this.getAttribute('data-original');
            currentFileName = this.getAttribute('data-filename');

            modalImage.src = thumbPath;
            imageModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });
}

// ダウンロードボタンクリックイベント
if (downloadBtn) {
    downloadBtn.addEventListener('click', function () {
        // 画像モーダルを閉じる
        if (imageModal) imageModal.classList.remove('active');

        // パスワードモーダルを開く
        if (passwordModal) {
            passwordModal.classList.add('active');
            if (passwordInput) {
                passwordInput.value = '';
                passwordInput.focus();
            }
            if (errorMessage) errorMessage.textContent = '';
        }
    });
}

// パスワード送信
if (passwordSubmit) {
    passwordSubmit.addEventListener('click', function () {
        submitPassword();
    });
}

// Enterキーでパスワード送信
if (passwordInput) {
    passwordInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            submitPassword();
        }
    });
}

// パスワード送信処理
function submitPassword() {
    if (!passwordInput) return;
    const password = passwordInput.value.trim();

    if (password === '') {
        if (errorMessage) errorMessage.textContent = 'パスワードを入力してください';
        return;
    }

    // ローディング表示
    if (passwordSubmit) {
        passwordSubmit.disabled = true;
        passwordSubmit.textContent = '確認中...';
    }
    if (errorMessage) errorMessage.textContent = '';

    // PHPにパスワードと画像パスを送信
    fetch('download.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `password=${encodeURIComponent(password)}&image=${encodeURIComponent(currentOriginalPath)}&filename=${encodeURIComponent(currentFileName)}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // パスワードが正しい場合、ダウンロードを開始
                downloadImage(data.download_url);

                // モーダルを閉じる
                if (passwordModal) passwordModal.classList.remove('active');
                document.body.style.overflow = 'auto';

                // リセット
                passwordInput.value = '';
                if (errorMessage) errorMessage.textContent = '';
            } else {
                // パスワードが間違っている場合
                if (errorMessage) errorMessage.textContent = data.message || 'パスワードが正しくありません';
                passwordInput.value = '';
                passwordInput.focus();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (errorMessage) errorMessage.textContent = 'エラーが発生しました。もう一度お試しください。';
        })
        .finally(() => {
            if (passwordSubmit) {
                passwordSubmit.disabled = false;
                passwordSubmit.textContent = 'ダウンロード';
            }
        });
}

// 画像をダウンロード
function downloadImage(url) {
    const link = document.createElement('a');
    link.href = url;
    link.download = currentFileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// 画像モーダルを閉じる
if (closeBtn && imageModal) {
    closeBtn.addEventListener('click', function () {
        imageModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    });
}

// パスワードモーダルを閉じる
if (closePasswordBtn && passwordModal) {
    closePasswordBtn.addEventListener('click', function () {
        passwordModal.classList.remove('active');
        document.body.style.overflow = 'auto';
        if (passwordInput) passwordInput.value = '';
        if (errorMessage) errorMessage.textContent = '';
    });
}

// モーダル外クリックで閉じる
if (imageModal) {
    imageModal.addEventListener('click', function (e) {
        if (e.target === imageModal) {
            imageModal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    });
}

if (passwordModal) {
    passwordModal.addEventListener('click', function (e) {
        if (e.target === passwordModal) {
            passwordModal.classList.remove('active');
            document.body.style.overflow = 'auto';
            if (passwordInput) passwordInput.value = '';
            if (errorMessage) errorMessage.textContent = '';
        }
    });
}

// ESCキーでモーダルを閉じる
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        if (imageModal && imageModal.classList.contains('active')) {
            imageModal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        if (passwordModal && passwordModal.classList.contains('active')) {
            passwordModal.classList.remove('active');
            document.body.style.overflow = 'auto';
            if (passwordInput) passwordInput.value = '';
            if (errorMessage) errorMessage.textContent = '';
        }
        const editModal = document.getElementById('editModal');
        if (editModal && editModal.classList.contains('active')) {
            closeEditModal();
        }
    }
});

// Admin Panel Accordion
const adminPanelHeaders = document.querySelectorAll('.admin-panel-header');

adminPanelHeaders.forEach(header => {
    header.addEventListener('click', function () {
        const content = this.nextElementSibling;
        const icon = this.querySelector('.toggle-icon');

        content.classList.toggle('active');

        if (content.classList.contains('active')) {
            icon.style.transform = 'rotate(180deg)';
        } else {
            icon.style.transform = 'rotate(0deg)';
        }
    });
});

// Tag Filtering
const filterCheckboxes = document.querySelectorAll('input[name="filter_tags[]"]');

function filterImages() {
    const checkedTags = Array.from(filterCheckboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);

    const matchMode = document.querySelector('input[name="match_mode"]:checked').value;
    const galleryItems = document.querySelectorAll('.gallery-item');

    galleryItems.forEach(item => {
        if (checkedTags.length === 0) {
            item.style.display = '';
            return;
        }

        // data-tags属性がない場合（admin.phpなど）はフィルタリングしない、または空として扱う
        // admin.phpでもフィルタリングしたい場合はdata-tagsを追加する必要がある
        const tagsAttr = item.getAttribute('data-tags');
        if (!tagsAttr) return;

        const itemTags = JSON.parse(tagsAttr || '[]');

        let isMatch = false;
        if (matchMode === 'all') {
            // AND: すべての選択されたタグを含んでいるか
            isMatch = checkedTags.every(tag => itemTags.includes(tag));
        } else {
            // OR: いずれかの選択されたタグを含んでいるか
            isMatch = checkedTags.some(tag => itemTags.includes(tag));
        }

        if (isMatch) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });

    // Update URL without reloading
    const url = new URL(window.location);
    url.searchParams.delete('filter_tags[]');
    checkedTags.forEach(tag => {
        url.searchParams.append('filter_tags[]', tag);
    });
    url.searchParams.set('match_mode', matchMode);
    window.history.replaceState({}, '', url);
}

// Match mode change listener
const matchModeRadios = document.querySelectorAll('input[name="match_mode"]');
matchModeRadios.forEach(radio => {
    radio.addEventListener('change', function () {
        // Update UI classes
        document.querySelectorAll('.match-mode-option').forEach(opt => opt.classList.remove('active'));
        this.closest('.match-mode-option').classList.add('active');

        filterImages();
    });
});

filterCheckboxes.forEach(cb => {
    cb.addEventListener('change', filterImages);
});

// Initial filter on load (in case of back button or reload)
const urlParams = new URLSearchParams(window.location.search);
const savedMatchMode = urlParams.get('match_mode');
if (savedMatchMode) {
    const radio = document.querySelector(`input[name="match_mode"][value="${savedMatchMode}"]`);
    if (radio) {
        radio.checked = true;
        // Update UI classes for initial state
        document.querySelectorAll('.match-mode-option').forEach(opt => opt.classList.remove('active'));
        radio.closest('.match-mode-option').classList.add('active');
    }
}
filterImages();

// Admin Edit Modal Functions
function openEditModal(path, filename, tags) {
    const modal = document.getElementById('editModal');
    const modalImg = document.getElementById('editModalImage');
    const filenameInput = document.getElementById('editModalFilename');
    const tagsInput = document.getElementById('editModalTags');

    if (modal && modalImg && filenameInput && tagsInput) {
        modalImg.src = path;
        filenameInput.value = filename;
        tagsInput.value = tags;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

// モーダル外クリックで閉じる (editModal)
const editModal = document.getElementById('editModal');
if (editModal) {
    editModal.addEventListener('click', function (e) {
        if (e.target === editModal) {
            closeEditModal();
        }
    });
}

// Make functions global so they can be called from onclick attributes
window.openEditModal = openEditModal;
window.closeEditModal = closeEditModal;
