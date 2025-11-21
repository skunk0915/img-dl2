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

// 現在選択されている画像情報
let currentOriginalPath = '';
let currentFileName = '';

// ギャラリーアイテムクリックイベント
galleryItems.forEach(item => {
    item.addEventListener('click', function () {
        const thumbPath = this.getAttribute('data-thumb');
        currentOriginalPath = this.getAttribute('data-original');
        currentFileName = this.getAttribute('data-filename');

        modalImage.src = thumbPath;
        imageModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    });
});

// ダウンロードボタンクリックイベント
downloadBtn.addEventListener('click', function () {
    // 画像モーダルを閉じる
    imageModal.classList.remove('active');

    // パスワードモーダルを開く
    passwordModal.classList.add('active');
    passwordInput.value = '';
    errorMessage.textContent = '';
    passwordInput.focus();
});

// パスワード送信
passwordSubmit.addEventListener('click', function () {
    submitPassword();
});

// Enterキーでパスワード送信
passwordInput.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
        submitPassword();
    }
});

// パスワード送信処理
function submitPassword() {
    const password = passwordInput.value.trim();

    if (password === '') {
        errorMessage.textContent = 'パスワードを入力してください';
        return;
    }

    // ローディング表示
    passwordSubmit.disabled = true;
    passwordSubmit.textContent = '確認中...';
    errorMessage.textContent = '';

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
                passwordModal.classList.remove('active');
                document.body.style.overflow = 'auto';

                // リセット
                passwordInput.value = '';
                errorMessage.textContent = '';
            } else {
                // パスワードが間違っている場合
                errorMessage.textContent = data.message || 'パスワードが正しくありません';
                passwordInput.value = '';
                passwordInput.focus();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorMessage.textContent = 'エラーが発生しました。もう一度お試しください。';
        })
        .finally(() => {
            passwordSubmit.disabled = false;
            passwordSubmit.textContent = 'ダウンロード';
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
closeBtn.addEventListener('click', function () {
    imageModal.classList.remove('active');
    document.body.style.overflow = 'auto';
});

// パスワードモーダルを閉じる
closePasswordBtn.addEventListener('click', function () {
    passwordModal.classList.remove('active');
    document.body.style.overflow = 'auto';
    passwordInput.value = '';
    errorMessage.textContent = '';
});

// モーダル外クリックで閉じる
imageModal.addEventListener('click', function (e) {
    if (e.target === imageModal) {
        imageModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
});

passwordModal.addEventListener('click', function (e) {
    if (e.target === passwordModal) {
        passwordModal.classList.remove('active');
        document.body.style.overflow = 'auto';
        passwordInput.value = '';
        errorMessage.textContent = '';
    }
});

// ESCキーでモーダルを閉じる
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        if (imageModal.classList.contains('active')) {
            imageModal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        if (passwordModal.classList.contains('active')) {
            passwordModal.classList.remove('active');
            document.body.style.overflow = 'auto';
            passwordInput.value = '';
            errorMessage.textContent = '';
        }
    }
});
